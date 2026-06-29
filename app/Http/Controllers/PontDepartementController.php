<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ValidatesGeoJsonFile;
use App\Models\Departement;
use App\Services\DepartementGeoJsonImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PontDepartementController extends Controller
{
    use ValidatesGeoJsonFile;
    public function __construct(
        private readonly DepartementGeoJsonImporter $geoJsonImporter,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $regionId = $request->query('region_id') ? (int) $request->query('region_id') : null;

        $query = Departement::query()
            ->with('region')
            ->withCount('sousPrefectures')
            ->orderBy('nom');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhereHas('region', function ($regionQuery) use ($term) {
                        $regionQuery->where('nom', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    });
            });
        }

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        $departements = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Departement::query()->count(),
            'with_geojson' => Departement::query()
                ->whereNotNull('geojson')
                ->where('geojson', '!=', '')
                ->count(),
        ];

        $hasMapDepartements = $stats['with_geojson'] > 0;

        return view('ponts.departements.index', compact(
            'departements',
            'search',
            'regionId',
            'stats',
            'hasMapDepartements',
        ));
    }

    public function mapData(): JsonResponse
    {
        $departements = Departement::query()
            ->with('region:id,nom')
            ->whereNotNull('geojson')
            ->where('geojson', '!=', '')
            ->orderBy('nom')
            ->get(['id', 'region_id', 'nom', 'geojson']);

        return response()->json([
            'success' => true,
            'data' => $departements->map(fn (Departement $departement) => [
                'id' => $departement->id,
                'nom' => $departement->nom,
                'region' => $departement->region?->nom,
                'geojson' => $departement->geojson,
            ])->values(),
        ]);
    }

    public function show(Departement $departement): JsonResponse
    {
        $departement->load('region');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $departement->id,
                'code' => $departement->code,
                'nom' => $departement->nom,
                'region_id' => $departement->region_id,
                'region' => $departement->region?->nom,
                'geojson' => $departement->geojson,
            ],
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $import = $this->validateGeoJsonImport($request);
        $data = $this->decodeGeoJsonFile($import['file']);

        try {
            $result = $this->geoJsonImporter->import($data, $import['mode']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'geojson_file' => $exception->getMessage(),
            ]);
        }

        $message = sprintf(
            'Import départements terminé : %d créé(s), %d mis à jour, %d ignoré(s).',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        );

        if ($result['errors'] !== []) {
            session()->flash('import_warnings', array_slice($result['errors'], 0, 10));
        }

        return redirect()
            ->route('ponts.departements.index')
            ->with('success', $message);
    }
}
