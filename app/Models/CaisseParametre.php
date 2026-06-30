<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaisseParametre extends Model
{
    protected $table = 'caisse_parametres';

    protected $fillable = [
        'montant_utilisable',
    ];

    protected function casts(): array
    {
        return [
            'montant_utilisable' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate([], [
            'montant_utilisable' => 0,
        ]);
    }
}
