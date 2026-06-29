<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'nom',
    'geojson',
])]
class Region extends Model
{
    protected $table = 'regions';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function ponts(): HasMany
    {
        return $this->hasMany(PontBascule::class, 'id_region', 'id');
    }
}
