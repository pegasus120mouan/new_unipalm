<?php

namespace App\Services;

use App\Models\Agent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AgentTransactionsHistoryPdfService
{
    public function __construct(
        private readonly FinancementService $financementService,
    ) {}

    public function download(Agent $agent, string $dateDebut, string $dateFin): Response
    {
        $data = $this->buildViewData($agent, $dateDebut, $dateFin);

        $filename = 'Historique_Transactions_'
            .str_replace(' ', '_', $agent->full_name)
            .'_'.$dateDebut
            .'_'.$dateFin
            .'.pdf';

        return Pdf::loadView('comptes-agents.transactions-history-pdf', $data)
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

        $paiements = $this->paymentsForPeriod($agent->id_agent, $dateDebut, $dateFin);
        $financements = $this->financementService->historyForPeriod(
            $agent->id_agent,
            $dateDebut,
            $dateFin,
        );

        $totalPaye = $paiements->sum(fn ($row) => (float) $row->montant_paye);
        $financementStats = $this->financementService->periodStats($financements);

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'agent' => $agent,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'paiements' => $paiements,
            'financements' => $financements,
            'totalPaye' => $totalPaye,
            'financementStats' => $financementStats,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'generatedAt' => now(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function paymentsForPeriod(int $agentId, string $dateDebut, string $dateFin): Collection
    {
        return DB::table('recus_paiements')
            ->where('id_agent', $agentId)
            ->whereBetween('date_creation', [
                $dateDebut.' 00:00:00',
                $dateFin.' 23:59:59',
            ])
            ->orderByDesc('date_creation')
            ->orderByDesc('numero_recu')
            ->get();
    }
}
