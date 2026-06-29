<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'region_id',
    'code',
    'nom',
    'geojson',
])]
class Departement extends Model
{
    protected $table = 'departements';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function sousPrefectures(): HasMany
    {
        return $this->hasMany(SousPrefecture::class, 'departement_id', 'id');
    }
}
