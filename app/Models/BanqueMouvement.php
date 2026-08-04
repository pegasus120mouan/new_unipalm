<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_banque',
    'type_mouvement',
    'montant',
    'libelle',
    'reference',
    'id_utilisateur',
    'id_usine',
    'solde_apres',
    'date_mouvement',
])]
class BanqueMouvement extends Model
{
    public const TYPE_SOLDE_INITIAL = 'solde_initial';

    public const TYPE_MANUEL = 'approvisionnement_manuel';

    public const TYPE_CAISSE = 'approvisionnement_caisse';

    public const TYPE_USINE = 'paiement_usine';

    public const TYPE_FINANCEMENT_USINE = 'financement_usine';

    protected $table = 'banque_mouvements';

    protected $primaryKey = 'id_mouvement';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'solde_apres' => 'decimal:2',
            'date_mouvement' => 'datetime',
        ];
    }

    public function banque(): BelongsTo
    {
        return $this->belongsTo(Banque::class, 'id_banque', 'id_banque');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function usine(): BelongsTo
    {
        return $this->belongsTo(Usine::class, 'id_usine', 'id_usine');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type_mouvement) {
            self::TYPE_SOLDE_INITIAL => 'Solde initial',
            self::TYPE_MANUEL => 'Approvisionnement manuel',
            self::TYPE_CAISSE => 'Vers caisse',
            self::TYPE_USINE => 'Paiement usine',
            self::TYPE_FINANCEMENT_USINE => 'Financement usine',
            default => $this->type_mouvement,
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type_mouvement) {
            self::TYPE_SOLDE_INITIAL => 'secondary',
            self::TYPE_MANUEL => 'success',
            self::TYPE_CAISSE => 'warning',
            self::TYPE_USINE => 'info',
            self::TYPE_FINANCEMENT_USINE => 'primary',
            default => 'light text-dark',
        };
    }
}
