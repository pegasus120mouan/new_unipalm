<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Services\GeoJsonService;
use App\Services\PlanteurApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use RuntimeException;

class LocalisationController extends Controller
{
    public function __construct(
        private readonly PlanteurApiService $planteurApi,
        private readonly GeoJsonService $geoJsonService,
    ) {}

    public function index(): View
    {
        $regions = Region::query()
            ->orderBy('nom')
            ->get(['id', 'code', 'nom', 'geojson'])
            ->map(function (Region $region) {
                return [
                    'id' => $region->id,
                    'code' => $region->code,
                    'nom' => $region->nom,
                    'has_geojson' => filled($region->geojson),
                ];
            })
            ->values()
            ->all();

        return view('plantations.localisation.index', [
            'regions' => $regions,
            'loadError' => null,
        ]);
    }

    public function show(Region $region): View
    {
        return view('plantations.localisation.show', [
            'region' => $region,
            'regionId' => $region->id,
            'regionName' => (string) ($region->nom ?: 'Région #'.$region->id),
            'hasGeojson' => filled($region->geojson),
        ]);
    }

    public function mapData(Region $region): JsonResponse
    {
        try {
            $all = $this->fetchAllPlanteurs();
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }

        $targetName = $this->normalizeRegion((string) ($region->nom ?? ''));
        $hasGeojson = filled($region->geojson);

        $inRegion = [];
        foreach ($all as $planteur) {
            if (! is_array($planteur)) {
                continue;
            }

            $lat = $this->parseCoordinate($planteur['exploitation']['latitude'] ?? null);
            $lng = $this->parseCoordinate($planteur['exploitation']['longitude'] ?? null);
            $nameMatch = $targetName !== ''
                && $this->normalizeRegion((string) ($planteur['exploitation']['region'] ?? '')) === $targetName;

            if ($hasGeojson) {
                if ($lat !== null && $lng !== null) {
                    if (! $this->geoJsonService->containsPoint($region->geojson, $lat, $lng)) {
                        continue;
                    }
                } elseif (! $nameMatch) {
                    continue;
                }
            } elseif (! $nameMatch) {
                continue;
            }

            $inRegion[] = $planteur;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'region' => [
                    'id' => $region->id,
                    'code' => $region->code,
                    'nom' => $region->nom,
                    'geojson' => $region->geojson,
                    'has_geojson' => $hasGeojson,
                ],
                'total' => count($inRegion),
                'planteurs' => array_values($inRegion),
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllPlanteurs(): array
    {
        $all = [];
        $page = 1;
        $pageSize = 100;

        do {
            $json = $this->planteurApi->getPlanteurs([
                'page' => $page,
                'limit' => $pageSize,
            ]);

            if (($json['success'] ?? false) !== true) {
                throw new RuntimeException($json['error'] ?? $json['message'] ?? 'Erreur API plantations.');
            }

            $batch = is_array($json['data']['planteurs'] ?? null) ? $json['data']['planteurs'] : [];
            foreach ($batch as $planteur) {
                if (is_array($planteur)) {
                    $all[] = $planteur;
                }
            }

            $totalPages = max(1, (int) ($json['data']['total_pages'] ?? 1));
            $page++;
        } while ($page <= $totalPages);

        return $all;
    }

    private function parseCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = is_numeric($value) ? (float) $value : null;

        return $number !== null && is_finite($number) ? $number : null;
    }

    private function normalizeRegion(string $value): string
    {
        $value = trim($value);

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_D);
            if (is_string($normalized)) {
                $value = preg_replace('/\p{Mn}/u', '', $normalized) ?? $normalized;
            }
        }

        return mb_strtolower($value);
    }
}
