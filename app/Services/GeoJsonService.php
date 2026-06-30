<?php

namespace App\Services;

class GeoJsonService
{
    public function containsPoint(?string $geojson, float $lat, float $lng): bool
    {
        if ($geojson === null || trim($geojson) === '') {
            return false;
        }

        $data = json_decode($geojson, true);
        if (! is_array($data)) {
            return false;
        }

        return $this->geometryContainsPoint($data, $lat, $lng);
    }

    /**
     * @param  array<string, mixed>  $geometry
     */
    private function geometryContainsPoint(array $geometry, float $lat, float $lng): bool
    {
        $type = $geometry['type'] ?? null;

        return match ($type) {
            'Feature' => $this->geometryContainsPoint($geometry['geometry'] ?? [], $lat, $lng),
            'FeatureCollection' => collect($geometry['features'] ?? [])
                ->contains(fn ($feature) => is_array($feature) && $this->geometryContainsPoint($feature, $lat, $lng)),
            'Polygon' => $this->polygonContainsPoint($geometry['coordinates'] ?? [], $lat, $lng),
            'MultiPolygon' => collect($geometry['coordinates'] ?? [])
                ->contains(fn ($polygon) => $this->polygonContainsPoint($polygon, $lat, $lng)),
            default => false,
        };
    }

    /**
     * @param  array<int, mixed>  $rings
     */
    private function polygonContainsPoint(array $rings, float $lat, float $lng): bool
    {
        if ($rings === []) {
            return false;
        }

        if (! $this->ringContainsPoint($rings[0], $lat, $lng)) {
            return false;
        }

        for ($i = 1, $count = count($rings); $i < $count; $i++) {
            if ($this->ringContainsPoint($rings[$i], $lat, $lng)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    private function ringContainsPoint(array $ring, float $lat, float $lng): bool
    {
        $inside = false;
        $count = count($ring);

        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $ring[$i][1];
            $yi = (float) $ring[$i][0];
            $xj = (float) $ring[$j][1];
            $yj = (float) $ring[$j][0];

            $intersects = (($yi > $lng) !== ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
