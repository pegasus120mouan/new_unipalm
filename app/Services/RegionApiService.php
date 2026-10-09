<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class RegionApiService
{
    /**
     * Régions (sans $regionId) ou sous-préfectures d'une région, triées et sans doublons de nom.
     *
     * @return list<array{id: int, nom: string}>
     */
    public function referentiel(?int $regionId = null): array
    {
        $query = $regionId ? ['action' => 'sous_prefectures', 'region_id' => $regionId] : ['action' => 'list'];

        return Cache::remember('referentiel_regions:'.($regionId ?? 'all'), now()->addHours(6), function () use ($query): array {
            $items = [];
            foreach ($this->get($query)['data'] ?? [] as $item) {
                $nom = trim((string) ($item['nom'] ?? ''));
                $key = mb_strtolower($nom);
                if ($nom === '' || isset($items[$key])) {
                    continue;
                }
                $items[$key] = ['id' => (int) ($item['id'] ?? 0), 'nom' => $nom];
            }
            $items = array_values($items);
            usort($items, fn (array $a, array $b): int => strcmp(
                Str::lower(Str::ascii($a['nom'])),
                Str::lower(Str::ascii($b['nom']))
            ));

            return $items;
        });
    }

    public function get(array $query = []): array
    {
        return $this->proxyGet(config('planteurs.regions_url'), $query);
    }

    public function post(array $data): array
    {
        return $this->proxyPost(config('planteurs.regions_url'), $data);
    }

    private function proxyGet(string $url, array $query): array
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->get($url, $query);

        if (! $response->successful()) {
            throw new RuntimeException('Erreur lors de la récupération des régions (API distante).');
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Réponse invalide de l\'API distante.');
        }

        return $json;
    }

    private function proxyPost(string $url, array $data): array
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $data);

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Réponse invalide de l\'API distante.');
        }

        if (! $response->successful() || ($json['success'] ?? false) !== true) {
            throw new RuntimeException($json['error'] ?? $json['message'] ?? 'Erreur API régions.');
        }

        return $json;
    }
}
