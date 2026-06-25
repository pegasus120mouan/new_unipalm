<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Financement extends Model
{
    protected $table = 'financement';

    protected $primaryKey = 'Numero_financement';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'Numero_financement',
        'id_agent',
        'montant',
        'motif',
        'date_financement',
    ];

    protected function casts(): array
    {
        return [
            'date_financement' => 'datetime',
            'montant' => 'decimal:2',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function isAdvance(): bool
    {
        return (float) $this->montant > 0;
    }

    public function isRepayment(): bool
    {
        return (float) $this->montant < 0;
    }
}
