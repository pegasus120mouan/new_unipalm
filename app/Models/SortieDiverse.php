<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'numero_sorties',
    'montant',
    'date_sortie',
    'motifs',
])]
class SortieDiverse extends Model
{
    protected $table = 'sorties_diverses';

    protected $primaryKey = 'id_sorties';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date_sortie' => 'datetime',
            'montant' => 'decimal:2',
        ];
    }

    public static function genererNumeroSortie(): string
    {
        $annee = date('Y');
        $mois = date('m');
        $pattern = "SD-{$annee}-{$mois}-%";

        $dernierNumero = static::query()
            ->where('numero_sorties', 'like', $pattern)
            ->orderByDesc('id_sorties')
            ->value('numero_sorties');

        $sequence = 1;

        if ($dernierNumero) {
            $parts = explode('-', $dernierNumero);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('SD-%s-%s-%03d', $annee, $mois, $sequence);
    }
}
