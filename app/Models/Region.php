<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'district_id',
    'code',
    'nom',
    'geojson',
])]
class Region extends Model
{
    protected $table = 'regions';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function departements(): HasMany
    {
        return $this->hasMany(Departement::class, 'region_id', 'id');
    }

    public function ponts(): HasMany
    {
        return $this->hasMany(PontBascule::class, 'id_region', 'id');
    }
}
