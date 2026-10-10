<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Cca;
use App\Models\Pret;
use App\Services\PretService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        $resteCaisse = (float) Pret::query()
            ->where('id_agent', $agent->id_agent)
            ->where('origine', 'caisse')
            ->sum('montant_restant');
        $ccasPrets = Pret::query()
            ->with('cca')
            ->where('id_agent', $agent->id_agent)
            ->where('origine', 'cca')
            ->where('montant_restant', '>', 0)
            ->orderBy('id_pret')
            ->get()
            ->groupBy('id_cca')
            ->map(function ($rows) {
                $cca = $rows->first()->cca;

                return [
                    'id_cca' => (int) $rows->first()->id_cca,
                    'nom' => $cca?->nom ?: 'CCA',
                    'reste' => (float) $rows->sum('montant_restant'),
                ];
            })
            ->values();

        return view('prets.show', compact('agent', 'stats', 'prets', 'filters', 'agents', 'ccas', 'resteCaisse', 'ccasPrets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'montant_initial' => preg_replace('/\s+/', '', (string) $request->input('montant_initial', '')),
            'motif' => trim((string) $request->input('motif', '')),
        ]);

        $validated = $request->validate([
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'montant_initial' => ['required', 'numeric', 'min:1'],
            'motif' => ['required', 'string', 'max:255'],
            'origine' => ['required', Rule::in(['caisse', 'cca'])],
            'id_cca' => ['required_if:origine,cca', 'nullable', 'integer', 'exists:ccas,id_cca'],
            'redirect_to' => ['nullable', 'string'],
        ], [
            'origine.required' => 'Choisissez l\'origine du prêt.',
            'id_cca.required_if' => 'Sélectionnez le CCA.',
            'motif.required' => 'Indiquez le motif du prêt.',
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

    public function rembourser(Request $request, Agent $agent): RedirectResponse
    {
        abort_if($agent->date_suppression !== null, 404);

        $request->merge([
            'montant' => preg_replace('/\s+/', '', (string) $request->input('montant', '')),
        ]);

        $validator = Validator::make($request->all(), [
            'origine' => ['required', Rule::in(['caisse', 'cca'])],
            'id_cca' => ['required_if:origine,cca', 'nullable', 'integer', 'exists:ccas,id_cca'],
            'montant' => ['required', 'numeric', 'min:1'],
        ], [
            'origine.required' => 'Choisissez la source à rembourser.',
            'id_cca.required_if' => 'Sélectionnez le CCA.',
            'montant.required' => 'Indiquez le montant.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_remboursement_modal', true);
        }

        $validated = $validator->validated();

        try {
            $this->pretService->rembourser(
                (int) $agent->id_agent,
                (float) $validated['montant'],
                $validated['origine'],
                isset($validated['id_cca']) ? (int) $validated['id_cca'] : null,
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['remboursement' => $e->getMessage()])
                ->withInput()
                ->with('open_remboursement_modal', true);
        }

        $montant = number_format((float) $validated['montant'], 0, ',', ' ');
        $message = $validated['origine'] === 'caisse'
            ? 'Remboursement de '.$montant.' FCFA enregistré. La caisse a été créditée.'
            : 'Remboursement de '.$montant.' FCFA enregistré. Le CCA a été crédité.';

        return redirect()
            ->route('prets.show', $agent)
            ->with('success', $message);
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
