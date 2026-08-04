<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_usine',
    'date_entree',
    'poids_usine',
    'prix_unitaire',
])]
class UsineEntree extends Model
{
    protected $table = 'usine_entrees';

    protected function casts(): array
    {
        return [
            'date_entree' => 'date',
            'poids_usine' => 'decimal:2',
            'prix_unitaire' => 'decimal:2',
        ];
    }

    public function usine(): BelongsTo
    {
        return $this->belongsTo(Usine::class, 'id_usine', 'id_usine');
    }

    public function montant(): float
    {
        return round((float) $this->poids_usine * (float) $this->prix_unitaire, 2);
    }
}
