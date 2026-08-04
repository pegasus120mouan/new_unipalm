<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Usine;
use App\Models\Utilisateur;
use App\Services\ApprovisionnementService;
use Illuminate\Support\Facades\DB;

class UsinePaymentService
{
    public function __construct(
        private readonly ApprovisionnementService $approvisionnementService,
        private readonly UsineFinancementService $usineFinancementService,
    ) {}

    public function resteAPayer(int $idUsine): float
    {
        return (float) Ticket::query()
            ->validated()
            ->where('id_usine', $idUsine)
            ->where('montant_reste', '>', 0)
            ->sum('montant_reste');
    }

    /**
     * Enregistre un paiement pour une usine et le répartit sur les tickets
     * non soldés de cette usine, du plus ancien au plus récent.
     *
     * @param  float|null  $restePlafond  Si fourni, plafonne le paiement (ex. bilan des entrées).
     *
     * @throws \InvalidArgumentException
     */
    public function create(
        Usine $usine,
        float $montant,
        string $datePaiement,
        string $modePaiement,
        ?string $referencePaiement,
        ?Utilisateur $utilisateur = null,
        bool $crediterCaisse = true,
        ?float $restePlafond = null,
        bool $distributeToTickets = true,
    ): Payment {
        $resteTickets = (float) Ticket::query()
            ->validated()
            ->where('id_usine', $usine->id_usine)
            ->where('montant_reste', '>', 0)
            ->sum('montant_reste');

        $resteTotal = $restePlafond !== null ? max(0, $restePlafond) : $resteTickets;

        if ($resteTotal <= 0) {
            throw new \InvalidArgumentException('Aucun montant restant à payer pour cette usine.');
        }

        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant du paiement doit être supérieur à 0.');
        }

        if ($restePlafond !== null && $montant > $resteTotal + 0.009) {
            throw new \InvalidArgumentException(
                'Le montant du paiement dépasse le reste à payer ('
                .number_format($resteTotal, 0, ',', ' ')
                .' FCFA).'
            );
        }

        return DB::transaction(function () use (
            $usine,
            $montant,
            $datePaiement,
            $modePaiement,
            $referencePaiement,
            $utilisateur,
            $crediterCaisse,
            $distributeToTickets,
        ) {
            $payment = Payment::create([
                'id_usine' => $usine->id_usine,
                'montant' => $montant,
                'date_paiement' => $datePaiement,
                'mode_paiement' => $modePaiement,
                'reference_paiement' => $referencePaiement,
            ]);

            if ($distributeToTickets) {
                $this->distributePayment($usine, $montant, $datePaiement);
            }

            if ($crediterCaisse && $utilisateur !== null) {
                $this->approvisionnementService->recordUsinePayment(
                    $usine,
                    $montant,
                    $modePaiement,
                    $referencePaiement,
                    $utilisateur,
                    \Carbon\Carbon::parse($datePaiement),
                );
            }

            return $payment;
        });
    }

    /**
     * Paiement des livraisons déduit du solde de financement usine (sans crédit banque).
     *
     * @throws \InvalidArgumentException
     */
    public function createFromFinancement(
        Usine $usine,
        float $montant,
        string $datePaiement,
        ?string $referencePaiement,
        ?Utilisateur $utilisateur,
        float $restePlafond,
    ): Payment {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant du paiement doit être supérieur à 0.');
        }

        if ($restePlafond <= 0) {
            throw new \InvalidArgumentException('Aucun montant restant à payer pour cette usine.');
        }

        if ($montant > $restePlafond + 0.009) {
            throw new \InvalidArgumentException(
                'Le montant du paiement dépasse le reste à payer ('
                .number_format($restePlafond, 0, ',', ' ')
                .' FCFA).'
            );
        }

        $soldeFinancement = $this->usineFinancementService->solde((int) $usine->id_usine);
        if ($soldeFinancement <= 0) {
            throw new \InvalidArgumentException('Aucun financement disponible pour cette usine.');
        }

        if ($montant > $soldeFinancement + 0.009) {
            throw new \InvalidArgumentException(
                'Le montant dépasse le financement disponible ('
                .number_format($soldeFinancement, 0, ',', ' ')
                .' FCFA).'
            );
        }

        return DB::transaction(function () use (
            $usine,
            $montant,
            $datePaiement,
            $referencePaiement,
            $utilisateur,
        ) {
            $payment = Payment::create([
                'id_usine' => $usine->id_usine,
                'montant' => $montant,
                'date_paiement' => $datePaiement,
                'mode_paiement' => 'Financement',
                'reference_paiement' => $referencePaiement,
            ]);

            $this->distributePayment($usine, $montant, $datePaiement);

            $this->usineFinancementService->deduire(
                $usine,
                $montant,
                $datePaiement,
                $referencePaiement,
                $utilisateur,
            );

            return $payment;
        });
    }

    private function distributePayment(Usine $usine, float $montant, string $datePaiement): void
    {
        $remaining = $montant;

        $tickets = Ticket::query()
            ->validated()
            ->where('id_usine', $usine->id_usine)
            ->where('montant_reste', '>', 0)
            ->orderBy('date_ticket')
            ->orderBy('id_ticket')
            ->get();

        foreach ($tickets as $ticket) {
            if ($remaining <= 0) {
                break;
            }

            $ticketReste = (float) $ticket->montant_reste;
            $toApply = min($remaining, $ticketReste);

            $newMontantPayer = (float) $ticket->montant_payer + $toApply;
            $newMontantReste = $ticketReste - $toApply;

            $ticket->update([
                'montant_payer' => $newMontantPayer,
                'montant_reste' => $newMontantReste,
                'date_paie' => $datePaiement,
                'statut_ticket' => $newMontantReste <= 0 ? 'soldé' : 'non soldé',
            ]);

            $remaining -= $toApply;
        }
    }
}
