<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Groupe extends Model
{
    protected $table = 'chef_equipe';

    protected $primaryKey = 'id_chef';

    public $timestamps = false;

    public function getFullNameAttribute(): string
    {
        return trim("{$this->nom} {$this->prenoms}");
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'id_chef', 'id_chef');
    }

    public function agentsParticuliers(): HasMany
    {
        return $this->agents()->where('sous_groupe', Agent::SOUS_GROUPE_PARTICULIER);
    }

    public function agentsProfessionnels(): HasMany
    {
        return $this->agents()->where('sous_groupe', Agent::SOUS_GROUPE_PROFESSIONNEL);
    }
}
