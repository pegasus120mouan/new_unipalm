<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\Ticket;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GroupeTicketPaymentService
{
    public function __construct(
        private readonly CaisseService $caisseService,
    ) {}

    /**
     * @return array{
     *     numero_recu: string,
     *     montant: float,
     *     tickets_soldes: int,
     *     tickets_partiels: int,
     *     nouveau_solde: float,
     *     reste_a_payer: float
     * }
     */
    public function pay(Groupe $groupe, Utilisateur $caissier, float $montant, ?string $motif = null): array
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        return DB::transaction(function () use ($groupe, $caissier, $montant, $motif) {
            $soldeCaisse = $this->caisseService->getSolde();

            if ($soldeCaisse < $montant) {
                throw new InvalidArgumentException(
                    'Solde de caisse insuffisant. Solde actuel : '.number_format($soldeCaisse, 0, '', ' ').' FCFA'
                );
            }

            $tickets = Ticket::query()
                ->join('agents as a', 'tickets.id_agent', '=', 'a.id_agent')
                ->where('a.id_chef', $groupe->id_chef)
                ->whereNull('a.date_suppression')
                ->select('tickets.*')
                ->validated()
                ->where(function ($q) {
                    $q->whereNull('tickets.date_paie')
                        ->whereRaw('COALESCE(tickets.montant_paie, 0) > COALESCE(tickets.montant_payer, 0)');
                })
                ->orderBy('tickets.date_ticket')
                ->orderBy('tickets.id_ticket')
                ->lockForUpdate()
                ->get();

            $montantRestant = $montant;
            $ticketsSoldes = 0;
            $ticketsPartiels = 0;

            foreach ($tickets as $ticket) {
                if ($montantRestant <= 0) {
                    break;
                }

                $montantTicket = (float) $ticket->montant_paie;
                $dejaPaye = (float) ($ticket->montant_payer ?? 0);
                $resteTicket = $montantTicket - $dejaPaye;

                if ($resteTicket <= 0) {
                    continue;
                }

                if ($montantRestant >= $resteTicket) {
                    $nouveauMontantPaye = $montantTicket;
                    $nouveauReste = 0.0;
                    $datePaie = now();
                    $montantRestant -= $resteTicket;
                    $ticketsSoldes++;
                } else {
                    $nouveauMontantPaye = $dejaPaye + $montantRestant;
                    $nouveauReste = $montantTicket - $nouveauMontantPaye;
                    $datePaie = null;
                    $montantRestant = 0;
                    $ticketsPartiels++;
                }

                $ticket->update([
                    'montant_payer' => $nouveauMontantPaye,
                    'montant_reste' => $nouveauReste,
                    'date_paie' => $datePaie,
                    'statut_ticket' => $nouveauReste <= 0 ? 'soldé' : 'non soldé',
                ]);
            }

            if ($montant - $montantRestant <= 0) {
                throw new InvalidArgumentException('Aucun ticket éligible au paiement pour ce chef d\'équipe.');
            }

            $montantApplique = $montant - $montantRestant;
            $nouveauSolde = $soldeCaisse - $montantApplique;
            $motifs = 'Paiement chef d\'équipe: '.$groupe->full_name;

            if ($motif) {
                $motifs .= ' - '.$motif;
            }

            DB::table('transactions')->insert([
                'type_transaction' => 'paiement',
                'montant' => $montantApplique,
                'date_transaction' => now(),
                'motifs' => $motifs,
                'id_utilisateur' => $caissier->id,
                'solde' => $nouveauSolde,
                'numero_cheque' => null,
            ]);

            $resteGlobal = (float) Ticket::query()
                ->join('agents as a', 'tickets.id_agent', '=', 'a.id_agent')
                ->where('a.id_chef', $groupe->id_chef)
                ->whereNull('a.date_suppression')
                ->validated()
                ->where(function ($q) {
                    $q->whereNull('tickets.date_paie')
                        ->whereRaw('COALESCE(tickets.montant_paie, 0) > COALESCE(tickets.montant_payer, 0)');
                })
                ->selectRaw('COALESCE(SUM(tickets.montant_paie - COALESCE(tickets.montant_payer, 0)), 0) AS reste')
                ->value('reste');

            return [
                'numero_recu' => 'CHEF-'.now()->format('Ymd').sprintf('%04d', random_int(1, 9999)),
                'montant' => $montantApplique,
                'tickets_soldes' => $ticketsSoldes,
                'tickets_partiels' => $ticketsPartiels,
                'nouveau_solde' => $nouveauSolde,
                'reste_a_payer' => $resteGlobal,
            ];
        });
    }
}
