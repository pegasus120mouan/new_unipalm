<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\PontBascule;

class PontBasculeService
{
    public function generateCode(): string
    {
        $lastCode = PontBascule::query()
            ->whereRaw("code_pont REGEXP '^UNIPALM-PB-[0-9]{4}-CI$'")
            ->orderByDesc('id_pont')
            ->value('code_pont');

        if ($lastCode) {
            $parts = explode('-', $lastCode);
            $nextNumber = ((int) ($parts[2] ?? 0)) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('UNIPALM-PB-%04d-CI', $nextNumber);
    }

    /**
     * @return array{total: int, actifs: int, inactifs: int, avec_cooperative: int}
     */
    public function stats(): array
    {
        $total = PontBascule::query()->count();

        return [
            'total' => $total,
            'actifs' => PontBascule::query()->where('statut', 'Actif')->count(),
            'inactifs' => PontBascule::query()->where('statut', 'Inactif')->count(),
            'avec_cooperative' => PontBascule::query()
                ->whereNotNull('cooperatif')
                ->where('cooperatif', '!=', '')
                ->count(),
        ];
    }

    public function create(array $data): PontBascule
    {
        $agent = Agent::query()->findOrFail((int) $data['id_agent']);

        return PontBascule::query()->create([
            'code_pont' => $this->generateCode(),
            'nom_pont' => trim($data['nom_pont']),
            'id_type_pont' => $data['id_type_pont'] ?? null,
            'id_region' => $data['id_region'] ?? null,
            'id_departement' => $data['id_departement'] ?? null,
            'id_sous_prefecture' => $data['id_sous_prefecture'] ?? null,
            'id_village' => $data['id_village'] ?? null,
            'id_agent' => $agent->id_agent,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'gerant' => $agent->full_name,
            'cooperatif' => filled($data['cooperatif'] ?? null) ? trim($data['cooperatif']) : null,
            'statut' => $data['statut'] ?? 'Actif',
        ]);
    }

    public function update(PontBascule $pont, array $data): PontBascule
    {
        $payload = [
            'code_pont' => trim($data['code_pont']),
            'nom_pont' => trim($data['nom_pont']),
            'id_type_pont' => $data['id_type_pont'] ?? null,
            'id_region' => $data['id_region'] ?? null,
            'id_departement' => $data['id_departement'] ?? null,
            'id_sous_prefecture' => $data['id_sous_prefecture'] ?? null,
            'id_village' => $data['id_village'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'cooperatif' => filled($data['cooperatif'] ?? null) ? trim($data['cooperatif']) : null,
            'statut' => $data['statut'] ?? 'Actif',
        ];

        if (filled($data['id_agent'] ?? null)) {
            $agent = Agent::query()->findOrFail((int) $data['id_agent']);
            $payload['id_agent'] = $agent->id_agent;
            $payload['gerant'] = $agent->full_name;
        }

        $pont->update($payload);

        return $pont->fresh();
    }
}
