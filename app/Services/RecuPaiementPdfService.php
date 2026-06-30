<?php

namespace App\Services;

use App\Models\RecuPaiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecuPaiementPdfService
{
    public function stream(RecuPaiement $recu): Response
    {
        $data = $this->buildViewData($recu);

        return Pdf::loadView('recus.payment-receipt-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream('Recu_'.$recu->numero_recu.'.pdf', ['Attachment' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(RecuPaiement $recu): array
    {
        $logoPath = public_path('assets/images/logo/logo.png');

        $sourceLabel = match ($recu->source_paiement) {
            'transactions' => 'Caisse',
            'financement' => 'Financement',
            'cheque' => 'Chèque',
            default => (string) $recu->source_paiement,
        };

        return [
            'recu' => $recu,
            'typeDocument' => $recu->typeLabel(),
            'sourceLabel' => $sourceLabel,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'dateCreation' => $recu->date_creation ?? now(),
        ];
    }

    public function findOrFail(int $id): RecuPaiement
    {
        $recu = RecuPaiement::query()->find($id);

        if (! $recu) {
            throw new NotFoundHttpException('Reçu introuvable.');
        }

        return $recu;
    }
}
