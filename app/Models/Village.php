<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sous_prefecture_id',
    'nom',
    'latitude',
    'longitude',
])]
class Village extends Model
{
    protected $table = 'villages';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function sousPrefecture(): BelongsTo
    {
        return $this->belongsTo(SousPrefecture::class, 'sous_prefecture_id', 'id');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && ((float) $this->latitude !== 0.0 || (float) $this->longitude !== 0.0);
    }
}
