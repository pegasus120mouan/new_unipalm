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
        'code_financement',
        'id_agent',
        'montant',
        'motif',
        'statut',
        'date_financement',
    ];

    public const STATUT_VALIDE = 'valide';

    public const STATUT_EN_ATTENTE = 'en_attente';

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

    public function getCodeAfficheAttribute(): string
    {
        return (string) ($this->code_financement ?: $this->Numero_financement);
    }

    public function isAdvance(): bool
    {
        return (float) $this->montant > 0;
    }

    public function isRepayment(): bool
    {
        return (float) $this->montant < 0;
    }

    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isValide(): bool
    {
        return $this->statut === self::STATUT_VALIDE || $this->statut === null || $this->statut === '';
    }

    public function scopeValides($query)
    {
        return $query->where(function ($q) {
            $q->where('statut', self::STATUT_VALIDE)
                ->orWhereNull('statut')
                ->orWhere('statut', '');
        });
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }
}
