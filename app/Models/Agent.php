<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'numero_agent',
    'nom',
    'prenom',
    'contact',
    'id_chef',
    'sous_groupe',
    'cree_par',
    'code_pin',
    'avatar',
    'date_modification',
    'date_suppression',
])]
class Agent extends Model
{
    public const SOUS_GROUPE_PARTICULIER = 'particulier';

    public const SOUS_GROUPE_PROFESSIONNEL = 'professionnel';

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

    public static function sousGroupes(): array
    {
        return [
            self::SOUS_GROUPE_PARTICULIER => 'Particuliers',
            self::SOUS_GROUPE_PROFESSIONNEL => 'Professionnels',
        ];
    }

    public function sousGroupeLabel(): string
    {
        return self::sousGroupes()[$this->sous_groupe] ?? '—';
    }

    public function isParticulier(): bool
    {
        return $this->sous_groupe === self::SOUS_GROUPE_PARTICULIER;
    }

    public function isProfessionnel(): bool
    {
        return $this->sous_groupe === self::SOUS_GROUPE_PROFESSIONNEL;
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

    public function ponts(): HasMany
    {
        return $this->hasMany(PontBascule::class, 'id_agent', 'id_agent');
    }

    public function commis(): HasMany
    {
        return $this->hasMany(Commis::class, 'id_agent', 'id_agent')
            ->whereNull('date_suppression');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AgentDocument::class, 'id_agent', 'id_agent');
    }

    public function photoIdentiteDocument(): HasOne
    {
        return $this->hasOne(AgentDocument::class, 'id_agent', 'id_agent')
            ->where('type', AgentDocument::TYPE_PHOTO_IDENTITE);
    }
}
