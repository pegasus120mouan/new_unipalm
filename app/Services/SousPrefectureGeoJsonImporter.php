<?php

namespace App\Services;

use App\Models\Departement;
use App\Models\Region;
use App\Models\SousPrefecture;
use InvalidArgumentException;

class SousPrefectureGeoJsonImporter
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
                    $errors[] = "Entité #{$line} ignorée : nom de la sous-préfecture introuvable.";

                    continue;
                }

                if ($attributes['departement_id'] === null) {
                    $skipped++;
                    $errors[] = "Entité #{$line} ignorée : département « {$attributes['departement_label']} » introuvable en base.";

                    continue;
                }

                $sousPrefecture = null;

                if ($mode === 'upsert') {
                    if (filled($attributes['code'])) {
                        $sousPrefecture = SousPrefecture::query()
                            ->where('departement_id', $attributes['departement_id'])
                            ->where('code', $attributes['code'])
                            ->first();
                    }

                    if ($sousPrefecture === null) {
                        $sousPrefecture = SousPrefecture::query()
                            ->where('departement_id', $attributes['departement_id'])
                            ->where('nom', $attributes['nom'])
                            ->first();
                    }
                } elseif ($mode === 'create') {
                    $exists = SousPrefecture::query()
                        ->where('departement_id', $attributes['departement_id'])
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
                    'departement_id' => $attributes['departement_id'],
                    'code' => $attributes['code'],
                    'nom' => $attributes['nom'],
                    'geojson' => $attributes['geojson'],
                ];

                if ($sousPrefecture !== null) {
                    $sousPrefecture->update($payload);
                    $updated++;
                } else {
                    SousPrefecture::query()->create($payload);
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
     * @return array{departement_id: ?int, departement_label: string, code: ?string, nom: string, geojson: string}
     */
    private function featureToAttributes(array $feature): array
    {
        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];

        $nom = $this->pickProperty($properties, [
            'NomSp', 'NOMSP', 'nomsp', 'NomSP',
            'nom', 'name', 'NOM', 'NAME',
            'libelle', 'LIBELLE', 'label', 'LABEL',
        ]);

        $code = $this->pickProperty($properties, [
            'CodSp', 'CODSP', 'codsp', 'CodSP',
            'code', 'CODE', 'code_sp', 'CODE_SP',
        ]);

        $departementCode = $this->pickProperty($properties, [
            'CodDep', 'CODDEP', 'coddep',
            'code_dep', 'CODE_DEP',
        ]);

        $departementNom = $this->pickProperty($properties, [
            'NomDep', 'NOMDEP', 'nomdep',
            'nom_dep', 'NOM_DEP',
        ]);

        $regionCode = $this->pickProperty($properties, [
            'CodReg', 'CODREG', 'codreg',
            'code_region', 'CODE_REGION',
        ]);

        $regionNom = $this->pickProperty($properties, [
            'NomReg', 'NOMREG', 'nomreg',
            'region', 'REGION', 'nom_region', 'NOM_REGION',
        ]);

        $departement = $this->resolveDepartement($regionCode, $regionNom, $departementCode, $departementNom);
        $departementLabel = trim(($departementNom ?: $departementCode ?: '—').' / '.($regionNom ?: $regionCode ?: '—'));

        if (blank($nom)) {
            $nom = $code ?? '';
        }

        return [
            'departement_id' => $departement?->id,
            'departement_label' => $departementLabel,
            'code' => filled($code) ? mb_substr(trim((string) $code), 0, 10) : null,
            'nom' => mb_substr(trim((string) $nom), 0, 150),
            'geojson' => json_encode($feature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function resolveDepartement(?string $regionCode, ?string $regionNom, ?string $depCode, ?string $depNom): ?Departement
    {
        $region = $this->resolveRegion($regionCode, $regionNom);

        if ($region === null) {
            return null;
        }

        if (filled($depCode)) {
            $trimmedCode = trim($depCode);
            $candidates = array_unique(array_filter([
                $trimmedCode,
                str_pad(ltrim($trimmedCode, '0') ?: '0', 2, '0', STR_PAD_LEFT),
                str_pad(ltrim($trimmedCode, '0') ?: '0', 3, '0', STR_PAD_LEFT),
            ]));

            $departement = Departement::query()
                ->where('region_id', $region->id)
                ->whereIn('code', $candidates)
                ->first();

            if ($departement !== null) {
                return $departement;
            }
        }

        if (filled($depNom)) {
            return Departement::query()
                ->where('region_id', $region->id)
                ->whereRaw('UPPER(nom) = ?', [mb_strtoupper(trim($depNom))])
                ->first();
        }

        return null;
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
            return Region::query()
                ->whereRaw('UPPER(nom) = ?', [mb_strtoupper(trim($nom))])
                ->first();
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
