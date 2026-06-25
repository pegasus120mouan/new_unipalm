<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Usine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class UsinePaymentPdfService
{
    /**
     * @return Collection<int, Payment>
     */
    public function paymentsForUsine(Usine $usine): Collection
    {
        return Payment::query()
            ->where('id_usine', $usine->id_usine)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get();
    }

    public function download(Usine $usine): Response
    {
        $data = $this->buildViewData($usine);

        $filename = 'Historique_Paiements_'
            .str_replace(' ', '_', $usine->nom_usine)
            .'.pdf';

        return Pdf::loadView('usines.payments-history-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Usine $usine): array
    {
        $payments = $this->paymentsForUsine($usine);
        $totalMontant = $payments->sum(fn (Payment $payment) => (float) $payment->montant);

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'usine' => $usine,
            'payments' => $payments,
            'totalMontant' => $totalMontant,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'generatedAt' => now(),
        ];
    }
}
