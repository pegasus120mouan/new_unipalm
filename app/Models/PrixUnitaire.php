<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_usine',
    'prix',
    'date_debut',
    'date_fin',
])]
class PrixUnitaire extends Model
{
    protected $table = 'prix_unitaires';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function usine(): BelongsTo
    {
        return $this->belongsTo(Usine::class, 'id_usine', 'id_usine');
    }
}
