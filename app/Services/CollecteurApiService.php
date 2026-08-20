<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CollecteurApiService
{
    public function __construct(
        private readonly MinioStorageService $minio,
    ) {}

    public function getUtilisateurs(array $query = []): array
    {
        $json = $this->proxyGet(config('planteurs.utilisateurs_url'), $query);

        if (($json['success'] ?? false) === true) {
            if (isset($json['data']['utilisateurs']) && is_array($json['data']['utilisateurs'])) {
                foreach ($json['data']['utilisateurs'] as $index => $user) {
                    if (is_array($user)) {
                        $json['data']['utilisateurs'][$index] = $this->enrichAvatar($user);
                    }
                }
            } elseif (isset($json['data']) && is_array($json['data']) && isset($json['data']['id'])) {
                $json['data'] = $this->enrichAvatar($json['data']);
            } elseif (isset($json['data'][0]) && is_array($json['data'][0])) {
                foreach ($json['data'] as $index => $user) {
                    if (is_array($user)) {
                        $json['data'][$index] = $this->enrichAvatar($user);
                    }
                }
            }
        }

        return $json;
    }

    public function getUtilisateur(int $id): ?array
    {
        $json = $this->getUtilisateurs(['id' => $id]);

        if (($json['success'] ?? false) !== true || empty($json['data'])) {
            return null;
        }

        $data = $json['data'];

        if (isset($data['utilisateurs'][0]) && is_array($data['utilisateurs'][0])) {
            return $data['utilisateurs'][0];
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        if (isset($data['id'])) {
            return $data;
        }

        return null;
    }

    /**
     * @return array{stats: array, repartition_cultures: array, evolution_mensuelle: array, derniers_planteurs: array}
     */
    public function getStats(int $collecteurId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = ['collecteur_id' => $collecteurId];

        if ($dateDebut) {
            $query['date_debut'] = $dateDebut;
        }
        if ($dateFin) {
            $query['date_fin'] = $dateFin;
        }

        $json = $this->proxyGet((string) config('planteurs.stats_collecteur_url'), $query);

        if (($json['success'] ?? false) !== true) {
            throw new RuntimeException($json['error'] ?? $json['message'] ?? 'Erreur stats collecteur.');
        }

        $data = is_array($json['data'] ?? null) ? $json['data'] : [];

        return [
            'stats' => [
                'nombre_exploitants' => (int) ($data['stats']['nombre_exploitants'] ?? 0),
                'superficie_totale' => (float) ($data['stats']['superficie_totale'] ?? 0),
                'nombre_parcelles' => (int) ($data['stats']['nombre_parcelles'] ?? 0),
            ],
            'repartition_cultures' => $data['repartition_cultures'] ?? [],
            'evolution_mensuelle' => $data['evolution_mensuelle'] ?? [],
            'derniers_planteurs' => $data['derniers_planteurs'] ?? [],
        ];
    }

    public function createUtilisateur(array $data): array
    {
        return $this->proxyPost(
            config('planteurs.api_base').'/create_utilisateur.php',
            $data
        );
    }

    public function updateUtilisateur(array $data): array
    {
        return $this->proxyPost(
            config('planteurs.api_base').'/update_utilisateur.php',
            $data
        );
    }

    public function deleteUtilisateur(int $id): array
    {
        return $this->proxyPost(
            config('planteurs.api_base').'/delete_utilisateur.php',
            ['id' => $id]
        );
    }

    public function updatePhoto(int $userId, UploadedFile $file): array
    {
        $response = Http::timeout(60)
            ->attach('photo', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post(config('planteurs.api_base').'/update_photo_utilisateur.php', [
                'user_id' => $userId,
            ]);

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Réponse invalide de l\'API distante.');
        }

        if (! $response->successful() || ($json['success'] ?? false) !== true) {
            throw new RuntimeException($json['error'] ?? $json['message'] ?? 'Erreur lors de la mise à jour de la photo.');
        }

        return $json;
    }

    private function enrichAvatar(array $user): array
    {
        $bucket = (string) config('planteurs.minio_bucket', 'planteurs');
        $avatar = $user['avatar'] ?? '';

        if ($avatar !== '' && $avatar !== 'default.jpg' && empty($user['avatar_url'])) {
            $presigned = $this->minio->getPresignedUrl($avatar, 3600, $bucket);
            if ($presigned) {
                $user['avatar_url'] = $presigned;
            }
        }

        return $user;
    }

    private function proxyGet(string $url, array $query): array
    {
        $response = Http::timeout(60)
            ->acceptJson()
            ->get($url, $query);

        if (! $response->successful()) {
            throw new RuntimeException('Erreur lors de la récupération des données (API distante).');
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
            throw new RuntimeException($json['error'] ?? $json['message'] ?? 'Erreur API collecteurs.');
        }

        return $json;
    }
}
