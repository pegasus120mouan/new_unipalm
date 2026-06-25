<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pret extends Model
{
    protected $table = 'prets';

    protected $primaryKey = 'id_pret';

    public $timestamps = true;

    protected $fillable = [
        'id_agent',
        'montant_initial',
        'montant_restant',
        'date_octroi',
        'date_echeance',
        'statut',
        'motif',
    ];

    protected function casts(): array
    {
        return [
            'date_octroi' => 'date',
            'date_echeance' => 'date',
            'montant_initial' => 'decimal:2',
            'montant_restant' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function montantRembourse(): float
    {
        return max((float) $this->montant_initial - (float) ($this->montant_restant ?? 0), 0);
    }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'en_cours' => 'En cours',
            'termine', 'solde', 'soldé' => 'Terminé',
            'annule', 'annulé' => 'Annulé',
            default => (string) $this->statut,
        };
    }

    public function statutBadgeClass(): string
    {
        return match ($this->statut) {
            'en_cours' => 'bg-warning text-dark',
            'termine', 'solde', 'soldé' => 'bg-success',
            'annule', 'annulé' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
