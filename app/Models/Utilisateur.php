<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['nom', 'prenoms', 'contact', 'login', 'avatar', 'password', 'role', 'statut_compte'])]
#[Hidden(['password'])]
class Utilisateur extends Authenticatable
{
    protected $table = 'utilisateurs';

    protected function casts(): array
    {
        return [
            'statut_compte' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->nom} {$this->prenoms}");
    }

    public function checkPassword(string $plainPassword): bool
    {
        return hash('sha256', $plainPassword) === $this->password;
    }

    public function isActive(): bool
    {
        return (bool) $this->statut_compte;
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Administrateur',
            'operateur' => 'Opérateur',
            'validateur' => 'Validateur',
            'caissiere' => 'Caissière',
            'directeur' => 'Directeur',
            default => ucfirst((string) $this->role),
        };
    }

    public static function roleOptions(): array
    {
        return [
            'admin' => 'Administrateur',
            'operateur' => 'Opérateur',
            'validateur' => 'Validateur',
            'caissiere' => 'Caissière',
            'directeur' => 'Directeur',
        ];
    }
}
