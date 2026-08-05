<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'matricule_vehicule',
    'type_vehicule',
])]
class Vehicule extends Model
{
    protected $table = 'vehicules';

    protected $primaryKey = 'vehicules_id';

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'vehicule_id', 'vehicules_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match (strtolower(trim((string) $this->type_vehicule))) {
            'moto' => 'Moto',
            'tricycle' => 'Tricyclette',
            'voiture' => 'Voiture',
            default => ucfirst((string) $this->type_vehicule),
        };
    }

    public static function normalizeMatricule(string $matricule): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($matricule)) ?? '');
    }

    public function normalizedMatricule(): string
    {
        return self::normalizeMatricule((string) $this->matricule_vehicule);
    }
}
