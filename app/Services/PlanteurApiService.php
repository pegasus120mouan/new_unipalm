<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlanteurApiService
{
    public function __construct(
        private readonly MinioStorageService $minio,
    ) {}

    public function getPlanteurs(array $query = []): array
    {
        $url = config('planteurs.api_base').'/planteurs.php';

        // Ne pas transmettre le paramètre local "action" à l'API distante.
        unset($query['action']);

        return $this->proxyRemoteGet($url, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{success: bool, data: array{planteurs: list<array<string, mixed>>, total: int, page: int, limit: int, total_pages: int}}
     */
    public function searchPlanteurs(array $query): array
    {
        $nom = $this->normalizeName((string) ($query['nom'] ?? $query['nom_prenoms'] ?? ''));
        $fiche = mb_strtolower(trim((string) ($query['numero_fiche'] ?? '')));
        $tel = $this->normalizePhone((string) ($query['telephone'] ?? ''));
        $collecteur = $this->normalizeName((string) ($query['collecteur'] ?? ''));

        $matched = [];
        foreach ($this->allPlanteurs() as $planteur) {
            if ($nom !== '') {
                $planteurNom = $this->normalizeName((string) ($planteur['nom_prenoms'] ?? ''));
                if ($planteurNom === '' || ! str_contains($planteurNom, $nom)) {
                    continue;
                }
            }

            if ($fiche !== '') {
                $planteurFiche = mb_strtolower(trim((string) ($planteur['numero_fiche'] ?? '')));
                if ($planteurFiche === '' || ! str_contains($planteurFiche, $fiche)) {
                    continue;
                }
            }

            if ($tel !== '') {
                $planteurTel = $this->normalizePhone((string) ($planteur['telephone'] ?? ''));
                if ($planteurTel === '' || ! str_contains($planteurTel, $tel)) {
                    continue;
                }
            }

            if ($collecteur !== '') {
                $collecteurNom = '';
                if (is_array($planteur['collecteur'] ?? null)) {
                    $collecteurNom = $this->normalizeName(trim(
                        (string) ($planteur['collecteur']['nom'] ?? '').' '.(string) ($planteur['collecteur']['prenoms'] ?? '')
                    ));
                } elseif (is_string($planteur['collecteur'] ?? null)) {
                    $collecteurNom = $this->normalizeName((string) $planteur['collecteur']);
                }
                if ($collecteurNom === '' || ! str_contains($collecteurNom, $collecteur)) {
                    continue;
                }
            }

            $matched[] = $planteur;
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = max(1, min(100, (int) ($query['limit'] ?? 15)));
        $total = count($matched);
        $totalPages = max(1, (int) ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'success' => true,
            'message' => 'Résultats de la recherche',
            'data' => [
                'planteurs' => array_slice($matched, ($page - 1) * $limit, $limit),
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /**
     * @return array{success: bool, data: array{planteur: array<string, mixed>, champs: list<array<string, mixed>>}}
     */
    public function getChampsForPlanteur(int $id): array
    {
        $json = $this->getPlanteurs(['id' => $id]);
        $planteur = $this->extractPlanteur($json, $id);

        if ($planteur === null) {
            throw new RuntimeException('Planteur introuvable.');
        }

        return [
            'success' => true,
            'data' => [
                'planteur' => $planteur,
                'champs' => $this->extractChamps($planteur),
            ],
        ];
    }

    /**
     * @return array{success: bool, data: array{planteur: array<string, mixed>, champ: array<string, mixed>}}
     */
    public function getChampForPlanteur(int $id, int $champId): array
    {
        $payload = $this->getChampsForPlanteur($id);

        foreach ($payload['data']['champs'] as $champ) {
            if ((int) ($champ['id'] ?? 0) === $champId) {
                return [
                    'success' => true,
                    'data' => [
                        'planteur' => $payload['data']['planteur'],
                        'champ' => $champ,
                    ],
                ];
            }
        }

        throw new RuntimeException('Champ introuvable pour ce planteur.');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{success: bool, data: array{matches: list<array<string, mixed>>, count: int}}
     */
    public function checkDoublon(array $query): array
    {
        $needle = [
            'nom_prenoms' => $query['nom_prenoms'] ?? '',
            'telephone' => $query['telephone'] ?? '',
        ];
        $matches = [];

        foreach ($this->allPlanteurs() as $item) {
            if ($this->isSamePerson($needle, $item)) {
                $matches[] = $item;
            }
        }

        return [
            'success' => true,
            'data' => [
                'matches' => $matches,
                'count' => count($matches),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>|null
     */
    private function extractPlanteur(array $json, int $id): ?array
    {
        $data = $json['data'] ?? null;
        if (! is_array($data)) {
            return null;
        }

        if (isset($data['planteurs']) && is_array($data['planteurs'])) {
            foreach ($data['planteurs'] as $item) {
                if (is_array($item) && (int) ($item['id'] ?? 0) === $id) {
                    return $item;
                }
            }
        }

        if (isset($data['id']) && (int) $data['id'] === $id) {
            return $data;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $planteur
     * @return list<array<string, mixed>>
     */
    private function extractChamps(array $planteur): array
    {
        $champs = [];

        // Numérotation des champs sur l'ensemble des exploitations (ordre de création),
        // pour que les codes des champs existants ne changent pas.
        $cultures = [];
        foreach ($this->normalizeExploitations($planteur) as $exploitation) {
            foreach ($exploitation['cultures'] as $culture) {
                $culture['_exploitation'] = $exploitation;
                $cultures[] = $culture;
            }
        }
        usort(
            $cultures,
            fn (array $a, array $b): int => ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0))
        );

        $index = 0;
        foreach ($cultures as $culture) {
            $index++;
            $exploitation = $culture['_exploitation'];
            $parcelles = is_array($culture['parcelles'] ?? null) ? $culture['parcelles'] : [];
            $champs[] = [
                'id' => $culture['id'] ?? $index,
                'numero_champ' => $this->champCode($planteur, $index, $culture),
                'type_culture' => $this->cultureLabel($culture),
                'type_culture_code' => (string) ($culture['type_culture'] ?? ''),
                'autre_culture' => (string) ($culture['autre_culture'] ?? ''),
                'editable' => isset($culture['id']),
                'superficie_ha' => $culture['superficie_ha'] ?? '',
                'age_culture' => $culture['age_culture'] ?? $culture['age'] ?? '',
                'mode_culture' => $culture['mode_culture'] ?? '',
                'production_estimee_kg' => $culture['production_estimee_kg'] ?? '',
                'parcelles_count' => count($parcelles),
                'parcelles' => $parcelles,
                'created_at' => $culture['created_at'] ?? null,
                'exploitation_id' => $exploitation['id'] ?? null,
                'exploitation_numero' => $exploitation['numero'],
                'exploitation_label' => $this->exploitationLabel($exploitation),
                'region' => (string) ($exploitation['region'] ?? ''),
                'sous_prefecture' => (string) ($exploitation['sous_prefecture_village'] ?? ''),
                'village' => (string) ($exploitation['village'] ?? ''),
            ];
        }

        if ($champs === [] && is_array($planteur['parcelles'] ?? null)) {
            foreach ($planteur['parcelles'] as $parcelle) {
                if (! is_array($parcelle)) {
                    continue;
                }
                $index++;
                $champs[] = [
                    'id' => $parcelle['id'] ?? $index,
                    'numero_champ' => $this->champCode($planteur, $index, $parcelle),
                    'type_culture' => (string) ($parcelle['type_culture'] ?? $parcelle['nom'] ?? ''),
                    'superficie_ha' => $parcelle['superficie_ha'] ?? $parcelle['superficie_calculee'] ?? $parcelle['superficie'] ?? '',
                    'age_culture' => $parcelle['age_culture'] ?? '',
                    'mode_culture' => $parcelle['mode_culture'] ?? '',
                    'production_estimee_kg' => $parcelle['production_estimee_kg'] ?? '',
                    'parcelles_count' => 1,
                    'parcelles' => [$parcelle],
                    'created_at' => $parcelle['created_at'] ?? null,
                    'region' => (string) ($planteur['exploitation']['region'] ?? ''),
                    'sous_prefecture' => (string) ($planteur['exploitation']['sous_prefecture_village'] ?? ''),
                    'village' => (string) ($planteur['exploitation']['village'] ?? ''),
                ];
            }
        }

        return $champs;
    }

    /** "Autre" affiche le nom saisi (autre_culture) */
    private function cultureLabel(array $culture): string
    {
        $type = trim((string) ($culture['type_culture'] ?? ''));
        $autre = trim((string) ($culture['autre_culture'] ?? ''));
        if (($type === '' || $type === 'Autre') && $autre !== '') {
            return $autre;
        }

        return $type;
    }

    /**
     * Toutes les exploitations d'un planteur, chacune avec "numero", "cultures" et "informations".
     * Si l'API ne renvoie pas encore "exploitations", on reconstruit une exploitation
     * à partir des champs "exploitation", "cultures" et "informations".
     *
     * @param  array<string, mixed>  $planteur
     * @return list<array<string, mixed>>
     */
    public function normalizeExploitations(array $planteur): array
    {
        if (is_array($planteur['exploitations'] ?? null) && $planteur['exploitations'] !== []) {
            $list = array_values(array_filter($planteur['exploitations'], 'is_array'));
        } else {
            $legacy = is_array($planteur['exploitation'] ?? null) ? $planteur['exploitation'] : null;
            $cultures = is_array($planteur['cultures'] ?? null) ? $planteur['cultures'] : [];
            if ($legacy === null && $cultures === []) {
                return [];
            }
            $list = [array_merge($legacy ?? [], [
                'cultures' => $cultures,
                'informations' => $planteur['informations'] ?? $planteur['informations_complementaires'] ?? null,
            ])];
        }

        foreach ($list as $i => $exploitation) {
            $cultures = is_array($exploitation['cultures'] ?? null) ? $exploitation['cultures'] : [];
            $informations = $exploitation['informations'] ?? $exploitation['informations_complementaires'] ?? null;
            if (is_array($informations) && array_is_list($informations)) {
                $informations = $informations[0] ?? null;
            }

            $list[$i]['numero'] = (int) ($exploitation['numero'] ?? $i + 1);
            $list[$i]['cultures'] = array_values(array_filter($cultures, 'is_array'));
            $list[$i]['informations'] = is_array($informations) ? $informations : null;
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $exploitation
     */
    private function exploitationLabel(array $exploitation): string
    {
        $lieu = array_filter([
            trim((string) ($exploitation['village'] ?? '')),
            trim((string) ($exploitation['sous_prefecture_village'] ?? '')),
            trim((string) ($exploitation['region'] ?? '')),
        ]);

        $label = 'Exploitation '.$exploitation['numero'];

        return $lieu === [] ? $label : $label.' — '.implode(', ', $lieu);
    }

    /**
     * @param  array<string, mixed>  $planteur
     * @param  array<string, mixed>  $source
     */
    private function champCode(array $planteur, int $index, array $source): string
    {
        foreach (['numero_champ', 'code_champ', 'code'] as $key) {
            $existing = trim((string) ($source[$key] ?? ''));
            if ($existing !== '') {
                return $existing;
            }
        }

        $seq = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        $fiche = trim((string) ($planteur['numero_fiche'] ?? ''));
        if ($fiche !== '') {
            return $fiche.'-C'.$seq;
        }

        return 'CHAMP-'.((int) ($planteur['id'] ?? 0)).'-C'.$seq;
    }

    private const PLANTATIONS_CACHE_KEY = 'planteurs.plantations_rows.v2';

    /** Court : les synchronisations de l'application mobile ne vident pas ce cache */
    private const PLANTATIONS_CACHE_SECONDS = 30;

    /**
     * Liste des plantations : une ligne par champ, avec son exploitation et le planteur associé.
     * Filtres : q (planteur, fiche, téléphone, code champ), region, village ; pagination page / limit.
     * refresh=1 : relit l'API sans passer par le cache.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getPlantations(array $query): array
    {
        if (filter_var($query['refresh'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->forgetPlantationsCache();
        }

        $cached = Cache::remember(self::PLANTATIONS_CACHE_KEY, self::PLANTATIONS_CACHE_SECONDS, fn (): array => [
            'rows' => $this->buildPlantationRows(),
            'loaded_at' => now()->toIso8601String(),
        ]);
        $rows = $cached['rows'] ?? [];

        $q = $this->normalizeName((string) ($query['q'] ?? ''));
        $region = trim((string) ($query['region'] ?? ''));
        $village = $this->normalizeName((string) ($query['village'] ?? ''));

        $filtered = array_values(array_filter($rows, function (array $row) use ($q, $region, $village): bool {
            if ($region !== '' && $row['region'] !== $region) {
                return false;
            }
            if ($village !== '') {
                $lieu = $this->normalizeName($row['village'].' '.$row['sous_prefecture']);
                if (! str_contains($lieu, $village)) {
                    return false;
                }
            }
            if ($q !== '') {
                $haystack = $this->normalizeName(implode(' ', [
                    $row['planteur_nom'], $row['numero_fiche'], $row['telephone'], $row['numero_champ'],
                ]));
                if (! str_contains($haystack, $q)) {
                    return false;
                }
            }

            return true;
        }));

        $limit = max(1, min(100, (int) ($query['limit'] ?? 15)));
        $total = count($filtered);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = min(max(1, (int) ($query['page'] ?? 1)), $totalPages);

        $regions = array_values(array_unique(array_filter(array_column($rows, 'region'))));
        sort($regions);

        return [
            'success' => true,
            'data' => [
                'plantations' => array_slice($filtered, ($page - 1) * $limit, $limit),
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'superficie_totale' => round(array_sum(array_map(
                    fn (array $r): float => (float) ($r['superficie_ha'] ?: $r['superficie_gps']),
                    $filtered
                )), 2),
                'parcelles_tracees' => count(array_filter($filtered, fn (array $r): bool => $r['parcelle_tracee'])),
                'nb_planteurs' => count(array_unique(array_column($filtered, 'planteur_id'))),
                'regions' => $regions,
                'loaded_at' => $cached['loaded_at'] ?? null,
            ],
        ];
    }

    public function forgetPlantationsCache(): void
    {
        Cache::forget(self::PLANTATIONS_CACHE_KEY);
    }

    /**
     * Une ligne par champ (culture), numérotée comme sur la fiche du planteur.
     *
     * @return list<array<string, mixed>>
     */
    private function buildPlantationRows(): array
    {
        $json = $this->requestJson('get', config('planteurs.api_base').'/planteurs.php', ['page' => 1, 'limit' => 10000]);
        $rows = [];

        foreach ($json['data']['planteurs'] ?? [] as $planteur) {
            if (! is_array($planteur)) {
                continue;
            }

            $exploitations = [];
            foreach ($this->normalizeExploitations($planteur) as $exploitation) {
                $exploitations[(int) ($exploitation['id'] ?? 0)] = $exploitation;
            }
            $firstExploitation = $exploitations === [] ? [] : reset($exploitations);

            $cultures = array_values(array_filter(is_array($planteur['cultures'] ?? null) ? $planteur['cultures'] : [], 'is_array'));
            usort($cultures, fn (array $a, array $b): int => ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)));

            $collecteur = is_array($planteur['collecteur'] ?? null)
                ? trim(($planteur['collecteur']['nom'] ?? '').' '.($planteur['collecteur']['prenoms'] ?? ''))
                : '';

            foreach ($cultures as $index => $culture) {
                $exploitation = $exploitations[(int) ($culture['exploitation_id'] ?? 0)] ?? $firstExploitation;
                $parcelles = array_filter(
                    is_array($culture['parcelles'] ?? null) ? $culture['parcelles'] : [],
                    fn ($p): bool => is_array($p) && is_array($p['points'] ?? null) && count($p['points']) >= 3
                );

                $rows[] = [
                    'champ_id' => $culture['id'] ?? null,
                    'numero_champ' => $this->champCode($planteur, $index + 1, $culture),
                    'type_culture' => $this->cultureLabel($culture),
                    'superficie_ha' => (float) ($culture['superficie_ha'] ?? 0),
                    'superficie_gps' => round(array_sum(array_map(fn (array $p): float => (float) ($p['superficie_calculee'] ?? 0), $parcelles)), 2),
                    'parcelle_tracee' => $parcelles !== [],
                    'region' => trim((string) ($exploitation['region'] ?? '')),
                    'sous_prefecture' => trim((string) ($exploitation['sous_prefecture_village'] ?? '')),
                    'village' => trim((string) ($exploitation['village'] ?? '')),
                    'exploitation_numero' => $exploitation['numero'] ?? null,
                    'planteur_id' => (int) ($planteur['id'] ?? 0),
                    'planteur_nom' => trim((string) ($planteur['nom_prenoms'] ?? '')),
                    'numero_fiche' => (string) ($planteur['numero_fiche'] ?? ''),
                    'telephone' => (string) ($planteur['telephone'] ?? ''),
                    'collecteur' => $collecteur,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allPlanteurs(): array
    {
        $json = $this->getPlanteurs(['page' => 1, 'limit' => 10000]);
        $rows = is_array($json['data']['planteurs'] ?? null) ? $json['data']['planteurs'] : [];
        $collected = [];
        foreach ($rows as $item) {
            if (is_array($item)) {
                $collected[] = $item;
            }
        }

        $total = (int) ($json['data']['total'] ?? 0);
        $totalPages = max(1, (int) ($json['data']['total_pages'] ?? 1));
        if ($totalPages <= 1 || ($total > 0 && count($collected) >= $total)) {
            return $collected;
        }

        for ($page = 2; $page <= $totalPages && $page <= 200; $page++) {
            $next = $this->getPlanteurs(['page' => $page, 'limit' => 100]);
            foreach ($next['data']['planteurs'] ?? [] as $item) {
                if (is_array($item)) {
                    $collected[] = $item;
                }
            }
        }

        return $collected;
    }

    private function normalizeName(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && $ascii !== '') {
            $value = strtolower($ascii);
        }

        $value = preg_replace('/[^a-z0-9 ]+/', '', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (str_starts_with($digits, '225') && strlen($digits) > 10) {
            $digits = substr($digits, 3);
        }

        return strlen($digits) >= 8 ? $digits : '';
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private function isSamePerson(array $a, array $b): bool
    {
        $nomA = $this->normalizeName((string) ($a['nom_prenoms'] ?? ''));
        $nomB = $this->normalizeName((string) ($b['nom_prenoms'] ?? ''));
        if ($nomA !== '' && $nomA === $nomB) {
            return true;
        }

        $telA = $this->normalizePhone((string) ($a['telephone'] ?? ''));
        $telB = $this->normalizePhone((string) ($b['telephone'] ?? ''));

        return $telA !== '' && $telA === $telB;
    }

    public function getGlobalStats(): array
    {
        return $this->requestJson('get', config('planteurs.stats_url'));
    }

    public function getRegions(): array
    {
        $json = $this->getPlanteurs(['limit' => 10000]);
        $regions = [];

        foreach ($json['data']['planteurs'] ?? [] as $planteur) {
            if (! is_array($planteur)) {
                continue;
            }

            foreach ($this->normalizeExploitations($planteur) as $exploitation) {
                $region = $exploitation['region'] ?? '';
                $sousPref = $exploitation['sous_prefecture_village'] ?? '';

                if ($region === '') {
                    continue;
                }

                if (! isset($regions[$region])) {
                    $regions[$region] = [];
                }

                if ($sousPref !== '' && ! in_array($sousPref, $regions[$region], true)) {
                    $regions[$region][] = $sousPref;
                }
            }
        }

        ksort($regions);
        foreach ($regions as $region => $sousPrefs) {
            sort($regions[$region]);
        }

        return [
            'success' => true,
            'message' => 'Régions récupérées',
            'data' => ['regions' => $regions],
        ];
    }

    public function getDoublons(): array
    {
        $planteurs = [];
        foreach ($this->allPlanteurs() as $planteur) {
            $id = (int) ($planteur['id'] ?? 0);
            if ($id > 0) {
                $planteurs[$id] = $planteur;
            }
        }

        $parent = [];
        foreach (array_keys($planteurs) as $id) {
            $parent[$id] = $id;
        }

        $find = function (int $id) use (&$parent, &$find): int {
            if ($parent[$id] !== $id) {
                $parent[$id] = $find($parent[$id]);
            }

            return $parent[$id];
        };
        $union = function (int $a, int $b) use (&$parent, $find): void {
            $rootA = $find($a);
            $rootB = $find($b);
            if ($rootA !== $rootB) {
                $parent[$rootB] = $rootA;
            }
        };

        $byNom = [];
        $byTel = [];
        foreach ($planteurs as $id => $planteur) {
            $nom = $this->normalizeName((string) ($planteur['nom_prenoms'] ?? ''));
            if ($nom !== '') {
                $byNom[$nom][] = $id;
            }
            $tel = $this->normalizePhone((string) ($planteur['telephone'] ?? ''));
            if ($tel !== '') {
                $byTel[$tel][] = $id;
            }
        }

        foreach ([$byNom, $byTel] as $buckets) {
            foreach ($buckets as $ids) {
                $first = $ids[0];
                for ($i = 1, $count = count($ids); $i < $count; $i++) {
                    $union($first, $ids[$i]);
                }
            }
        }

        $grouped = [];
        foreach ($planteurs as $id => $planteur) {
            $grouped[$find($id)][] = $planteur;
        }

        $groupes = [];
        foreach ($grouped as $rows) {
            if (count($rows) < 2) {
                continue;
            }
            usort(
                $rows,
                fn (array $a, array $b): int => strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''))
            );
            $groupes[] = $rows;
        }

        usort(
            $groupes,
            fn (array $a, array $b): int => strcmp(
                $this->normalizeName((string) ($a[0]['nom_prenoms'] ?? '')),
                $this->normalizeName((string) ($b[0]['nom_prenoms'] ?? ''))
            )
        );

        $totalDoublons = 0;
        foreach ($groupes as $groupe) {
            $totalDoublons += count($groupe);
        }

        return [
            'success' => true,
            'message' => 'Doublons détectés sur le nom et prénoms ou le téléphone',
            'data' => [
                'groupes' => $groupes,
                'total_groupes' => count($groupes),
                'total_doublons' => $totalDoublons,
                'a_supprimer' => max(0, $totalDoublons - count($groupes)),
                'a_integrer' => max(0, $totalDoublons - count($groupes)),
            ],
        ];
    }

    /**
     * Intègre les champs d'une fiche doublon dans la fiche originale, puis retire la fiche doublon.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function integrerDoublon(array $data): array
    {
        $originalId = (int) ($data['original_id'] ?? $data['cible_id'] ?? 0);
        $doublonId = (int) ($data['doublon_id'] ?? $data['id'] ?? 0);

        if ($originalId <= 0 || $doublonId <= 0) {
            throw new RuntimeException('Identifiants de fiches manquants.');
        }
        if ($originalId === $doublonId) {
            throw new RuntimeException('La fiche originale et la fiche doublon doivent être distinctes.');
        }

        $original = $this->extractPlanteur($this->getPlanteurs(['id' => $originalId]), $originalId);
        $doublon = $this->extractPlanteur($this->getPlanteurs(['id' => $doublonId]), $doublonId);

        if ($original === null || $doublon === null) {
            throw new RuntimeException('Fiche originale ou fiche doublon introuvable.');
        }

        $exploitationId = (int) ($original['exploitation']['id'] ?? 0);
        if ($exploitationId <= 0) {
            throw new RuntimeException('La fiche originale n\'a pas d\'exploitation pour recevoir les champs.');
        }

        $copied = $this->copyChampsToExploitation($original, $doublon);
        if ($copied === 0) {
            throw new RuntimeException('Aucun champ n\'a pu être intégré depuis la fiche doublon.');
        }

        $this->requestJson(
            'post',
            config('planteurs.api_base').'/delete_planteur.php',
            ['action' => 'delete_planteur', 'id' => $doublonId],
            ['Content-Type' => 'application/json']
        );

        return [
            'success' => true,
            'message' => $copied.' champ(s) intégré(s) dans la fiche '.($original['numero_fiche'] ?? $originalId).'.',
            'data' => [
                'original_id' => $originalId,
                'doublon_id' => $doublonId,
                'champs_integres' => $copied,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $doublon
     */
    private function copyChampsToExploitation(array $original, array $doublon): int
    {
        $exploitationId = (int) ($original['exploitation']['id'] ?? 0);
        $url = config('planteurs.api_base').'/parcelles.php';
        $copied = 0;

        $cultures = is_array($doublon['cultures'] ?? null) ? $doublon['cultures'] : [];
        $cultures = array_values(array_filter($cultures, 'is_array'));

        if ($cultures === [] && is_array($doublon['parcelles'] ?? null)) {
            $cultures = [[
                'type_culture' => '',
                'parcelles' => $doublon['parcelles'],
            ]];
        }

        foreach ($cultures as $culture) {
            $parcelles = is_array($culture['parcelles'] ?? null) ? $culture['parcelles'] : [];
            if ($parcelles === []) {
                $parcelles = [[]];
            }

            foreach ($parcelles as $parcelle) {
                if (! is_array($parcelle)) {
                    continue;
                }

                $points = $this->normalizeParcellePoints($parcelle['points'] ?? []);
                if (count($points) < 3) {
                    $points = $this->fallbackTriangle($doublon['exploitation'] ?? $original['exploitation'] ?? [], $points);
                }
                if (count($points) < 3) {
                    continue;
                }

                $this->requestJson('post', $url, [
                    'exploitation_id' => $exploitationId,
                    'nom' => trim((string) ($parcelle['nom'] ?? $culture['type_culture'] ?? $culture['autre_culture'] ?? 'Champ')),
                    'type_culture' => trim((string) ($culture['type_culture'] ?? $culture['autre_culture'] ?? $parcelle['nom'] ?? '')),
                    'superficie_ha' => $culture['superficie_ha'] ?? $parcelle['superficie_ha'] ?? $parcelle['superficie_calculee'] ?? '',
                    'age_culture' => $culture['age_culture'] ?? $culture['age'] ?? '',
                    'mode_culture' => $culture['mode_culture'] ?? '',
                    'production_estimee_kg' => $culture['production_estimee_kg'] ?? '',
                    'points' => $points,
                ], ['Content-Type' => 'application/json']);

                $copied++;
            }
        }

        return $copied;
    }

    /**
     * @param  mixed  $raw
     * @return list<array{latitude: float, longitude: float}>
     */
    private function normalizeParcellePoints(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $points = [];
        foreach ($raw as $pt) {
            if (is_array($pt) && array_is_list($pt) && count($pt) >= 2) {
                $lat = (float) $pt[0];
                $lng = (float) $pt[1];
            } elseif (is_array($pt)) {
                $lat = (float) ($pt['latitude'] ?? $pt['lat'] ?? 0);
                $lng = (float) ($pt['longitude'] ?? $pt['lng'] ?? $pt['lon'] ?? 0);
            } else {
                continue;
            }

            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }
            $points[] = ['latitude' => $lat, 'longitude' => $lng];
        }

        return $points;
    }

    /**
     * @param  array<string, mixed>  $exploitation
     * @param  list<array{latitude: float, longitude: float}>  $points
     * @return list<array{latitude: float, longitude: float}>
     */
    private function fallbackTriangle(array $exploitation, array $points): array
    {
        $lat = (float) ($points[0]['latitude'] ?? $exploitation['latitude'] ?? 0);
        $lng = (float) ($points[0]['longitude'] ?? $exploitation['longitude'] ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return $points;
        }

        $delta = 0.00015;

        return [
            ['latitude' => $lat, 'longitude' => $lng],
            ['latitude' => $lat + $delta, 'longitude' => $lng],
            ['latitude' => $lat, 'longitude' => $lng + $delta],
        ];
    }

    public function post(array $data): array
    {
        $action = $data['action'] ?? '';

        // Toute écriture (champ, planteur, import…) peut changer la liste des plantations
        $this->forgetPlantationsCache();

        if ($action === 'integrer_doublon') {
            return $this->integrerDoublon($data);
        }

        if ($action === 'update_champ' || $action === 'delete_champ') {
            return $this->saveChamp($action === 'update_champ' ? 'update' : 'delete', $data);
        }

        if ($action === 'delete_planteur') {
            return $this->deletePlanteur($data);
        }

        $url = match ($action) {
            'update_planteur' => config('planteurs.api_base').'/update_planteur.php',
            'delete_planteur' => config('planteurs.api_base').'/delete_planteur.php',
            'import_planteurs' => config('planteurs.api_base').'/api_import_planteurs.php',
            'create_planteur' => config('planteurs.api_base').'/api_import_planteurs.php',
            default => throw new RuntimeException('Action non supportée : '.$action),
        };

        if ($action === 'create_planteur') {
            return $this->createPlanteur($data);
        }

        if ($action === 'import_planteurs') {
            $rows = $data['rows'] ?? $data['planteurs'] ?? [];
            if (! is_array($rows) || $rows === []) {
                throw new RuntimeException('Aucune ligne à importer.');
            }

            return $this->requestJson('post', $url, ['rows' => $rows], [
                'Content-Type' => 'application/json',
            ]);
        }

        $json = $this->requestJson('post', $url, $data, [
            'Content-Type' => 'application/json',
        ]);

        return $json;
    }

    /**
     * Modifie ou supprime un champ (culture + parcelles) sur l'API distante.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function saveChamp(string $op, array $data): array
    {
        $champId = (int) ($data['champ_id'] ?? $data['id'] ?? 0);
        if ($champId <= 0) {
            throw new RuntimeException('Identifiant du champ manquant.');
        }

        $payload = [
            'op' => $op,
            'id' => $champId,
            'planteur_id' => (int) ($data['planteur_id'] ?? 0),
        ];

        if ($op === 'update') {
            $typeCulture = trim((string) ($data['type_culture'] ?? ''));
            if ($typeCulture === '') {
                throw new RuntimeException('Le type de culture est obligatoire.');
            }
            foreach (['superficie_ha', 'age_culture', 'production_estimee_kg'] as $field) {
                $value = $data[$field] ?? '';
                if ($value !== '' && $value !== null && (! is_numeric($value) || (float) $value < 0)) {
                    throw new RuntimeException('Valeur invalide pour « '.$field.' ».');
                }
            }
            $payload += [
                'type_culture' => $typeCulture,
                'autre_culture' => trim((string) ($data['autre_culture'] ?? '')),
                'superficie_ha' => $data['superficie_ha'] ?? '',
                'age_culture' => $data['age_culture'] ?? '',
                'mode_culture' => trim((string) ($data['mode_culture'] ?? '')),
                'production_estimee_kg' => $data['production_estimee_kg'] ?? '',
            ];
        }

        return $this->requestJson('post', config('planteurs.api_base').'/champ.php', $payload, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Supprime tous les champs (cultures + parcelles) du planteur, puis sa fiche.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function deletePlanteur(array $data): array
    {
        $id = (int) ($data['id'] ?? $data['planteur_id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Identifiant du planteur manquant.');
        }

        $champsSupprimes = $this->deleteAllChamps($id);

        $json = $this->requestJson(
            'post',
            config('planteurs.api_base').'/delete_planteur.php',
            ['action' => 'delete_planteur', 'id' => $id],
            ['Content-Type' => 'application/json']
        );

        $json['message'] = 'Planteur supprimé avec '.$champsSupprimes.' champ(s).';
        $json['champs_supprimes'] = $champsSupprimes;

        return $json;
    }

    private function deleteAllChamps(int $planteurId): int
    {
        try {
            $json = $this->requestJson('post', config('planteurs.api_base').'/champ.php', [
                'op' => 'delete_all',
                'planteur_id' => $planteurId,
            ], ['Content-Type' => 'application/json']);

            return (int) ($json['data']['champs_supprimes'] ?? 0);
        } catch (RuntimeException $e) {
            // champ.php sans "delete_all" (ancienne version) : suppression champ par champ
            if (! str_contains($e->getMessage(), 'Opération inconnue')) {
                throw $e;
            }
        }

        $planteur = $this->extractPlanteur($this->getPlanteurs(['id' => $planteurId]), $planteurId);
        if ($planteur === null) {
            return 0;
        }

        $count = 0;
        foreach ($this->extractChamps($planteur) as $champ) {
            if (! ($champ['editable'] ?? false)) {
                continue;
            }
            $this->saveChamp('delete', ['champ_id' => $champ['id'], 'planteur_id' => $planteurId]);
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createPlanteur(array $data): array
    {
        $exploitation = is_array($data['exploitation'] ?? null) ? $data['exploitation'] : [];
        $nom = trim((string) ($data['nom_prenoms'] ?? ''));

        if ($nom === '') {
            throw new RuntimeException('Le nom et prénoms sont obligatoires.');
        }

        $row = [
            'nom_prenoms' => $nom,
            'telephone' => trim((string) ($data['telephone'] ?? '')),
            'piece_identite' => trim((string) ($data['piece_identite'] ?? '')),
            'date_naissance' => trim((string) ($data['date_naissance'] ?? '')),
            'lieu_naissance' => trim((string) ($data['lieu_naissance'] ?? '')),
            'situation_matrimoniale' => trim((string) ($data['situation_matrimoniale'] ?? '')),
            'nombre_enfants' => $data['nombre_enfants'] ?? '',
            'region' => trim((string) ($exploitation['region'] ?? $data['region'] ?? '')),
            'sous_prefecture_village' => trim((string) ($exploitation['sous_prefecture_village'] ?? $data['sous_prefecture_village'] ?? '')),
            'village' => trim((string) ($exploitation['village'] ?? $data['village'] ?? '')),
            'latitude' => trim((string) ($exploitation['latitude'] ?? $data['latitude'] ?? '')),
            'longitude' => trim((string) ($exploitation['longitude'] ?? $data['longitude'] ?? '')),
            'collecteur' => trim((string) ($data['collecteur'] ?? '')),
        ];

        return $this->requestJson(
            'post',
            config('planteurs.api_base').'/api_import_planteurs.php',
            ['rows' => [$row]],
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * @param  array<string, mixed>  $queryParams
     * @return array<string, mixed>
     */
    private function proxyRemoteGet(string $url, array $queryParams): array
    {
        $json = $this->requestJson('get', $url, $queryParams);

        if (($json['success'] ?? false) === true) {
            if (isset($json['data']['planteurs']) && is_array($json['data']['planteurs'])) {
                foreach ($json['data']['planteurs'] as $index => $planteur) {
                    if (is_array($planteur)) {
                        $json['data']['planteurs'][$index] = $this->enrichPlanteur($planteur);
                    }
                }
            } elseif (isset($json['data']) && is_array($json['data']) && isset($json['data']['id'])) {
                $json['data'] = $this->enrichPlanteur($json['data']);
            }
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $url, array $payload = [], array $headers = []): array
    {
        try {
            $pending = Http::timeout(60)
                ->connectTimeout(15)
                ->retry(2, 1500, fn ($exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()), throw: false)
                ->acceptJson();

            if ($headers !== []) {
                $pending = $pending->withHeaders($headers);
            }

            $response = strtolower($method) === 'post'
                ? $pending->post($url, $payload)
                : $pending->get($url, $payload);
        } catch (ConnectionException) {
            throw new RuntimeException(
                'L\'API planteurs ne répond pas (délai dépassé). Réessayez dans quelques instants.'
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException(match (true) {
                $response->status() === 404 => 'Le fichier '.basename((string) parse_url($url, PHP_URL_PATH)).' est introuvable sur le serveur API (HTTP 404). Il doit y être déployé.',
                $response->serverError() => 'L\'API planteurs a renvoyé une erreur serveur (HTTP '.$response->status().'). Réessayez dans quelques instants.',
                default => 'Réponse invalide de l\'API planteurs.',
            });
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                $json['error'] ?? $json['message'] ?? 'Impossible de joindre l\'API planteurs.'
            );
        }

        return $json;
    }

    private function enrichPlanteur(array $planteur): array
    {
        $bucket = (string) config('planteurs.minio_bucket', 'planteurs');

        $photoKey = '';
        if (! empty($planteur['photo_url'])
            && is_string($planteur['photo_url'])
            && preg_match('/^https?:\/\//i', $planteur['photo_url'])
            && ! $this->urlHasSignature($planteur['photo_url'])) {
            $photoKey = $this->extractObjectKeyFromUrl($planteur['photo_url'], $bucket);
        }

        if ($photoKey === '') {
            $photoKey = $this->extractPhotoKey($planteur);
        }

        if ($photoKey !== '' && (empty($planteur['photo_url']) || ! $this->urlHasSignature((string) ($planteur['photo_url'] ?? '')))) {
            $presigned = $this->minio->getPresignedUrl($photoKey, 3600, $bucket);
            if ($presigned) {
                $planteur['photo_url'] = $presigned;
            }
        }

        if (isset($planteur['exploitation']) && is_array($planteur['exploitation'])) {
            $planteur['exploitation'] = $this->presignExploitationVideo($planteur['exploitation'], $bucket);
        }

        $planteur['exploitations'] = array_map(
            fn (array $exploitation): array => $this->presignExploitationVideo($exploitation, $bucket),
            $this->normalizeExploitations($planteur)
        );
        $planteur['nb_exploitations'] = count($planteur['exploitations']);

        return $planteur;
    }

    /**
     * @param  array<string, mixed>  $exploitation
     * @return array<string, mixed>
     */
    private function presignExploitationVideo(array $exploitation, string $bucket): array
    {
        $videoKey = '';
        $videoUrl = $exploitation['video_url'] ?? '';

        if (is_string($videoUrl) && $videoUrl !== '' && preg_match('/^https?:\/\//i', $videoUrl) && ! $this->urlHasSignature($videoUrl)) {
            $videoKey = $this->extractObjectKeyFromUrl($videoUrl, $bucket);
        }

        if ($videoKey === '' && ! empty($exploitation['video']) && is_string($exploitation['video'])) {
            $videoKey = trim($exploitation['video']);
        }

        if ($videoKey !== '' && (empty($videoUrl) || ! $this->urlHasSignature((string) $videoUrl))) {
            $presignedVideo = $this->minio->getPresignedUrl($videoKey, 3600, $bucket);
            if ($presignedVideo) {
                $exploitation['video_url'] = $presignedVideo;
            }
        }

        return $exploitation;
    }

    private function extractPhotoKey(array $planteur): string
    {
        foreach (['photo', 'photo_planteur', 'image', 'image_planteur', 'avatar', 'profil_photo', 'photo_key', 'image_key'] as $key) {
            if (! empty($planteur[$key]) && is_string($planteur[$key])) {
                return trim($planteur[$key]);
            }
        }

        return '';
    }

    private function extractObjectKeyFromUrl(string $url, string $bucket): string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || empty($parts['path'])) {
            return '';
        }

        $prefix = '/'.trim($bucket, '/').'/';

        if (! str_starts_with($parts['path'], $prefix)) {
            return '';
        }

        return ltrim(substr($parts['path'], strlen($prefix)), '/');
    }

    private function urlHasSignature(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['query'])) {
            return false;
        }

        parse_str($parts['query'], $query);

        return isset($query['X-Amz-Signature']);
    }
}
