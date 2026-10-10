<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'origine',
        'id_cca',
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

    public function cca(): BelongsTo
    {
        return $this->belongsTo(Cca::class, 'id_cca', 'id_cca');
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(PretRemboursement::class, 'id_pret', 'id_pret')
            ->orderBy('date_remboursement')
            ->orderBy('id_remboursement');
    }

    public function origineLabel(): string
    {
        return match ($this->origine) {
            'caisse' => 'Caisse',
            'cca' => 'CCA'.($this->cca?->nom ? ' — '.$this->cca->nom : ''),
            default => '—',
        };
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
