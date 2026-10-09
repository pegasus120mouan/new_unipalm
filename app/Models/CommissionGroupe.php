<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'date_debut',
    'date_fin',
    'montant',
    'created_by',
])]
class CommissionGroupe extends Model
{
    protected $table = 'commissions_groupe';

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
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
