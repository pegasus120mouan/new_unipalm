<?php

namespace App\Services;

use App\Models\Region;
use InvalidArgumentException;

class RegionGeoJsonImporter
{
    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(array $data, string $mode = 'upsert'): array
    {
        $features = $this->extractFeatures($data);
        $features = $this->groupFeaturesByRegion($features);

        if ($features === []) {
            throw new InvalidArgumentException('Aucune entité géographique trouvée dans le fichier GeoJSON.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($features as $index => $feature) {
            $line = $index + 1;

            try {
                $attributes = $this->featureToAttributes($feature);

                if (blank($attributes['nom'])) {
                    $skipped++;
                    $errors[] = "Entité #{$line} ignorée : nom introuvable dans les propriétés.";

                    continue;
                }

                $region = null;

                if ($mode === 'upsert') {
                    if (filled($attributes['code'])) {
                        $region = Region::query()->where('code', $attributes['code'])->first();
                    }

                    if ($region === null) {
                        $region = Region::query()->where('nom', $attributes['nom'])->first();
                    }
                } elseif ($mode === 'create') {
                    $exists = Region::query()
                        ->when(filled($attributes['code']), fn ($q) => $q->where('code', $attributes['code']))
                        ->when(blank($attributes['code']), fn ($q) => $q->where('nom', $attributes['nom']))
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        $errors[] = "Entité #{$line} ignorée : « {$attributes['nom']} » existe déjà.";

                        continue;
                    }
                }

                if ($region !== null) {
                    $region->update($attributes);
                    $updated++;
                } else {
                    Region::query()->create($attributes);
                    $created++;
                }
            } catch (\Throwable $exception) {
                $skipped++;
                $errors[] = "Entité #{$line} : ".$exception->getMessage();
            }
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractFeatures(array $data): array
    {
        $type = $data['type'] ?? null;

        if ($type === 'FeatureCollection') {
            return array_values(array_filter(
                $data['features'] ?? [],
                fn ($feature) => is_array($feature) && ($feature['type'] ?? null) === 'Feature'
            ));
        }

        if ($type === 'Feature') {
            return [$data];
        }

        if (isset($data['type']) && in_array($data['type'], ['Polygon', 'MultiPolygon', 'LineString', 'MultiLineString', 'Point', 'MultiPoint'], true)) {
            return [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => $data,
            ]];
        }

        return [];
    }

    /**
     * Regroupe les entités (ex. districts) par code/nom de région et fusionne les géométries.
     *
     * @param  list<array<string, mixed>>  $features
     * @return list<array<string, mixed>>
     */
    private function groupFeaturesByRegion(array $features): array
    {
        $groups = [];

        foreach ($features as $feature) {
            $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];

            $code = $this->pickProperty($properties, [
                'CodReg', 'CODREG', 'codreg', 'code', 'CODE', 'code_region',
            ]);

            $nom = $this->pickProperty($properties, [
                'NomReg', 'NOMREG', 'nomreg', 'nom', 'name', 'NOM', 'NAME', 'region', 'REGION',
            ]);

            $groupKey = filled($code) ? 'code:'.$code : (filled($nom) ? 'nom:'.$nom : null);

            if ($groupKey === null) {
                $geometries = [];
                if (isset($feature['geometry']) && is_array($feature['geometry'])) {
                    $geometries[] = $feature['geometry'];
                }

                $groups[] = [
                    'feature' => $feature,
                    'code' => null,
                    'nom' => $this->pickProperty($properties, [
                        'NomDistric', 'NomReg', 'nom', 'name',
                    ]),
                    'geometries' => $geometries,
                ];

                continue;
            }

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'feature' => $feature,
                    'code' => $code,
                    'nom' => $nom,
                    'geometries' => [],
                ];
            }

            if (isset($feature['geometry']) && is_array($feature['geometry'])) {
                $groups[$groupKey]['geometries'][] = $feature['geometry'];
            }

            if (filled($nom)) {
                $groups[$groupKey]['nom'] = $nom;
            }
        }

        $merged = [];

        foreach ($groups as $group) {
            $feature = $group['feature'];
            $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];

            if (filled($group['code'])) {
                $properties['CodReg'] = $group['code'];
            }

            if (filled($group['nom'])) {
                $properties['NomReg'] = $group['nom'];
            }

            $merged[] = [
                'type' => 'Feature',
                'properties' => $properties,
                'geometry' => $this->mergeGeometries($group['geometries']),
            ];
        }

        return $merged;
    }

    /**
     * @param  list<array<string, mixed>>  $geometries
     * @return array<string, mixed>
     */
    private function mergeGeometries(array $geometries): array
    {
        $polygons = [];

        foreach ($geometries as $geometry) {
            $polygons = array_merge($polygons, $this->extractPolygonCoordinates($geometry));
        }

        if ($polygons === []) {
            return ['type' => 'MultiPolygon', 'coordinates' => []];
        }

        if (count($polygons) === 1) {
            return ['type' => 'Polygon', 'coordinates' => $polygons[0]];
        }

        return ['type' => 'MultiPolygon', 'coordinates' => $polygons];
    }

    /**
     * @param  array<string, mixed>  $geometry
     * @return list<mixed>
     */
    private function extractPolygonCoordinates(array $geometry): array
    {
        $type = $geometry['type'] ?? null;

        if ($type === 'Polygon' && isset($geometry['coordinates'])) {
            return [$geometry['coordinates']];
        }

        if ($type === 'MultiPolygon' && isset($geometry['coordinates']) && is_array($geometry['coordinates'])) {
            return $geometry['coordinates'];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $feature
     * @return array{code: ?string, nom: string, geojson: string}
     */
    private function featureToAttributes(array $feature): array
    {
        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];

        $nom = $this->pickProperty($properties, [
            'NomReg', 'NOMREG', 'nomreg',
            'NomDistric', 'NOMDISTRIC', 'nomdistric',
            'DR', 'dr',
            'nom', 'name', 'NOM', 'NAME', 'region', 'Region', 'REGION',
            'libelle', 'LIBELLE', 'label', 'LABEL', 'nom_region', 'NOM_REGION',
        ]);

        $code = $this->pickProperty($properties, [
            'CodReg', 'CODREG', 'codreg',
            'CodDistric', 'CODDISTRIC', 'coddistric',
            'code', 'CODE', 'codereg', 'CODE_REG', 'code_region', 'id_region',
        ]);

        if (blank($code) && isset($properties['id']) && is_scalar($properties['id'])) {
            $codeCandidate = trim((string) $properties['id']);
            if (strlen($codeCandidate) <= 10) {
                $code = $codeCandidate;
            }
        }

        if (blank($nom)) {
            $nom = $code ?? '';
        }

        return [
            'code' => filled($code) ? mb_substr(trim((string) $code), 0, 10) : null,
            'nom' => mb_substr(trim((string) $nom), 0, 100),
            'geojson' => json_encode($feature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  list<string>  $keys
     */
    private function pickProperty(array $properties, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->readProperty($properties, $key);
            if ($value !== null) {
                return $value;
            }
        }

        $normalized = [];
        foreach ($properties as $propertyKey => $propertyValue) {
            if (is_string($propertyKey)) {
                $normalized[strtolower($propertyKey)] = $propertyKey;
            }
        }

        foreach ($keys as $key) {
            $actualKey = $normalized[strtolower($key)] ?? null;
            if ($actualKey === null) {
                continue;
            }

            $value = $this->readProperty($properties, $actualKey);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function readProperty(array $properties, string $key): ?string
    {
        if (! array_key_exists($key, $properties)) {
            return null;
        }

        $value = $properties[$key];

        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        return null;
    }
}
