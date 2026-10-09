<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'date_debut',
    'date_fin',
    'prix',
    'created_by',
])]
class PrimeBismar extends Model
{
    protected $table = 'primes_bismar';

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'created_by', 'id');
    }

    public function scopeOverlapping($query, string $dateDebut, string $dateFin)
    {
        return $query
            ->whereDate('date_debut', '<=', $dateFin)
            ->whereDate('date_fin', '>=', $dateDebut);
    }
}
