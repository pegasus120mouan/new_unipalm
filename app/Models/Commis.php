<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nom',
    'prenoms',
    'contact',
    'code_pin',
    'id_agent',
    'id_pont',
    'cree_par',
    'date_modification',
    'date_suppression',
])]
class Commis extends Model
{
    protected $table = 'commis';

    protected $primaryKey = 'id_commis';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date_ajout' => 'datetime',
            'date_modification' => 'datetime',
            'date_suppression' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->nom} {$this->prenoms}");
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function pont(): BelongsTo
    {
        return $this->belongsTo(PontBascule::class, 'id_pont', 'id_pont');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'cree_par', 'id');
    }
}
