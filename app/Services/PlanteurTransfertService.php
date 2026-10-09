<?php

namespace App\Services;

use App\Models\PlanteurTransfert;
use App\Models\Utilisateur;
use Illuminate\Support\Collection;
use RuntimeException;

class PlanteurTransfertService
{
    public function __construct(
        private readonly PlanteurApiService $planteurApi,
        private readonly CollecteurApiService $collecteurApi,
    ) {}

    /**
     * @return list<array{id: int, nom: string, role: string, zone: string}>
     */
    public function collecteurs(): array
    {
        $json = $this->collecteurApi->getUtilisateurs();
        $data = $json['data'] ?? [];
        $users = is_array($data['utilisateurs'] ?? null) ? $data['utilisateurs'] : (array_is_list($data) ? $data : []);

        $list = [];
        foreach ($users as $user) {
            if (! is_array($user) || (int) ($user['id'] ?? 0) <= 0) {
                continue;
            }
            $list[] = [
                'id' => (int) $user['id'],
                'nom' => $this->personName($user),
                'role' => $this->label($user['role'] ?? null),
                'zone' => $this->label($user['zone'] ?? null),
            ];
        }

        usort($list, fn (array $a, array $b): int => strcasecmp($a['nom'], $b['nom']));

        return $list;
    }

    private function label(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['nom'] ?? $value['libelle'] ?? $value['name']
                ?? implode(', ', array_filter($value, 'is_scalar'));
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Change le collecteur du planteur sur l'API distante. La fiche, les exploitations
     * et les champs restent rattachés au planteur ; l'état des champs au moment du
     * transfert est conservé dans l'historique local.
     */
    public function transferer(int $planteurId, int $nouveauCollecteurId, ?string $motif, Utilisateur $auteur): PlanteurTransfert
    {
        $payload = $this->planteurApi->getChampsForPlanteur($planteurId);
        $planteur = $payload['data']['planteur'];
        $champs = $payload['data']['champs'];

        $ancienCollecteurId = (int) ($planteur['collecteur_id'] ?? $planteur['collecteur']['id'] ?? 0);
        if ($ancienCollecteurId === $nouveauCollecteurId) {
            throw new RuntimeException('Le planteur est déjà rattaché à ce collecteur.');
        }

        $nouveauCollecteur = $this->collecteurApi->getUtilisateur($nouveauCollecteurId);
        if ($nouveauCollecteur === null) {
            throw new RuntimeException('Collecteur de destination introuvable.');
        }

        $this->planteurApi->post($this->updatePayload($planteur, $nouveauCollecteurId));

        $verifie = $this->planteurApi->getChampsForPlanteur($planteurId)['data']['planteur'];
        if ((int) ($verifie['collecteur_id'] ?? $verifie['collecteur']['id'] ?? 0) !== $nouveauCollecteurId) {
            throw new RuntimeException(
                'L\'API planteurs n\'a pas pris en compte le changement de collecteur : '
                .'update_planteur.php doit accepter le champ « collecteur_id » sur le serveur API.'
            );
        }

        $snapshot = array_map(fn (array $champ): array => [
            'id' => $champ['id'] ?? null,
            'numero_champ' => (string) ($champ['numero_champ'] ?? ''),
            'type_culture' => (string) ($champ['type_culture'] ?? ''),
            'superficie_ha' => (float) ($champ['superficie_ha'] ?: 0),
            'parcelles_count' => (int) ($champ['parcelles_count'] ?? 0),
            'exploitation_numero' => $champ['exploitation_numero'] ?? null,
            'village' => (string) ($champ['village'] ?? ''),
            'created_at' => $champ['created_at'] ?? null,
        ], $champs);

        return PlanteurTransfert::query()->create([
            'planteur_id' => $planteurId,
            'numero_fiche' => (string) ($planteur['numero_fiche'] ?? ''),
            'planteur_nom' => trim((string) ($planteur['nom_prenoms'] ?? '')),
            'ancien_collecteur_id' => $ancienCollecteurId ?: null,
            'ancien_collecteur_nom' => is_array($planteur['collecteur'] ?? null) ? $this->personName($planteur['collecteur']) : null,
            'nouveau_collecteur_id' => $nouveauCollecteurId,
            'nouveau_collecteur_nom' => $this->personName($nouveauCollecteur),
            'motif' => filled($motif) ? trim($motif) : null,
            'nb_champs' => count($snapshot),
            'superficie_totale' => round(array_sum(array_column($snapshot, 'superficie_ha')), 2),
            'champs' => $snapshot,
            'id_utilisateur' => $auteur->id,
            'utilisateur_nom' => $auteur->full_name,
        ]);
    }

    /**
     * @return Collection<int, PlanteurTransfert>
     */
    public function historique(int $planteurId): Collection
    {
        return PlanteurTransfert::query()
            ->where('planteur_id', $planteurId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * update_planteur.php remplace la fiche : on renvoie toutes les valeurs actuelles
     * pour ne modifier que le collecteur.
     *
     * @param  array<string, mixed>  $planteur
     * @return array<string, mixed>
     */
    private function updatePayload(array $planteur, int $collecteurId): array
    {
        $exploitation = is_array($planteur['exploitation'] ?? null) ? $planteur['exploitation'] : [];

        return [
            'action' => 'update_planteur',
            'id' => (int) $planteur['id'],
            'nom_prenoms' => (string) ($planteur['nom_prenoms'] ?? ''),
            'telephone' => (string) ($planteur['telephone'] ?? ''),
            'piece_identite' => (string) ($planteur['piece_identite'] ?? ''),
            'date_naissance' => (string) ($planteur['date_naissance'] ?? ''),
            'lieu_naissance' => (string) ($planteur['lieu_naissance'] ?? ''),
            'situation_matrimoniale' => (string) ($planteur['situation_matrimoniale'] ?? ''),
            'nombre_enfants' => $planteur['nombre_enfants'] ?? '',
            'collecteur_id' => $collecteurId,
            'exploitation' => [
                'region' => (string) ($exploitation['region'] ?? ''),
                'sous_prefecture_village' => (string) ($exploitation['sous_prefecture_village'] ?? ''),
                'village' => (string) ($exploitation['village'] ?? ''),
                'latitude' => (string) ($exploitation['latitude'] ?? ''),
                'longitude' => (string) ($exploitation['longitude'] ?? ''),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $person
     */
    private function personName(array $person): string
    {
        $nom = trim(((string) ($person['nom'] ?? '')).' '.((string) ($person['prenoms'] ?? '')));

        return $nom !== '' ? $nom : trim((string) ($person['nom_complet'] ?? $person['login'] ?? ''));
    }
}
