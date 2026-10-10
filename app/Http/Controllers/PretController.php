<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Cca;
use App\Services\PretService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $ccas = $this->ccaOptions();

        return view('prets.index', compact('summaries', 'globalStats', 'prets', 'filters', 'agents', 'ccas'));
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

        $ccas = $this->ccaOptions();

        return view('prets.show', compact('agent', 'stats', 'prets', 'filters', 'agents', 'ccas'));
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
            'origine' => ['required', Rule::in(['caisse', 'cca'])],
            'id_cca' => ['required_if:origine,cca', 'nullable', 'integer', 'exists:ccas,id_cca'],
            'redirect_to' => ['nullable', 'string'],
        ], [
            'origine.required' => 'Choisissez l\'origine du prêt.',
            'id_cca.required_if' => 'Sélectionnez le CCA.',
        ]);

        $cca = $validated['origine'] === 'cca'
            ? Cca::query()->findOrFail($validated['id_cca'])
            : null;

        try {
            $this->pretService->create(
                (int) $validated['id_agent'],
                (float) $validated['montant_initial'],
                $validated['motif'] ?? null,
                $validated['origine'],
                $cca?->id_cca,
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['montant_initial' => $e->getMessage()])
                ->withInput();
        }

        $redirectTo = $validated['redirect_to'] ?? route('prets.index');
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, url('/prets'))) {
            $redirectTo = route('prets.index');
        }

        $montant = number_format((float) $validated['montant_initial'], 0, ',', ' ');
        $message = $cca
            ? 'Prêt enregistré. '.$montant.' FCFA reversés sur le CCA '.$cca->nom.'.'
            : 'Prêt enregistré. La caisse a été débitée de '.$montant.' FCFA.';

        return redirect($redirectTo)->with('success', $message);
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

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Cca>
     */
    private function ccaOptions()
    {
        return Cca::query()->withTotaux()->orderBy('nom')->get();
    }
}
