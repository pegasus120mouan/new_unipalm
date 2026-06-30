<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'numero_demande',
    'date_demande',
    'montant',
    'motif',
    'motif_refus',
    'statut',
    'date_approbation',
    'approuve_par',
    'date_paiement',
    'paye_par',
    'montant_payer',
    'montant_reste',
])]
class DemandeSortie extends Model
{
    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_APPROUVE = 'approuve';

    public const STATUT_REJETE = 'rejete';

    public const STATUT_PAYE = 'paye';

    protected $table = 'demande_sortie';

    protected $primaryKey = 'id_demande';

    protected function casts(): array
    {
        return [
            'date_demande' => 'datetime',
            'date_approbation' => 'datetime',
            'date_paiement' => 'datetime',
            'montant' => 'decimal:2',
            'montant_payer' => 'decimal:2',
            'montant_reste' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function approbateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'approuve_par');
    }

    public function payeur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'paye_par');
    }

    public function isEditable(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isApprovable(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE && $this->date_approbation === null;
    }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_APPROUVE => 'Approuvé',
            self::STATUT_REJETE => 'Rejeté',
            self::STATUT_PAYE => 'Payé',
            default => (string) $this->statut,
        };
    }

    public function getStatutBadgeClassAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'warning',
            self::STATUT_APPROUVE => 'success',
            self::STATUT_REJETE => 'danger',
            self::STATUT_PAYE => 'info',
            default => 'secondary',
        };
    }

    public static function genererNumeroDemande(): string
    {
        $annee = date('Y');
        $mois = date('m');
        $pattern = "DEM-{$annee}{$mois}-%";

        $dernierNumero = (int) static::query()
            ->where('numero_demande', 'like', $pattern)
            ->max(DB::raw('CAST(SUBSTRING_INDEX(numero_demande, "-", -1) AS UNSIGNED)'));

        $numero = ((int) $dernierNumero) + 1;

        return sprintf('DEM-%s%s-%04d', $annee, $mois, $numero);
    }
}
