<?php

namespace App\Http\Controllers;

use App\Models\RecuPaiement;
use App\Services\RecuPaiementPdfService;
use App\Services\RecuPaiementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RecuPaiementController extends Controller
{
    public function __construct(
        private readonly RecuPaiementService $recuPaiementService,
        private readonly RecuPaiementPdfService $recuPaiementPdfService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'type' => (string) $request->query('type', 'all'),
            'search' => trim((string) $request->query('search', '')),
            'agent_id' => trim((string) $request->query('agent_id', '')),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
        ];

        $recus = $this->recuPaiementService->paginated($filters);
        $stats = $this->recuPaiementService->stats($filters);
        $agents = $this->recuPaiementService->agentsForFilter();
        $hasFilters = $filters['type'] !== 'all'
            || $filters['search'] !== ''
            || $filters['agent_id'] !== ''
            || $filters['date_debut']
            || $filters['date_fin'];

        return view('recus.tickets.index', compact(
            'filters',
            'recus',
            'stats',
            'agents',
            'hasFilters',
        ));
    }

    public function pdf(RecuPaiement $recu): Response
    {
        return $this->recuPaiementPdfService->stream($recu);
    }
}
