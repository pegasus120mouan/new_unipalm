<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code_pont',
    'nom_pont',
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
