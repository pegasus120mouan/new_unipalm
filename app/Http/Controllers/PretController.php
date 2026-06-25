<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\PretService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PretController extends Controller
{
    public function __construct(
        private readonly PretService $pretService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->listFiltersFromRequest($request);

        $summaries = $this->pretService->paginatedAgentSummaries($filters);
        $allSummaries = $this->pretService->agentSummariesQuery($filters)->get();
        $globalStats = $this->pretService->globalStats($allSummaries);
        $prets = $this->pretService->detailedList($filters);

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('prets.index', compact('summaries', 'globalStats', 'prets', 'filters', 'agents'));
    }

    public function show(Request $request, Agent $agent): View
    {
        abort_if($agent->date_suppression !== null, 404);

        $agent->load('groupe');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'statut' => $request->input('statut', ''),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $stats = $this->pretService->statsForAgent($agent->id_agent);
        $prets = $this->pretService->paginatedAgentPrets($agent->id_agent, $filters);

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('prets.show', compact('agent', 'stats', 'prets', 'filters', 'agents'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('montant_initial')) {
            $request->merge([
                'montant_initial' => preg_replace('/\s+/', '', (string) $request->input('montant_initial')),
            ]);
        }

        $validated = $request->validate([
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'montant_initial' => ['required', 'numeric', 'min:1'],
            'motif' => ['nullable', 'string', 'max:255'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $this->pretService->create(
            (int) $validated['id_agent'],
            (float) $validated['montant_initial'],
            $validated['motif'] ?? null,
        );

        $redirectTo = $validated['redirect_to'] ?? route('prets.index');
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, url('/prets'))) {
            $redirectTo = route('prets.index');
        }

        return redirect($redirectTo)
            ->with('success', 'Prêt enregistré avec succès.');
    }

    /**
     * @return array<string, mixed>
     */
    private function listFiltersFromRequest(Request $request): array
    {
        return [
            'search' => trim((string) $request->input('search', '')),
            'agent_id' => $request->input('agent_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'statut' => $request->input('statut'),
        ];
    }
}
