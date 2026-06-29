<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Groupe;
use Illuminate\Support\Str;

class AgentService
{
    public function formatPersonName(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    public function generatePin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function generateNumeroAgent(int $groupeId, string $nom, string $prenom): string
    {
        $groupe = Groupe::query()->findOrFail($groupeId);
        $annee = date('y');
        $codeGroupe = Str::upper(Str::substr($groupe->nom, 0, 3));
        $initiales = Str::upper(Str::substr($nom, 0, 1).Str::substr($prenom, 0, 1));
        $prefix = "AGT-{$annee}-{$codeGroupe}-{$initiales}";

        $dernierNumero = Agent::query()
            ->where('numero_agent', 'like', $prefix.'%')
            ->orderByDesc('numero_agent')
            ->value('numero_agent');

        $sequence = $dernierNumero
            ? ((int) Str::substr($dernierNumero, -2)) + 1
            : 1;

        do {
            $numero = $prefix.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $exists = Agent::query()->where('numero_agent', $numero)->exists();
            $sequence++;
        } while ($exists);

        return $numero;
    }
}
