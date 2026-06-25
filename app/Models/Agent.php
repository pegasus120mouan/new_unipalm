<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'numero_agent',
    'nom',
    'prenom',
    'contact',
    'id_chef',
    'cree_par',
    'code_pin',
    'avatar',
    'date_modification',
    'date_suppression',
])]
class Agent extends Model
{
    protected $table = 'agents';

    protected $primaryKey = 'id_agent';

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
        return trim("{$this->nom} {$this->prenom}");
    }

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(Groupe::class, 'id_chef', 'id_chef');
    }

    public function financements(): HasMany
    {
        return $this->hasMany(Financement::class, 'id_agent', 'id_agent');
    }

    public function prets(): HasMany
    {
        return $this->hasMany(Pret::class, 'id_agent', 'id_agent');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'cree_par', 'id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_agent', 'id_agent');
    }

    public function bordereaux(): HasMany
    {
        return $this->hasMany(Bordereau::class, 'id_agent', 'id_agent');
    }
}
