<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\PontBascule;
use App\Models\Region;
use App\Models\TypePont;
use App\Services\PontBasculeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PontBasculeController extends Controller
{
    public function __construct(
        private readonly PontBasculeService $pontService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'statut' => trim((string) $request->query('statut', '')),
            'cooperatif' => trim((string) $request->query('cooperatif', '')),
        ];

        $query = PontBascule::query()
            ->with(['typePont', 'agent', 'region'])
            ->orderBy('code_pont');

        if ($filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('code_pont', 'like', $term)
                    ->orWhere('nom_pont', 'like', $term)
                    ->orWhere('gerant', 'like', $term)
                    ->orWhere('cooperatif', 'like', $term)
                    ->orWhereHas('agent', function ($agentQuery) use ($term) {
                        $agentQuery->where('nom', 'like', $term)
                            ->orWhere('prenom', 'like', $term)
                            ->orWhere('numero_agent', 'like', $term);
                    });
            });
        }

        if (in_array($filters['statut'], ['Actif', 'Inactif'], true)) {
            $query->where('statut', $filters['statut']);
        }

        if ($filters['cooperatif'] !== '') {
            $query->where('cooperatif', $filters['cooperatif']);
        }

        $ponts = $query->paginate(15)->withQueryString();
        $stats = $this->pontService->stats();
        $cooperatives = PontBascule::query()
            ->whereNotNull('cooperatif')
            ->where('cooperatif', '!=', '')
            ->distinct()
            ->orderBy('cooperatif')
            ->pluck('cooperatif');

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $agentsForAutocomplete = $agents->map(fn (Agent $agent) => [
            'id' => $agent->id_agent,
            'numero' => $agent->numero_agent ?? '',
            'name' => $agent->full_name,
        ])->values();

        $hasFilters = collect($filters)->contains(fn ($value) => $value !== '');

        $typesPont = TypePont::query()->orderBy('libelle')->get();
        $regions = Region::query()->orderBy('nom')->get(['id', 'code', 'nom']);

        return view('ponts.index', compact(
            'ponts',
            'filters',
            'stats',
            'cooperatives',
            'agents',
            'agentsForAutocomplete',
            'hasFilters',
            'typesPont',
            'regions',
        ));
    }

    public function location(): View
    {
        $ponts = PontBascule::query()
            ->with(['agent', 'region'])
            ->orderBy('code_pont')
            ->get()
            ->map(fn (PontBascule $pont) => [
                'id_pont' => $pont->id_pont,
                'code_pont' => $pont->code_pont,
                'nom_pont' => $pont->nom_pont,
                'latitude' => $pont->latitude,
                'longitude' => $pont->longitude,
                'gerant' => $pont->gerantLabel(),
                'cooperatif' => $pont->cooperatif,
                'statut' => $pont->statut,
                'id_region' => $pont->id_region,
                'region' => $pont->region?->nom,
                'has_coordinates' => $pont->hasCoordinates(),
            ]);

        $stats = [
            'total' => $ponts->count(),
            'actifs' => $ponts->where('statut', 'Actif')->count(),
            'inactifs' => $ponts->where('statut', 'Inactif')->count(),
            'geolocalises' => $ponts->where('has_coordinates', true)->count(),
        ];

        $regions = Region::query()
            ->orderBy('nom')
            ->get(['id', 'nom']);

        return view('ponts.location', compact('ponts', 'stats', 'regions'));
    }

    public function locationRegion(Region $region): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $region->id,
                'nom' => $region->nom,
                'geojson' => $region->geojson,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom_pont' => ['required', 'string', 'max:255'],
            'id_type_pont' => ['nullable', 'integer', Rule::exists('types_pont', 'id_type_pont')],
            'id_region' => ['required', 'integer', Rule::exists('regions', 'id')],
            'id_agent' => ['required', 'integer', Rule::exists('agents', 'id_agent')],
            'cooperatif' => ['nullable', 'string', 'max:100'],
            'statut' => ['required', Rule::in(['Actif', 'Inactif'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $pont = $this->pontService->create($validated);

        return redirect()
            ->route('ponts.index')
            ->with('success', 'Pont-bascule enregistré avec succès. Code généré : '.$pont->code_pont);
    }

    public function update(Request $request, PontBascule $pont): RedirectResponse
    {
        $validated = $request->validate([
            'code_pont' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pont_bascule', 'code_pont')->ignore($pont->id_pont, 'id_pont'),
            ],
            'nom_pont' => ['required', 'string', 'max:255'],
            'id_type_pont' => ['nullable', 'integer', Rule::exists('types_pont', 'id_type_pont')],
            'id_region' => ['required', 'integer', Rule::exists('regions', 'id')],
            'id_agent' => ['nullable', 'integer', Rule::exists('agents', 'id_agent')],
            'cooperatif' => ['nullable', 'string', 'max:100'],
            'statut' => ['required', Rule::in(['Actif', 'Inactif'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $this->pontService->update($pont, $validated);

        return redirect()
            ->route('ponts.index')
            ->with('success', 'Pont-bascule modifié avec succès.');
    }

    public function destroy(PontBascule $pont): RedirectResponse
    {
        $label = $pont->code_pont;
        $pont->delete();

        return redirect()
            ->route('ponts.index')
            ->with('success', "Pont-bascule {$label} supprimé avec succès.");
    }
}
