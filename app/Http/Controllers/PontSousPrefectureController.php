<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\SousPrefecture;
use App\Services\SousPrefectureGeoJsonImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class PontSousPrefectureController extends Controller
{
    public function __construct(
        private readonly SousPrefectureGeoJsonImporter $geoJsonImporter,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $departementId = $request->query('departement_id') ? (int) $request->query('departement_id') : null;

        $query = SousPrefecture::query()
            ->with(['departement.region'])
            ->orderBy('nom');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhereHas('departement', function ($departementQuery) use ($term) {
                        $departementQuery->where('nom', 'like', $term)
                            ->orWhere('code', 'like', $term)
                            ->orWhereHas('region', function ($regionQuery) use ($term) {
                                $regionQuery->where('nom', 'like', $term)
                                    ->orWhere('code', 'like', $term);
                            });
                    });
            });
        }

        if ($departementId) {
            $query->where('departement_id', $departementId);
        }

        $sousPrefectures = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => SousPrefecture::query()->count(),
            'with_geojson' => SousPrefecture::query()
                ->whereNotNull('geojson')
                ->where('geojson', '!=', '')
                ->count(),
        ];

        $hasMapSousPrefectures = $stats['with_geojson'] > 0;

        return view('ponts.sous-prefectures.index', compact(
            'sousPrefectures',
            'search',
            'departementId',
            'stats',
            'hasMapSousPrefectures',
        ));
    }

    public function byDepartement(Departement $departement): View
    {
        $departement->load('region');

        $sousPrefectures = $departement->sousPrefectures()
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        $sousPrefecturesForMap = $departement->sousPrefectures()
            ->whereNotNull('geojson')
            ->where('geojson', '!=', '')
            ->orderBy('nom')
            ->get(['id', 'code', 'nom', 'geojson']);

        $stats = [
            'total' => $departement->sousPrefectures()->count(),
            'with_geojson' => $sousPrefecturesForMap->count(),
        ];

        $hasMapSousPrefectures = $stats['with_geojson'] > 0;

        return view('ponts.departements.sous-prefectures', compact(
            'departement',
            'sousPrefectures',
            'sousPrefecturesForMap',
            'stats',
            'hasMapSousPrefectures',
        ));
    }

    public function mapData(): JsonResponse
    {
        $sousPrefectures = SousPrefecture::query()
            ->with(['departement:id,nom,region_id', 'departement.region:id,nom'])
            ->whereNotNull('geojson')
            ->where('geojson', '!=', '')
            ->orderBy('nom')
            ->get(['id', 'departement_id', 'nom', 'geojson']);

        return response()->json([
            'success' => true,
            'data' => $sousPrefectures->map(fn (SousPrefecture $sousPrefecture) => [
                'id' => $sousPrefecture->id,
                'nom' => $sousPrefecture->nom,
                'departement' => $sousPrefecture->departement?->nom,
                'region' => $sousPrefecture->departement?->region?->nom,
                'geojson' => $sousPrefecture->geojson,
            ])->values(),
        ]);
    }

    public function show(SousPrefecture $sousPrefecture): JsonResponse
    {
        $sousPrefecture->load(['departement.region']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $sousPrefecture->id,
                'code' => $sousPrefecture->code,
                'nom' => $sousPrefecture->nom,
                'departement_id' => $sousPrefecture->departement_id,
                'departement' => $sousPrefecture->departement?->nom,
                'region' => $sousPrefecture->departement?->region?->nom,
                'geojson' => $sousPrefecture->geojson,
            ],
        ]);
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
            'Import sous-préfectures terminé : %d créée(s), %d mise(s) à jour, %d ignorée(s).',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        );

        if ($result['errors'] !== []) {
            session()->flash('import_warnings', array_slice($result['errors'], 0, 10));
        }

        return redirect()
            ->route('ponts.sous-prefectures.index')
            ->with('success', $message);
    }
}
