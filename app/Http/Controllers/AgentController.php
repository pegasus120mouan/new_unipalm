<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Groupe;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentService $agentService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search_nom' => trim((string) $request->query('search_nom', '')),
            'search_prenom' => trim((string) $request->query('search_prenom', '')),
            'search_contact' => trim((string) $request->query('search_contact', '')),
            'search_groupe' => trim((string) $request->query('search_groupe', '')),
        ];

        $agentsQuery = Agent::query()
            ->with(['groupe', 'createur'])
            ->whereNull('date_suppression');

        if ($filters['search_nom'] !== '') {
            $agentsQuery->where('nom', 'like', '%'.$filters['search_nom'].'%');
        }

        if ($filters['search_prenom'] !== '') {
            $agentsQuery->where('prenom', 'like', '%'.$filters['search_prenom'].'%');
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

        $agents = $agentsQuery
            ->orderByDesc('date_ajout')
            ->orderByDesc('id_agent')
            ->paginate(15)
            ->withQueryString();

        $groupes = Groupe::query()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        $hasFilters = collect($filters)->contains(fn ($value) => $value !== '');

        return view('agents.index', compact('agents', 'groupes', 'filters', 'hasFilters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'id_chef' => ['required', 'integer', 'exists:chef_equipe,id_chef'],
        ]);

        $numeroAgent = $this->agentService->generateNumeroAgent(
            (int) $validated['id_chef'],
            $validated['nom'],
            $validated['prenom'],
        );

        Agent::query()->create([
            'numero_agent' => $numeroAgent,
            'nom' => trim($validated['nom']),
            'prenom' => trim($validated['prenom']),
            'contact' => trim($validated['contact']),
            'id_chef' => $validated['id_chef'],
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
        ];

        $groupes = Groupe::query()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        return view('agents.show', compact('agent', 'stats', 'groupes'));
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
            'code_pin' => ['nullable', 'digits:6'],
        ]);

        $data = [
            'nom' => trim($validated['nom']),
            'prenom' => trim($validated['prenom']),
            'contact' => trim($validated['contact']),
            'id_chef' => $validated['id_chef'],
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

        $agent->update([
            $field => $value,
            'date_modification' => now(),
        ]);

        return response()->json([
            'success' => true,
            'field' => $field,
            'value' => $agent->{$field},
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
