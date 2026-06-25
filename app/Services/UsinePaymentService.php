<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Usine;
use Illuminate\Support\Facades\DB;

class UsinePaymentService
{
    /**
     * Enregistre un paiement pour une usine et le répartit sur les tickets
     * non soldés de cette usine, du plus ancien au plus récent.
     *
     * @throws \InvalidArgumentException
     */
    public function create(
        Usine $usine,
        float $montant,
        string $datePaiement,
        string $modePaiement,
        ?string $referencePaiement,
    ): Payment {
        $resteTotal = Ticket::query()
            ->validated()
            ->where('id_usine', $usine->id_usine)
            ->where('montant_reste', '>', 0)
            ->sum('montant_reste');

        if ((float) $resteTotal <= 0) {
            throw new \InvalidArgumentException('Aucun montant restant à payer pour cette usine.');
        }

        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant du paiement doit être supérieur à 0.');
        }

        return DB::transaction(function () use (
            $usine,
            $montant,
            $datePaiement,
            $modePaiement,
            $referencePaiement,
        ) {
            $payment = Payment::create([
                'id_usine' => $usine->id_usine,
                'montant' => $montant,
                'date_paiement' => $datePaiement,
                'mode_paiement' => $modePaiement,
                'reference_paiement' => $referencePaiement,
            ]);

            $this->distributePayment($usine, $montant, $datePaiement);

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
