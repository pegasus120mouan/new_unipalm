<?php

namespace App\Services;

use App\Models\Departement;
use App\Models\Region;
use InvalidArgumentException;

class DepartementGeoJsonImporter
{
    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(array $data, string $mode = 'upsert'): array
    {
        $features = $this->extractFeatures($data);

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
                    $errors[] = "Entité #{$line} ignorée : nom du département introuvable.";

                    continue;
                }

                if ($attributes['region_id'] === null) {
                    $skipped++;
                    $errors[] = "Entité #{$line} ignorée : région « {$attributes['region_label']} » introuvable en base.";

                    continue;
                }

                $departement = null;

                if ($mode === 'upsert') {
                    if (filled($attributes['code'])) {
                        $departement = Departement::query()
                            ->where('region_id', $attributes['region_id'])
                            ->where('code', $attributes['code'])
                            ->first();
                    }

                    if ($departement === null) {
                        $departement = Departement::query()
                            ->where('region_id', $attributes['region_id'])
                            ->where('nom', $attributes['nom'])
                            ->first();
                    }
                } elseif ($mode === 'create') {
                    $exists = Departement::query()
                        ->where('region_id', $attributes['region_id'])
                        ->when(filled($attributes['code']), fn ($q) => $q->where('code', $attributes['code']))
                        ->when(blank($attributes['code']), fn ($q) => $q->where('nom', $attributes['nom']))
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        $errors[] = "Entité #{$line} ignorée : « {$attributes['nom']} » existe déjà.";

                        continue;
                    }
                }

                $payload = [
                    'region_id' => $attributes['region_id'],
                    'code' => $attributes['code'],
                    'nom' => $attributes['nom'],
                    'geojson' => $attributes['geojson'],
                ];

                if ($departement !== null) {
                    $departement->update($payload);
                    $updated++;
                } else {
                    Departement::query()->create($payload);
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

        if (isset($data['type']) && in_array($data['type'], ['Polygon', 'MultiPolygon'], true)) {
            return [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => $data,
            ]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $feature
     * @return array{region_id: ?int, region_label: string, code: ?string, nom: string, geojson: string}
     */
    private function featureToAttributes(array $feature): array
    {
        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];

        $nom = $this->pickProperty($properties, [
            'NomDep', 'NOMDEP', 'nomdep',
            'nom', 'name', 'NOM', 'NAME',
            'libelle', 'LIBELLE', 'label', 'LABEL',
        ]);

        $code = $this->pickProperty($properties, [
            'CodDep', 'CODDEP', 'coddep',
            'code', 'CODE', 'code_dep', 'CODE_DEP',
        ]);

        $regionCode = $this->pickProperty($properties, [
            'CodReg', 'CODREG', 'codreg',
            'code_region', 'CODE_REGION',
        ]);

        $regionNom = $this->pickProperty($properties, [
            'NomReg', 'NOMREG', 'nomreg',
            'DR', 'dr',
            'region', 'REGION', 'nom_region', 'NOM_REGION',
        ]);

        $region = $this->resolveRegion($regionCode, $regionNom);
        $regionLabel = $regionNom ?: $regionCode ?: '—';

        if (blank($nom)) {
            $nom = $code ?? '';
        }

        return [
            'region_id' => $region?->id,
            'region_label' => $regionLabel,
            'code' => filled($code) ? mb_substr(trim((string) $code), 0, 10) : null,
            'nom' => mb_substr(trim((string) $nom), 0, 150),
            'geojson' => json_encode($feature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function resolveRegion(?string $code, ?string $nom): ?Region
    {
        if (filled($code)) {
            $trimmedCode = trim($code);
            $normalizedCode = str_pad(ltrim($trimmedCode, '0') ?: '0', 2, '0', STR_PAD_LEFT);

            $region = Region::query()
                ->where(function ($query) use ($trimmedCode, $normalizedCode) {
                    $query->where('code', $trimmedCode)
                        ->orWhere('code', $normalizedCode);
                })
                ->first();

            if ($region !== null) {
                return $region;
            }
        }

        if (filled($nom)) {
            $region = Region::query()
                ->whereRaw('UPPER(nom) = ?', [mb_strtoupper(trim($nom))])
                ->first();

            if ($region !== null) {
                return $region;
            }
        }

        return null;
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
