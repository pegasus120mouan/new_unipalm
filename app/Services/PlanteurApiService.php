<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
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

    public function getDoublons(): array
    {
        $json = $this->requestJson('get', (string) config('planteurs.doublons_url'));

        if (($json['success'] ?? false) === true && isset($json['data']['groupes']) && is_array($json['data']['groupes'])) {
            foreach ($json['data']['groupes'] as $gIndex => $groupe) {
                if (! is_array($groupe)) {
                    continue;
                }
                foreach ($groupe as $pIndex => $planteur) {
                    if (is_array($planteur)) {
                        $json['data']['groupes'][$gIndex][$pIndex] = $this->enrichPlanteur($planteur);
                    }
                }
            }
        }

        return $json;
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

        $json = $this->requestJson('post', $url, $data, [
            'Content-Type' => 'application/json',
        ]);

        return $json;
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
                ->retry(2, 1500, fn ($exception): bool => $exception instanceof ConnectionException)
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
            throw new RuntimeException('Réponse invalide de l\'API planteurs.');
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
