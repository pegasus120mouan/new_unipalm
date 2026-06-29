<?php

namespace App\Http\Controllers;

use App\Models\PontBascule;
use App\Models\Region;
use App\Services\RegionGeoJsonImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class PontRegionController extends Controller
{
    public function __construct(
        private readonly RegionGeoJsonImporter $geoJsonImporter,
    ) {}
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Region::query()
            ->withCount('departements')
            ->orderBy('nom');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('code', 'like', $term);
            });
        }

        $regions = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Region::query()->count(),
            'with_geojson' => Region::query()
                ->whereNotNull('geojson')
                ->where('geojson', '!=', '')
                ->count(),
        ];

        $hasMapRegions = $stats['with_geojson'] > 0;

        return view('ponts.regions.index', compact('regions', 'search', 'stats', 'hasMapRegions'));
    }

    public function mapData(): JsonResponse
    {
        $regions = Region::query()
            ->whereNotNull('geojson')
            ->where('geojson', '!=', '')
            ->orderBy('nom')
            ->get(['id', 'nom', 'geojson']);

        return response()->json([
            'success' => true,
            'data' => $regions->map(fn (Region $region) => [
                'id' => $region->id,
                'nom' => $region->nom,
                'geojson' => $region->geojson,
            ])->values(),
        ]);
    }

    public function show(Region $region): JsonResponse
    {
        $region->load('district');

        $departements = $region->departements()
            ->whereNotNull('geojson')
            ->where('geojson', '!=', '')
            ->orderBy('nom')
            ->get(['id', 'code', 'nom', 'geojson'])
            ->map(fn ($departement) => [
                'id' => $departement->id,
                'code' => $departement->code,
                'nom' => $departement->nom,
                'geojson' => $departement->geojson,
            ])
            ->values();

        $ponts = $region->ponts()
            ->orderBy('nom_pont')
            ->get()
            ->map(fn (PontBascule $pont) => [
                'id_pont' => $pont->id_pont,
                'code_pont' => $pont->code_pont,
                'nom_pont' => trim($pont->nom_pont ?? ''),
                'latitude' => $pont->latitude,
                'longitude' => $pont->longitude,
                'gerant' => $pont->gerantLabel(),
                'cooperatif' => $pont->cooperatif,
                'statut' => $pont->statut,
                'has_coordinates' => $pont->hasCoordinates(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $region->id,
                'code' => $region->code,
                'nom' => $region->nom,
                'district_id' => $region->district_id,
                'district' => $region->district?->nom,
                'geojson' => $region->geojson,
                'departements' => $departements,
                'departements_count' => $departements->count(),
                'ponts' => $ponts,
                'ponts_count' => $ponts->count(),
                'ponts_geolocalises' => $ponts->where('has_coordinates', true)->count(),
            ],
        ]);
    }

    public function pontsMapData(): JsonResponse
    {
        $ponts = PontBascule::query()
            ->whereNotNull('id_region')
            ->orderBy('nom_pont')
            ->get()
            ->map(fn (PontBascule $pont) => [
                'id_pont' => $pont->id_pont,
                'code_pont' => $pont->code_pont,
                'nom_pont' => trim($pont->nom_pont ?? ''),
                'id_region' => $pont->id_region,
                'latitude' => $pont->latitude,
                'longitude' => $pont->longitude,
                'gerant' => $pont->gerantLabel(),
                'statut' => $pont->statut,
                'has_coordinates' => $pont->hasCoordinates(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $ponts,
        ]);
    }

    public function departements(Region $region): View
    {
        $departements = $region->departements()
            ->withCount('sousPrefectures')
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        $departementsForMap = $region->departements()
            ->whereNotNull('geojson')
            ->where('geojson', '!=', '')
            ->orderBy('nom')
            ->get(['id', 'code', 'nom', 'geojson']);

        $stats = [
            'total' => $region->departements()->count(),
            'with_geojson' => $departementsForMap->count(),
        ];

        $hasMapDepartements = $stats['with_geojson'] > 0;

        return view('ponts.regions.departements', compact(
            'region',
            'departements',
            'departementsForMap',
            'stats',
            'hasMapDepartements',
        ));
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'geojson_file' => ['required', 'file', 'max:102400'],
            'mode' => ['nullable', Rule::in(['create', 'upsert'])],
        ]);

        $extension = strtolower($request->file('geojson_file')->getClientOriginalExtension());
        if (! in_array($extension, ['json', 'geojson'], true)) {
            return back()->withErrors([
                'geojson_file' => 'Le fichier doit être au format .json ou .geojson.',
            ]);
        }

        $content = file_get_contents($request->file('geojson_file')->getRealPath());
        $data = json_decode($content, true);

        if (! is_array($data)) {
            return back()->withErrors([
                'geojson_file' => 'Le fichier GeoJSON n\'est pas un JSON valide.',
            ]);
        }

        try {
            $result = $this->geoJsonImporter->import($data, $validated['mode'] ?? 'upsert');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'geojson_file' => $exception->getMessage(),
            ]);
        }

        $message = sprintf(
            'Import terminé : %d créée(s), %d mise(s) à jour, %d ignorée(s).',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        );

        if ($result['errors'] !== []) {
            session()->flash('import_warnings', array_slice($result['errors'], 0, 10));
        }

        return redirect()
            ->route('ponts.regions.index')
            ->with('success', $message);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRegion($request);

        Region::query()->create($validated);

        return redirect()
            ->route('ponts.regions.index')
            ->with('success', 'Région enregistrée avec succès.');
    }

    public function update(Request $request, Region $region): RedirectResponse
    {
        $validated = $this->validateRegion($request, $region);

        $region->update($validated);

        return redirect()
            ->route('ponts.regions.index')
            ->with('success', 'Région modifiée avec succès.');
    }

    public function destroy(Region $region): RedirectResponse
    {
        $label = $region->nom ?? $region->code ?? '#'.$region->id;
        $region->delete();

        return redirect()
            ->route('ponts.regions.index')
            ->with('success', "Région « {$label} » supprimée avec succès.");
    }

    /**
     * @return array{code: ?string, nom: ?string, geojson: ?string}
     */
    private function validateRegion(Request $request, ?Region $region = null): array
    {
        $validated = $request->validate([
            'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
            'code' => [
                'nullable',
                'string',
                'max:10',
                Rule::unique('regions', 'code')->ignore($region?->id),
            ],
            'nom' => ['required', 'string', 'max:150'],
            'geojson' => ['nullable', 'string'],
        ]);

        $geojson = trim((string) ($validated['geojson'] ?? ''));

        if ($geojson !== '') {
            json_decode($geojson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'geojson' => 'Le GeoJSON fourni n\'est pas un JSON valide.',
                ]);
            }
        }

        return [
            'district_id' => blank($validated['district_id'] ?? null) ? null : (int) $validated['district_id'],
            'code' => blank($validated['code'] ?? null) ? null : $validated['code'],
            'nom' => $validated['nom'],
            'geojson' => $geojson !== '' ? $geojson : null,
        ];
    }
}
