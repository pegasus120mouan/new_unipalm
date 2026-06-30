<?php

namespace App\Services;

use App\Models\PontBascule;
use App\Models\Region;
use App\Models\Usine;

class UsineLocationService
{
    public function __construct(
        private readonly GeoDistanceService $distanceService,
        private readonly GeoJsonService $geoJsonService,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function buildLocationPayload(Usine $usine): ?array
    {
        if (! $usine->hasCoordinates()) {
            return null;
        }

        $lat = (float) $usine->latitude;
        $lng = (float) $usine->longitude;
        $region = $this->resolveRegion($lat, $lng);

        $pontsQuery = PontBascule::query()
            ->with('region')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($query) {
                $query->where('latitude', '!=', 0)
                    ->orWhere('longitude', '!=', 0);
            });

        if ($region) {
            $pontsQuery->where('id_region', $region->id);
        }

        $ponts = $pontsQuery
            ->get()
            ->map(function (PontBascule $pont) use ($lat, $lng) {
                $distanceKm = $this->distanceService->haversineKm(
                    $lat,
                    $lng,
                    (float) $pont->latitude,
                    (float) $pont->longitude,
                );

                return [
                    'id_pont' => $pont->id_pont,
                    'code_pont' => $pont->code_pont,
                    'nom_pont' => $pont->nom_pont,
                    'latitude' => (float) $pont->latitude,
                    'longitude' => (float) $pont->longitude,
                    'statut' => $pont->statut,
                    'region' => $pont->region?->nom,
                    'gerant' => $pont->gerantLabel(),
                    'distance_km' => round($distanceKm, 2),
                    'distance_label' => $this->distanceService->format($distanceKm),
                ];
            })
            ->sortBy('distance_km')
            ->values()
            ->all();

        $departements = [];
        if ($region) {
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
                ->values()
                ->all();
        }

        return [
            'usine' => [
                'id_usine' => $usine->id_usine,
                'nom_usine' => $usine->nom_usine,
                'latitude' => $lat,
                'longitude' => $lng,
            ],
            'region' => $region ? [
                'id' => $region->id,
                'nom' => $region->nom,
                'geojson' => $region->geojson,
            ] : null,
            'departements' => $departements,
            'ponts' => $ponts,
            'ponts_count' => count($ponts),
        ];
    }

    private function resolveRegion(float $lat, float $lng): ?Region
    {
        $regions = Region::query()
            ->whereNotNull('geojson')
            ->where('geojson', '!=', '')
            ->get();

        foreach ($regions as $region) {
            if ($this->geoJsonService->containsPoint($region->geojson, $lat, $lng)) {
                return $region;
            }
        }

        $nearestPont = PontBascule::query()
            ->with('region')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($query) {
                $query->where('latitude', '!=', 0)
                    ->orWhere('longitude', '!=', 0);
            })
            ->get()
            ->sortBy(fn (PontBascule $pont) => $this->distanceService->haversineKm(
                $lat,
                $lng,
                (float) $pont->latitude,
                (float) $pont->longitude,
            ))
            ->first();

        return $nearestPont?->region;
    }
}
