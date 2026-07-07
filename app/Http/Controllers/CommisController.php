<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Commis;
use App\Services\CommisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommisController extends Controller
{
    public function __construct(
        private readonly CommisService $commisService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'id_agent' => $request->query('id_agent') ? (int) $request->query('id_agent') : null,
            'search_contact' => trim((string) $request->query('search_contact', '')),
        ];

        $query = Commis::query()
            ->with(['agent.groupe', 'pont', 'createur'])
            ->whereNull('date_suppression');

        if ($filters['id_agent']) {
            $query->where('id_agent', $filters['id_agent']);
        } elseif ($filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('prenoms', 'like', $term)
                    ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$term]);
            });
        }

        if ($filters['search_contact'] !== '') {
            $query->where('contact', 'like', '%'.$filters['search_contact'].'%');
        }

        $commis = $query
            ->orderByDesc('date_ajout')
            ->orderByDesc('id_commis')
            ->paginate(15)
            ->withQueryString();

        $hasFilters = collect($filters)->contains(fn ($value) => $value !== null && $value !== '');

        $filterAgentLabel = '';
        if ($filters['id_agent']) {
            $filterAgent = Agent::query()
                ->whereNull('date_suppression')
                ->find($filters['id_agent']);
            if ($filterAgent) {
                $filterAgentLabel = trim(($filterAgent->numero_agent ? $filterAgent->numero_agent.' — ' : '').$filterAgent->full_name);
            }
        }

        $oldAgentLabel = '';
        if (old('id_agent')) {
            $oldAgent = Agent::query()
                ->whereNull('date_suppression')
                ->find((int) old('id_agent'));
            if ($oldAgent) {
                $oldAgentLabel = trim(($oldAgent->numero_agent ? $oldAgent->numero_agent.' — ' : '').$oldAgent->full_name);
            }
        }

        return view('commis.index', compact(
            'commis',
            'filters',
            'hasFilters',
            'filterAgentLabel',
            'oldAgentLabel',
        ));
    }

    public function pontsForAgent(Request $request, Agent $agent): JsonResponse
    {
        if ($agent->date_suppression !== null) {
            return response()->json([], 404);
        }

        $excludeCommisId = $request->query('exclude_commis')
            ? (int) $request->query('exclude_commis')
            : null;

        return response()->json(
            $this->commisService->pontsDisponiblesPourAgent($agent->id_agent, $excludeCommisId)
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenoms' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'id_agent' => ['required', 'integer', Rule::exists('agents', 'id_agent')->whereNull('date_suppression')],
            'id_pont' => [
                'required',
                'integer',
                Rule::exists('pont_bascule', 'id_pont'),
                Rule::unique('commis', 'id_pont')->whereNull('date_suppression'),
            ],
        ]);

        $this->commisService->assertPontAppartientAgent(
            (int) $validated['id_pont'],
            (int) $validated['id_agent']
        );

        $commis = Commis::query()->create([
            'nom' => $this->commisService->formatPersonName($validated['nom']),
            'prenoms' => $this->commisService->formatPersonName($validated['prenoms']),
            'contact' => trim($validated['contact']),
            'code_pin' => $this->commisService->generateUniquePin(),
            'id_agent' => $validated['id_agent'],
            'id_pont' => $validated['id_pont'],
            'cree_par' => auth()->id(),
        ]);

        return redirect()
            ->route('commis.index')
            ->with('success', 'Commis enregistré avec succès. Code PIN : '.$commis->code_pin);
    }

    public function update(Request $request, Commis $commis): RedirectResponse
    {
        if ($commis->date_suppression !== null) {
            abort(404);
        }

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenoms' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'id_agent' => ['required', 'integer', Rule::exists('agents', 'id_agent')->whereNull('date_suppression')],
            'id_pont' => [
                'required',
                'integer',
                Rule::exists('pont_bascule', 'id_pont'),
                Rule::unique('commis', 'id_pont')
                    ->ignore($commis->id_commis, 'id_commis')
                    ->whereNull('date_suppression'),
            ],
        ]);

        $this->commisService->assertPontAppartientAgent(
            (int) $validated['id_pont'],
            (int) $validated['id_agent']
        );

        $commis->update([
            'nom' => $this->commisService->formatPersonName($validated['nom']),
            'prenoms' => $this->commisService->formatPersonName($validated['prenoms']),
            'contact' => trim($validated['contact']),
            'id_agent' => $validated['id_agent'],
            'id_pont' => $validated['id_pont'],
            'date_modification' => now(),
        ]);

        return redirect()
            ->route('commis.index')
            ->with('success', 'Commis mis à jour avec succès.');
    }

    public function destroy(Commis $commis): RedirectResponse
    {
        if ($commis->date_suppression !== null) {
            abort(404);
        }

        $commis->update([
            'date_suppression' => now(),
            'date_modification' => now(),
        ]);

        return redirect()
            ->route('commis.index')
            ->with('success', 'Commis supprimé avec succès.');
    }
}
