<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'nom_usine',
    'latitude',
    'longitude',
    'created_by',
])]
class Usine extends Model
{
    protected $table = 'usines';

    protected $primaryKey = 'id_usine';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && ((float) $this->latitude !== 0.0 || (float) $this->longitude !== 0.0);
    }

    public function requiresBismar(): bool
    {
        $nom = preg_replace('/\s+/', ' ', Str::upper(Str::ascii(trim((string) $this->nom_usine))));

        return $nom === 'PALMCI BOUBO';
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_usine', 'id_usine');
    }

    public function entrees(): HasMany
    {
        return $this->hasMany(UsineEntree::class, 'id_usine', 'id_usine');
    }

    public function financements(): HasMany
    {
        return $this->hasMany(UsineFinancement::class, 'id_usine', 'id_usine');
    }
}
