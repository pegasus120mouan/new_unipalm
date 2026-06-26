<?php

namespace App\Services;

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

        return $this->proxyRemoteGet($url, $query);
    }

    public function getGlobalStats(): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->get(config('planteurs.stats_url'));

        if (! $response->successful()) {
            throw new RuntimeException('Erreur lors de la récupération des statistiques planteurs.');
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Réponse invalide de l\'API statistiques.');
        }

        return $json;
    }

    public function getRegions(): array
    {
        $json = $this->getPlanteurs(['limit' => 10000]);
        $regions = [];

        foreach ($json['data']['planteurs'] ?? [] as $planteur) {
            if (! is_array($planteur)) {
                continue;
            }

            $region = $planteur['exploitation']['region'] ?? '';
            $sousPref = $planteur['exploitation']['sous_prefecture_village'] ?? '';

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

    public function post(array $data): array
    {
        $action = $data['action'] ?? '';

        $url = match ($action) {
            'update_planteur' => config('planteurs.api_base').'/update_planteur.php',
            'delete_planteur' => config('planteurs.api_base').'/delete_planteur.php',
            'import_planteurs' => config('planteurs.api_base').'/api_import_planteurs.php',
            default => throw new RuntimeException('Action non supportée : '.$action),
        };

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $data);

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Réponse invalide de l\'API distante.');
        }

        if (! $response->successful()) {
            throw new RuntimeException($json['error'] ?? $json['message'] ?? 'Erreur API planteurs.');
        }

        return $json;
    }

    private function proxyRemoteGet(string $url, array $queryParams): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->get($url, $queryParams);

        if (! $response->successful()) {
            throw new RuntimeException('Erreur lors de la récupération des planteurs (API distante).');
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Réponse invalide de l\'API distante.');
        }

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
            $videoKey = '';
            $videoUrl = $planteur['exploitation']['video_url'] ?? '';

            if (is_string($videoUrl) && $videoUrl !== '' && preg_match('/^https?:\/\//i', $videoUrl) && ! $this->urlHasSignature($videoUrl)) {
                $videoKey = $this->extractObjectKeyFromUrl($videoUrl, $bucket);
            }

            if ($videoKey === '' && ! empty($planteur['exploitation']['video']) && is_string($planteur['exploitation']['video'])) {
                $videoKey = trim($planteur['exploitation']['video']);
            }

            if ($videoKey !== '' && (empty($videoUrl) || ! $this->urlHasSignature((string) $videoUrl))) {
                $presignedVideo = $this->minio->getPresignedUrl($videoKey, 3600, $bucket);
                if ($presignedVideo) {
                    $planteur['exploitation']['video_url'] = $presignedVideo;
                }
            }
        }

        return $planteur;
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
