<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'numero_bordereau',
    'id_agent',
    'date_debut',
    'date_fin',
    'poids_total',
    'montant_total',
    'montant_payer',
    'montant_reste',
    'statut_bordereau',
    'date_validation_boss',
    'date_paie',
    'created_by',
])]
class BordereauBismar extends Model
{
    protected $table = 'bordereaux_bismar';

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_validation_boss' => 'datetime',
            'date_paie' => 'datetime',
            'poids_total' => 'decimal:2',
            'montant_total' => 'decimal:2',
            'montant_payer' => 'decimal:2',
            'montant_reste' => 'decimal:2',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'numero_bordereau_bismar', 'numero_bordereau');
    }

    public function isValidated(): bool
    {
        return $this->date_validation_boss !== null;
    }
}
