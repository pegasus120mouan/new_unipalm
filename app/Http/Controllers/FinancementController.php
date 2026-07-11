<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\FinancementPdfService;
use App\Services\FinancementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinancementController extends Controller
{
    public function __construct(
        private readonly FinancementService $financementService,
        private readonly FinancementPdfService $financementPdfService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->listFiltersFromRequest($request);

        $summaries = $this->financementService->paginatedAgentSummaries($filters);
        $financements = $this->financementService->detailedList($filters);
        $financementsEnAttente = $this->financementService->pendingValidations();

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('financements.index', compact(
            'summaries',
            'financements',
            'financementsEnAttente',
            'filters',
            'agents',
        ));
    }

    public function show(Request $request, Agent $agent): View
    {
        abort_if($agent->date_suppression !== null, 404);

        $agent->load('groupe');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'type_filter' => $request->input('type_filter', ''),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $stats = $this->financementService->statsForAgent($agent->id_agent);
        $financements = $this->financementService->paginatedAgentHistory($agent->id_agent, $filters);

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('financements.show', compact(
            'agent',
            'stats',
            'financements',
            'filters',
            'agents',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'montant' => ['required', 'numeric', 'min:1'],
            'motif' => ['required', 'string', 'max:1000'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $this->financementService->create(
            (int) $validated['id_agent'],
            (float) $validated['montant'],
            $validated['motif'],
        );

        $redirectTo = $validated['redirect_to'] ?? route('financements.index');
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, url('/financements'))) {
            $redirectTo = route('financements.index');
        }

        return redirect($redirectTo)
            ->with('success', 'Financement ajouté avec succès.');
    }

    public function valider(\App\Models\Financement $financement): RedirectResponse
    {
        try {
            $this->financementService->valider($financement);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['financement' => $e->getMessage()]);
        }

        return back()->with(
            'success',
            'Financement '.$financement->code_affiche.' validé avec succès.'
        );
    }

    public function pdf(Request $request, Agent $agent): Response
    {
        abort_if($agent->date_suppression !== null, 404);

        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        return $this->financementPdfService->download(
            $agent,
            $validated['date_debut'],
            $validated['date_fin'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function listFiltersFromRequest(Request $request): array
    {
        return [
            'search' => trim((string) $request->input('search', '')),
            'agent' => trim((string) $request->input('agent', '')),
            'agent_id' => $request->input('agent_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];
    }
}
