<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code_pont',
    'nom_pont',
    'id_type_pont',
    'id_agent',
    'latitude',
    'longitude',
    'gerant',
    'cooperatif',
    'statut',
])]
class PontBascule extends Model
{
    protected $table = 'pont_bascule';

    protected $primaryKey = 'id_pont';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function typePont(): BelongsTo
    {
        return $this->belongsTo(TypePont::class, 'id_type_pont', 'id_type_pont');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function gerantLabel(): string
    {
        return $this->agent?->full_name ?? $this->gerant ?? '—';
    }

    public function isActive(): bool
    {
        return $this->statut === 'Actif';
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && ((float) $this->latitude !== 0.0 || (float) $this->longitude !== 0.0);
    }
}
