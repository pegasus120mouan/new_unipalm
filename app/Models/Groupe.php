<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'prenoms', 'token', 'login', 'password'])]
#[Hidden(['password'])]
class Groupe extends Model
{
    protected $table = 'chef_equipe';

    protected $primaryKey = 'id_chef';

    public $timestamps = false;

    public function getFullNameAttribute(): string
    {
        return trim("{$this->nom} {$this->prenoms}");
    }

    public function hasCredentials(): bool
    {
        return trim((string) ($this->login ?? '')) !== ''
            && trim((string) ($this->password ?? '')) !== '';
    }

    public function checkPassword(string $plainPassword): bool
    {
        return hash('sha256', $plainPassword) === (string) $this->password;
    }

    public function setPasswordFromPlain(string $plainPassword): void
    {
        $this->password = hash('sha256', $plainPassword);
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
