<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Groupe;
use App\Models\PontBascule;
use App\Services\AgentService;
use App\Services\AgentDocumentService;
use App\Services\MinioStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentService $agentService,
        private readonly AgentDocumentService $documentService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'id_agent' => $request->query('id_agent') ? (int) $request->query('id_agent') : null,
            'search_contact' => trim((string) $request->query('search_contact', '')),
            'search_groupe' => trim((string) $request->query('search_groupe', '')),
            'sous_groupe' => trim((string) $request->query('sous_groupe', '')),
        ];

        $agentsQuery = Agent::query()
            ->with(['groupe', 'createur', 'photoIdentiteDocument'])
            ->withCount('ponts')
            ->whereNull('date_suppression');

        if ($filters['id_agent']) {
            $agentsQuery->where('id_agent', $filters['id_agent']);
        } elseif ($filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $agentsQuery->where(function ($query) use ($term) {
                $query->where('nom', 'like', $term)
                    ->orWhere('prenom', 'like', $term)
                    ->orWhere('numero_agent', 'like', $term)
                    ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", [$term]);
            });
        }

        if ($filters['search_contact'] !== '') {
            $agentsQuery->where('contact', 'like', '%'.$filters['search_contact'].'%');
        }

        if ($filters['search_groupe'] !== '') {
            $agentsQuery->whereHas('groupe', function ($query) use ($filters) {
                $term = '%'.$filters['search_groupe'].'%';
                $query->where('nom', 'like', $term)
                    ->orWhere('prenoms', 'like', $term)
                    ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$term]);
            });
        }

        if (in_array($filters['sous_groupe'], [Agent::SOUS_GROUPE_PARTICULIER, Agent::SOUS_GROUPE_PROFESSIONNEL], true)) {
            $agentsQuery->where('sous_groupe', $filters['sous_groupe']);
        } else {
            $filters['sous_groupe'] = '';
        }

        $agents = $agentsQuery
            ->orderByDesc('date_ajout')
            ->orderByDesc('id_agent')
            ->paginate(15)
            ->withQueryString();

        $groupes = Groupe::query()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        $hasFilters = collect($filters)->contains(fn ($value) => $value !== null && $value !== '');

        $selectedAgent = null;
        if ($filters['id_agent']) {
            $selectedAgent = Agent::query()
                ->whereNull('date_suppression')
                ->find($filters['id_agent']);
        }

        $searchDisplay = $selectedAgent
            ? trim(($selectedAgent->numero_agent ? $selectedAgent->numero_agent.' — ' : '').$selectedAgent->full_name)
            : $filters['search'];

        return view('agents.index', compact(
            'agents',
            'groupes',
            'filters',
            'hasFilters',
            'selectedAgent',
            'searchDisplay',
        ))->with('sousGroupes', Agent::sousGroupes());
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'in:agent,contact,groupe'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        if ($term === '') {
            return response()->json([]);
        }

        $like = '%'.$term.'%';

        if ($validated['field'] === 'agent') {
            $agents = Agent::query()
                ->whereNull('date_suppression')
                ->where(function ($query) use ($like) {
                    $query->where('nom', 'like', $like)
                        ->orWhere('prenom', 'like', $like)
                        ->orWhere('numero_agent', 'like', $like)
                        ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", [$like]);
                })
                ->orderBy('nom')
                ->orderBy('prenom')
                ->limit(10)
                ->get()
                ->map(fn (Agent $agent) => [
                    'id' => $agent->id_agent,
                    'label' => trim(($agent->numero_agent ? $agent->numero_agent.' — ' : '').$agent->full_name),
                    'numero' => $agent->numero_agent ?? '',
                    'name' => $agent->full_name,
                ])
                ->values();

            return response()->json($agents);
        }

        $suggestions = match ($validated['field']) {
            'contact' => Agent::query()
                ->whereNull('date_suppression')
                ->where('contact', 'like', $like)
                ->distinct()
                ->orderBy('contact')
                ->limit(10)
                ->pluck('contact'),
            'groupe' => Groupe::query()
                ->where(function ($query) use ($like) {
                    $query->where('nom', 'like', $like)
                        ->orWhere('prenoms', 'like', $like)
                        ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$like]);
                })
                ->orderBy('nom')
                ->orderBy('prenoms')
                ->limit(10)
                ->get()
                ->map(fn (Groupe $groupe) => $groupe->full_name)
                ->unique()
                ->values(),
        };

        return response()->json($suggestions->values()->all());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'id_chef' => ['required', 'integer', 'exists:chef_equipe,id_chef'],
            'sous_groupe' => ['required', Rule::in([Agent::SOUS_GROUPE_PARTICULIER, Agent::SOUS_GROUPE_PROFESSIONNEL])],
        ]);

        $nom = $this->agentService->formatPersonName($validated['nom']);
        $prenom = $this->agentService->formatPersonName($validated['prenom']);

        $numeroAgent = $this->agentService->generateNumeroAgent(
            (int) $validated['id_chef'],
            $nom,
            $prenom,
        );

        Agent::query()->create([
            'numero_agent' => $numeroAgent,
            'nom' => $nom,
            'prenom' => $prenom,
            'contact' => trim($validated['contact']),
            'id_chef' => $validated['id_chef'],
            'sous_groupe' => $validated['sous_groupe'],
            'cree_par' => auth()->id(),
            'code_pin' => $this->agentService->generatePin(),
            'avatar' => 'agents.png',
        ]);

        return redirect()
            ->route('agents.index')
            ->with('success', "Agent enregistré avec succès. Numéro : {$numeroAgent}");
    }

    public function show(Agent $agent): View
    {
        if ($agent->date_suppression !== null) {
            abort(404);
        }

        $agent->load(['groupe', 'createur']);

        $stats = [
            'tickets' => $agent->tickets()->count(),
            'bordereaux' => $agent->bordereaux()->count(),
            'ponts' => $agent->ponts()->count(),
        ];

        $ponts = $agent->ponts()
            ->with(['typePont', 'commis'])
            ->orderBy('code_pont')
            ->get();

        $commis = $agent->commis()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        $availablePonts = PontBascule::query()
            ->with(['typePont', 'agent'])
            ->where(function ($query) use ($agent) {
                $query->whereNull('id_agent')
                    ->orWhere('id_agent', '!=', $agent->id_agent);
            })
            ->orderBy('code_pont')
            ->get();

        $groupes = Groupe::query()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        $documents = $this->documentService->documentsByType($agent);
        $minioConfigured = app(MinioStorageService::class)->isConfigured();

        return view('agents.show', compact('agent', 'stats', 'groupes', 'ponts', 'commis', 'availablePonts', 'documents', 'minioConfigured'))
            ->with('sousGroupes', Agent::sousGroupes());
    }

    public function associatePont(Request $request, Agent $agent): RedirectResponse
    {
        if ($agent->date_suppression !== null) {
            abort(404);
        }

        $validated = $request->validate([
            'pont_id' => ['required', 'integer', 'exists:pont_bascule,id_pont'],
        ]);

        $pont = PontBascule::query()->findOrFail((int) $validated['pont_id']);

        if ((int) $pont->id_agent === (int) $agent->id_agent) {
            return back()->withErrors([
                'pont_id' => 'Ce pont est déjà associé à cet agent.',
            ]);
        }

        $pont->update([
            'id_agent' => $agent->id_agent,
            'gerant' => $agent->full_name,
        ]);

        return redirect()
            ->route('agents.show', $agent)
            ->with('success', "Pont « {$pont->code_pont} — {$pont->nom_pont} » associé à l'agent avec succès.");
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        if ($agent->date_suppression !== null) {
            abort(404);
        }

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'id_chef' => ['required', 'integer', 'exists:chef_equipe,id_chef'],
            'sous_groupe' => ['required', Rule::in([Agent::SOUS_GROUPE_PARTICULIER, Agent::SOUS_GROUPE_PROFESSIONNEL])],
            'code_pin' => ['nullable', 'digits:6'],
        ]);

        $nom = $this->agentService->formatPersonName($validated['nom']);
        $prenom = $this->agentService->formatPersonName($validated['prenom']);

        $data = [
            'nom' => $nom,
            'prenom' => $prenom,
            'contact' => trim($validated['contact']),
            'id_chef' => $validated['id_chef'],
            'sous_groupe' => $validated['sous_groupe'],
            'date_modification' => now(),
        ];

        if (! empty($validated['code_pin'])) {
            $data['code_pin'] = $validated['code_pin'];
        }

        $agent->update($data);

        return redirect()
            ->route('agents.show', $agent)
            ->with('success', 'Informations de l\'agent mises à jour avec succès.');
    }

    public function inlineUpdate(Request $request, Agent $agent): JsonResponse
    {
        if ($agent->date_suppression !== null) {
            abort(404);
        }

        $validated = $request->validate([
            'field' => ['required', 'in:nom,prenom,contact'],
            'value' => ['required', 'string', 'max:255'],
        ]);

        $field = $validated['field'];
        $value = trim($validated['value']);

        if (in_array($field, ['nom', 'prenom'], true)) {
            $value = $this->agentService->formatPersonName($value);
        }

        $agent->update([
            $field => $value,
            'date_modification' => now(),
        ]);

        return response()->json([
            'success' => true,
            'field' => $field,
            'value' => $agent->{$field},
            'display' => $agent->{$field},
        ]);
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        if ($agent->date_suppression !== null) {
            abort(404);
        }

        $agent->update([
            'date_modification' => now(),
            'date_suppression' => now(),
        ]);

        return redirect()
            ->route('agents.index')
            ->with('success', "Agent {$agent->full_name} supprimé avec succès.");
    }
}
