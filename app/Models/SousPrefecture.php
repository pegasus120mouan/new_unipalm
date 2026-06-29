<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'departement_id',
    'code',
    'nom',
    'geojson',
])]
class SousPrefecture extends Model
{
    protected $table = 'sous_prefectures';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_id', 'id');
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'sous_prefecture_id', 'id');
    }
}
