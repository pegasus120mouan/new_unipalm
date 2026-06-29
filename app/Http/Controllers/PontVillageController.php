<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Region;
use App\Models\SousPrefecture;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PontVillageController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sousPrefectureId = $request->query('sous_prefecture_id') ? (int) $request->query('sous_prefecture_id') : null;

        $query = Village::query()
            ->with(['sousPrefecture.departement.region'])
            ->orderBy('nom');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhereHas('sousPrefecture', function ($spQuery) use ($term) {
                        $spQuery->where('nom', 'like', $term)
                            ->orWhere('code', 'like', $term)
                            ->orWhereHas('departement', function ($depQuery) use ($term) {
                                $depQuery->where('nom', 'like', $term)
                                    ->orWhere('code', 'like', $term)
                                    ->orWhereHas('region', function ($regionQuery) use ($term) {
                                        $regionQuery->where('nom', 'like', $term)
                                            ->orWhere('code', 'like', $term);
                                    });
                            });
                    });
            });
        }

        if ($sousPrefectureId) {
            $query->where('sous_prefecture_id', $sousPrefectureId);
        }

        $villages = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Village::query()->count(),
            'with_coordinates' => Village::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where(function ($q) {
                    $q->where('latitude', '!=', 0)
                        ->orWhere('longitude', '!=', 0);
                })
                ->count(),
        ];

        $regions = Region::query()->orderBy('nom')->get(['id', 'code', 'nom']);

        return view('ponts.villages.index', compact(
            'villages',
            'search',
            'sousPrefectureId',
            'stats',
            'regions',
        ));
    }

    public function departementsForRegion(Region $region): JsonResponse
    {
        $departements = $region->departements()
            ->orderBy('nom')
            ->get(['id', 'code', 'nom'])
            ->map(fn (Departement $departement) => [
                'id' => $departement->id,
                'code' => $departement->code,
                'nom' => $departement->nom,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $departements,
        ]);
    }

    public function sousPrefecturesForDepartement(Departement $departement): JsonResponse
    {
        $sousPrefectures = $departement->sousPrefectures()
            ->orderBy('nom')
            ->get(['id', 'code', 'nom'])
            ->map(fn (SousPrefecture $sousPrefecture) => [
                'id' => $sousPrefecture->id,
                'code' => $sousPrefecture->code,
                'nom' => $sousPrefecture->nom,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $sousPrefectures,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVillage($request);

        Village::query()->create([
            'sous_prefecture_id' => $validated['sous_prefecture_id'],
            'nom' => trim($validated['nom']),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return redirect()
            ->route('ponts.villages.index')
            ->with('success', 'Village enregistré avec succès.');
    }

    public function update(Request $request, Village $village): RedirectResponse
    {
        $validated = $this->validateVillage($request, $village);

        $village->update([
            'sous_prefecture_id' => $validated['sous_prefecture_id'],
            'nom' => trim($validated['nom']),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return redirect()
            ->route('ponts.villages.index')
            ->with('success', 'Village modifié avec succès.');
    }

    public function destroy(Village $village): RedirectResponse
    {
        $label = $village->nom;
        $village->delete();

        return redirect()
            ->route('ponts.villages.index')
            ->with('success', "Village « {$label} » supprimé avec succès.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVillage(Request $request, ?Village $village = null): array
    {
        $uniqueRule = Rule::unique('villages', 'nom')
            ->where(fn ($query) => $query->where('sous_prefecture_id', $request->input('sous_prefecture_id')));

        if ($village !== null) {
            $uniqueRule = $uniqueRule->ignore($village->id, 'id');
        }

        return $request->validate([
            'id_region' => ['required', 'integer', Rule::exists('regions', 'id')],
            'id_departement' => [
                'required',
                'integer',
                Rule::exists('departements', 'id')->where(fn ($query) => $query->where('region_id', $request->input('id_region'))),
            ],
            'sous_prefecture_id' => [
                'required',
                'integer',
                Rule::exists('sous_prefectures', 'id')->where(fn ($query) => $query->where('departement_id', $request->input('id_departement'))),
            ],
            'nom' => ['required', 'string', 'max:200', $uniqueRule],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }
}
