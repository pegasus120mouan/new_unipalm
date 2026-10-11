<?php

namespace App\Services;

use App\Models\Bordereau;
use App\Models\Ticket;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AgentBordereauPaymentService
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

    public function pay(Bordereau $bordereau, Utilisateur $caissier, array $data): int
    {
        return DB::transaction(function () use ($bordereau, $caissier, $data): int {
            /** @var Bordereau $locked */
            $locked = Bordereau::query()
                ->whereKey($bordereau->id_bordereau)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isValidated()) {
                throw new InvalidArgumentException('Ce bordereau n\'est pas encore approuvé.');
            }

            $montantTotal = (float) $locked->montant_total;
            $montantPrecedent = (float) ($locked->montant_payer ?? 0);
            $reste = $locked->montant_reste !== null
                ? (float) $locked->montant_reste
                : max($montantTotal - $montantPrecedent, 0);

            if ($reste <= 0) {
                throw new InvalidArgumentException('Ce bordereau est déjà soldé.');
            }

            $montant = (float) $data['montant'];
            if ($montant <= 0) {
                throw new InvalidArgumentException('Le montant doit être supérieur à 0.');
            }

            if ($montant > $reste) {
                throw new InvalidArgumentException('Le montant dépasse le reste à payer.');
            }

            $source = $data['source_paiement'];
            $soldeFinancement = (float) $this->financementService->statsForAgent((int) $locked->id_agent)['solde_financement'];
            $soldeCaisse = $this->caisseService->getSolde();
            $montantUtilisable = $this->caisseService->getMontantUtilisable();

            $maxPayable = $this->maxPayableAmount($reste, $source, $soldeFinancement, $montantUtilisable);
            if ($montant > $maxPayable) {
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

            $locked->load('agent');

            if ($source === 'financement') {
                $this->financementService->create(
                    (int) $locked->id_agent,
                    -$montant,
                    'Paiement du bordereau '.$locked->numero_bordereau,
                );
            }

            $nouveauMontantPaye = $montantPrecedent + $montant;
            $nouveauReste = max($montantTotal - $nouveauMontantPaye, 0);

            $locked->update([
                'montant_payer' => $nouveauMontantPaye,
                'montant_reste' => $nouveauReste,
                'date_paie' => now(),
                'statut_bordereau' => $nouveauReste <= 0 ? 'soldé' : 'non soldé',
            ]);

            $locked->refresh();
            $this->syncTicketsWithBordereauPayment($locked);

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
                    'motifs' => 'Paiement du bordereau '.$locked->numero_bordereau,
                    'id_utilisateur' => $caissier->id,
                    'solde' => $soldeApres,
                    'numero_cheque' => null,
                ]);

                if ($source === 'transactions') {
                    $this->caisseService->debiterUtilisable($montant);
                }
            }

            $numeroRecu = now()->format('Ymd').sprintf('%04d', random_int(1, 9999));

            $recuId = (int) DB::table('recus_paiements')->insertGetId([
                'numero_recu' => $numeroRecu,
                'type_document' => 'bordereau',
                'id_document' => $locked->id_bordereau,
                'numero_document' => $locked->numero_bordereau,
                'montant_total' => $montantTotal,
                'montant_paye' => $montant,
                'montant_precedent' => $montantPrecedent,
                'reste_a_payer' => $nouveauReste,
                'id_agent' => $locked->id_agent,
                'nom_agent' => $locked->agent?->full_name,
                'contact_agent' => $locked->agent?->contact,
                'nom_usine' => null,
                'matricule_vehicule' => null,
                'id_caissier' => $caissier->id,
                'nom_caissier' => $caissier->full_name,
                'source_paiement' => $source,
                'numero_cheque' => $numeroCheque,
                'id_transaction' => $idTransaction,
                'date_creation' => now(),
            ]);

            return $recuId;
        });
    }

    /**
     * @return array{total: int, a_solder: int, deja_soldes: int, reste: float}
     */
    public function syntheseSoldes(): array
    {
        $total = Bordereau::query()->count();
        $aSolder = $this->bordereauxASolderQuery()->count();
        $reste = (float) ($this->bordereauxASolderQuery()
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(montant_total, 0) - COALESCE(montant_payer, 0), 0)), 0) AS reste')
            ->first()
            ->reste ?? 0);

        return [
            'total' => $total,
            'a_solder' => $aSolder,
            'deja_soldes' => max(0, $total - $aSolder),
            'reste' => $reste,
        ];
    }

    /**
     * Marque soldés tous les bordereaux générés encore ouverts, et répartit le paiement sur leurs tickets.
     * La caisse n'est pas débitée.
     */
    public function solderTous(): int
    {
        $count = 0;

        DB::transaction(function () use (&$count): void {
            $this->bordereauxASolderQuery()
                ->orderBy('id_bordereau')
                ->chunkById(100, function ($bordereaux) use (&$count): void {
                    foreach ($bordereaux as $bordereau) {
                        $bordereau->update([
                            'montant_payer' => (float) $bordereau->montant_total,
                            'montant_reste' => 0,
                            'date_paie' => $bordereau->date_paie ?? now(),
                            'statut_bordereau' => 'soldé',
                        ]);

                        $this->syncTicketsWithBordereauPayment($bordereau->refresh());
                        $count++;
                    }
                }, 'id_bordereau');
        });

        return $count;
    }

    private function bordereauxASolderQuery()
    {
        return Bordereau::query()->where(function ($query): void {
            $query->whereRaw("LOWER(COALESCE(statut_bordereau, '')) <> 'soldé'")
                ->orWhere('montant_reste', '>', 0.009);
        });
    }

    public function syncTicketsForAgent(int $agentId): void
    {
        Bordereau::query()
            ->where('id_agent', $agentId)
            ->whereNotNull('date_validation_boss')
            ->where('montant_payer', '>', 0)
            ->orderBy('id_bordereau')
            ->each(function (Bordereau $bordereau) {
                if ($this->ticketsNeedSync($bordereau)) {
                    $this->syncTicketsWithBordereauPayment($bordereau);
                }
            });
    }

    public function syncTicketsWithBordereauPayment(Bordereau $bordereau): void
    {
        $tickets = Ticket::query()
            ->where('numero_bordereau', $bordereau->numero_bordereau)
            ->validated()
            ->orderBy('date_ticket')
            ->orderBy('id_ticket')
            ->get();

        if ($tickets->isEmpty()) {
            return;
        }

        foreach ($tickets as $ticket) {
            $montantPaie = $this->ticketMontantPaie($ticket);
            $ticket->update([
                'montant_payer' => 0,
                'montant_reste' => $montantPaie,
                'date_paie' => null,
                'statut_ticket' => 'non soldé',
            ]);
        }

        $remaining = (float) ($bordereau->montant_payer ?? 0);
        $datePaie = $bordereau->date_paie ?? now();

        foreach ($tickets as $ticket) {
            if ($remaining <= 0) {
                break;
            }

            $montantPaie = $this->ticketMontantPaie($ticket);
            $toApply = min($remaining, $montantPaie);
            $newReste = max($montantPaie - $toApply, 0);

            $ticket->update([
                'montant_payer' => $toApply,
                'montant_reste' => $newReste,
                'date_paie' => $toApply > 0 ? $datePaie : null,
                'statut_ticket' => $newReste <= 0 && $toApply > 0 ? 'soldé' : 'non soldé',
            ]);

            $remaining -= $toApply;
        }
    }

    private function ticketsNeedSync(Bordereau $bordereau): bool
    {
        $ticketPaye = (float) Ticket::query()
            ->where('numero_bordereau', $bordereau->numero_bordereau)
            ->sum('montant_payer');

        return abs($ticketPaye - (float) ($bordereau->montant_payer ?? 0)) > 0.01;
    }

    private function ticketMontantPaie(Ticket $ticket): float
    {
        if ($ticket->montant_paie !== null) {
            return (float) $ticket->montant_paie;
        }

        return (float) $ticket->prix_unitaire * (float) $ticket->poids;
    }
}
