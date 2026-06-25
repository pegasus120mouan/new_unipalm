<?php

namespace App\Services;

use App\Models\Agent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FinancementPdfService
{
    public function __construct(
        private readonly FinancementService $financementService,
    ) {}

    public function download(Agent $agent, string $dateDebut, string $dateFin): Response
    {
        $data = $this->buildViewData($agent, $dateDebut, $dateFin);

        $filename = 'Historique_Financements_'
            .str_replace(' ', '_', $agent->full_name)
            .'_'.$dateDebut
            .'_'.$dateFin
            .'.pdf';

        return Pdf::loadView('financements.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Agent $agent, string $dateDebut, string $dateFin): array
    {
        if ($agent->date_suppression !== null) {
            throw new NotFoundHttpException('Agent introuvable.');
        }

        $agent->loadMissing('groupe');

        $financements = $this->financementService->historyForPeriod(
            $agent->id_agent,
            $dateDebut,
            $dateFin,
        );

        $stats = $this->financementService->periodStats($financements);

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'agent' => $agent,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'financements' => $financements,
            'stats' => $stats,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'generatedAt' => now(),
        ];
    }
}
