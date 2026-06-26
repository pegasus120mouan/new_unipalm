<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RegionApiService
{
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
