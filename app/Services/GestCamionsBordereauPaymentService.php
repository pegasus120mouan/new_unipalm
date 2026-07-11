<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\BordereauAgentGestCamions;
use App\Models\PaiementAgentGestCamions;
use App\Models\Ticket;
use App\Models\Utilisateur;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GestCamionsBordereauPaymentService
{
    public function __construct(
        private readonly CaisseService $caisseService,
        private readonly FinancementService $financementService,
    ) {}

    public function maxPayableAmount(
        float $reste,
        string $source,
        float $soldeFinancement,
        float $soldeCaisse,
    ): float {
        return match ($source) {
            'financement' => min($reste, max(0, $soldeFinancement)),
            'transactions' => min($reste, max(0, $soldeCaisse)),
            default => $reste,
        };
    }

    /**
     * @param  array{montant: float|int|string, source_paiement: string, numero_cheque?: string|null}  $data
     */
    public function pay(BordereauAgentGestCamions $bordereau, Utilisateur $caissier, array $data): int
    {
        return DB::transaction(function () use ($bordereau, $caissier, $data): int {
            /** @var BordereauAgentGestCamions $locked */
            $locked = BordereauAgentGestCamions::query()
                ->whereKey($bordereau->id)
                ->lockForUpdate()
                ->firstOrFail();

            $montantTotal = (float) $locked->montant_total;
            $montantPrecedent = (float) ($locked->montant_paye ?? 0);
            $reste = max($montantTotal - $montantPrecedent, 0);

            if ($reste <= 0) {
                throw new InvalidArgumentException('Ce bordereau est déjà soldé.');
            }

            $montant = (float) $data['montant'];
            if ($montant <= 0) {
                throw new InvalidArgumentException('Le montant doit être supérieur à 0.');
            }

            if ($montant > $reste) {
                throw new InvalidArgumentException('Le montant dépasse le reste à payer du bordereau.');
            }

            $source = (string) $data['source_paiement'];
            $soldeFinancement = (float) $this->financementService->statsForAgent((int) $locked->id_agent)['solde_financement'];
            $soldeCaisse = $this->caisseService->getSolde();
            $montantUtilisable = $this->caisseService->getMontantUtilisable();

            $maxPayable = $this->maxPayableAmount($reste, $source, $soldeFinancement, $montantUtilisable);
            if ($montant > $maxPayable) {
                if ($source === 'financement') {
                    throw new InvalidArgumentException(
                        'Le paiement ne peut pas dépasser le financement disponible ('
                        .number_format($soldeFinancement, 0, '', ' ')
                        .' FCFA). Maximum autorisé : '
                        .number_format($maxPayable, 0, '', ' ')
                        .' FCFA.'
                    );
                }

                throw new InvalidArgumentException('Le montant dépasse le maximum autorisé pour cette source de paiement.');
            }

            $numeroCheque = null;
            if ($source === 'cheque') {
                $numeroCheque = trim((string) ($data['numero_cheque'] ?? ''));
                if ($numeroCheque === '') {
                    throw new InvalidArgumentException('Le numéro de chèque est obligatoire.');
                }

                $exists = DB::table('recus_paiements')
                    ->where('numero_cheque', $numeroCheque)
                    ->exists();

                if ($exists) {
                    throw new InvalidArgumentException('Ce numéro de chèque a déjà été utilisé.');
                }
            }

            if ($source === 'financement' && $soldeFinancement <= 0) {
                throw new InvalidArgumentException('Solde de financement insuffisant pour cet agent.');
            }

            if ($source === 'transactions' && $montantUtilisable < $montant) {
                throw new InvalidArgumentException(
                    'Montant utilisable insuffisant. Disponible : '.number_format($montantUtilisable, 0, '', ' ').' FCFA'
                );
            }

            $agent = Agent::query()->find($locked->id_agent);

            if ($source === 'financement') {
                $this->financementService->create(
                    (int) $locked->id_agent,
                    -$montant,
                    'Paiement du bordereau '.$locked->numero.' (gest-camions)',
                );
            }

            $nouveauMontantPaye = $montantPrecedent + $montant;
            $nouveauReste = max($montantTotal - $nouveauMontantPaye, 0);

            $locked->update([
                'montant_paye' => $nouveauMontantPaye,
            ]);

            // Les tickets transformés en bordereau doivent aussi être soldés :
            // le solde actuel (tickets) diminue du montant payé.
            $this->syncTicketsWithBordereauPayment($locked, $montant);

            $modePaiement = match ($source) {
                'financement' => 'Financement',
                'cheque' => 'Chèque',
                default => 'Espèces',
            };

            $paiement = PaiementAgentGestCamions::query()->create([
                'id_agent' => $locked->id_agent,
                'id_bordereau' => $locked->id,
                'montant' => (int) round($montant),
                'date_paiement' => now()->toDateString(),
                'mode_paiement' => $modePaiement,
                'reference' => $numeroCheque,
                'commentaire' => 'Paiement depuis comptes groupes',
            ]);

            $soldeApres = $soldeCaisse;
            $idTransaction = null;

            if ($source !== 'cheque') {
                if ($source === 'transactions') {
                    $soldeApres = $soldeCaisse - $montant;
                }

                $idTransaction = DB::table('transactions')->insertGetId([
                    'type_transaction' => 'paiement',
                    'montant' => $source === 'transactions' ? $montant : 0,
                    'date_transaction' => now(),
                    'motifs' => 'Paiement du bordereau '.$locked->numero.' (gest-camions)',
                    'id_utilisateur' => $caissier->id,
                    'solde' => $soldeApres,
                    'numero_cheque' => null,
                ]);

                if ($source === 'transactions') {
                    $this->caisseService->debiterUtilisable($montant);
                }
            }

            $numeroRecu = now()->format('Ymd').sprintf('%04d', random_int(1, 9999));

            $paiement->update(['numero_recu' => $numeroRecu]);

            return (int) DB::table('recus_paiements')->insertGetId([
                'numero_recu' => $numeroRecu,
                'type_document' => 'bordereau',
                'id_document' => $locked->id,
                'numero_document' => $locked->numero,
                'montant_total' => $montantTotal,
                'montant_paye' => $montant,
                'montant_precedent' => $montantPrecedent,
                'reste_a_payer' => $nouveauReste,
                'id_agent' => $locked->id_agent,
                'nom_agent' => $locked->agent_nom ?: $agent?->full_name,
                'contact_agent' => $agent?->contact,
                'nom_usine' => null,
                'matricule_vehicule' => null,
                'id_caissier' => $caissier->id,
                'nom_caissier' => $caissier->full_name,
                'source_paiement' => $source,
                'numero_cheque' => $numeroCheque,
                'id_transaction' => $idTransaction,
                'date_creation' => now(),
            ]);
        });
    }

    /**
     * Applique le paiement sur les tickets Unipalm de l'agent
     * (tickets du bordereau si trouvés, sinon FIFO sur les tickets non soldés).
     */
    public function syncTicketsWithBordereauPayment(BordereauAgentGestCamions $bordereau, float $montantPaiement): void
    {
        if ($montantPaiement <= 0) {
            return;
        }

        $tickets = $this->resolveTicketsForBordereau($bordereau);

        if ($tickets->isEmpty()) {
            return;
        }

        $remaining = $montantPaiement;
        $datePaie = now();

        foreach ($tickets as $ticket) {
            if ($remaining <= 0) {
                break;
            }

            $montantTicket = $this->ticketMontantPaie($ticket);
            $dejaPaye = (float) ($ticket->montant_payer ?? 0);
            $resteTicket = max($montantTicket - $dejaPaye, 0);

            if ($resteTicket <= 0) {
                continue;
            }

            $toApply = min($remaining, $resteTicket);
            $nouveauPaye = $dejaPaye + $toApply;
            $nouveauReste = max($montantTicket - $nouveauPaye, 0);

            $ticket->update([
                'montant_payer' => $nouveauPaye,
                'montant_reste' => $nouveauReste,
                'date_paie' => $nouveauPaye > 0 ? $datePaie : null,
                'statut_ticket' => $nouveauReste <= 0 && $nouveauPaye > 0 ? 'soldé' : 'non soldé',
                'numero_bordereau' => $ticket->numero_bordereau ?: $bordereau->numero,
            ]);

            $remaining -= $toApply;
        }
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function resolveTicketsForBordereau(BordereauAgentGestCamions $bordereau): Collection
    {
        $agentId = (int) $bordereau->id_agent;
        $numeros = collect($bordereau->fiches_data ?? [])
            ->pluck('numero_ticket')
            ->filter(fn ($n) => is_string($n) && trim($n) !== '' && trim($n) !== '—')
            ->map(fn ($n) => trim((string) $n))
            ->unique()
            ->values();

        if ($numeros->isNotEmpty()) {
            $linked = Ticket::query()
                ->where('id_agent', $agentId)
                ->whereIn('numero_ticket', $numeros->all())
                ->whereNotNull('montant_paie')
                ->where('montant_paie', '>', 0)
                ->orderBy('date_ticket')
                ->orderBy('id_ticket')
                ->lockForUpdate()
                ->get();

            if ($linked->isNotEmpty()) {
                return $linked;
            }
        }

        // Fallback : tickets non soldés de l'agent (FIFO), car le bordereau
        // représente une transformation de ces tickets en créance à payer.
        return Ticket::query()
            ->where('id_agent', $agentId)
            ->validated()
            ->where(function ($q) {
                $q->whereNull('date_paie')
                    ->whereRaw('COALESCE(montant_paie, 0) > COALESCE(montant_payer, 0)');
            })
            ->orderBy('date_ticket')
            ->orderBy('id_ticket')
            ->lockForUpdate()
            ->get();
    }

    private function ticketMontantPaie(Ticket $ticket): float
    {
        if ($ticket->montant_paie !== null) {
            return (float) $ticket->montant_paie;
        }

        return (float) $ticket->prix_unitaire * (float) $ticket->poids;
    }
}
