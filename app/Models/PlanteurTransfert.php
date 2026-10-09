<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'planteur_id',
    'numero_fiche',
    'planteur_nom',
    'ancien_collecteur_id',
    'ancien_collecteur_nom',
    'nouveau_collecteur_id',
    'nouveau_collecteur_nom',
    'motif',
    'nb_champs',
    'superficie_totale',
    'champs',
    'id_utilisateur',
    'utilisateur_nom',
])]
class PlanteurTransfert extends Model
{
    protected $table = 'planteur_transferts';

    protected function casts(): array
    {
        return [
            'champs' => 'array',
            'superficie_totale' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
