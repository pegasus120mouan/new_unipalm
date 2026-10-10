<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_cca',
    'type_mouvement',
    'montant',
    'source',
    'id_banque',
    'mode_paiement',
    'numero_cheque',
    'solde_apres',
    'libelle',
    'id_transaction',
    'id_utilisateur',
    'date_mouvement',
])]
class CcaMouvement extends Model
{
    public const TYPE_PRET_CAISSE = 'pret_caisse';

    public const TYPE_PRET_AGENT = 'pret_agent';

    public const TYPE_REMBOURSEMENT = 'remboursement';

    /**
     * @return list<string>
     */
    public static function typesAvance(): array
    {
        return [self::TYPE_PRET_CAISSE, self::TYPE_PRET_AGENT];
    }

    public const SOURCE_BANQUE = 'banque';

    public const SOURCE_CAISSE = 'caisse';

    public const SOURCE_PRET_AGENT = 'pret_agent';

    public const MODE_VIREMENT = 'Virement';

    public const MODE_CHEQUE = 'Chèque';

    protected $table = 'cca_mouvements';

    protected $primaryKey = 'id_mouvement';

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'solde_apres' => 'decimal:2',
            'date_mouvement' => 'datetime',
        ];
    }

    public function cca(): BelongsTo
    {
        return $this->belongsTo(Cca::class, 'id_cca', 'id_cca');
    }

    public function banque(): BelongsTo
    {
        return $this->belongsTo(Banque::class, 'id_banque', 'id_banque');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id');
    }

    public function typeLabel(): string
    {
        return match ($this->type_mouvement) {
            self::TYPE_PRET_CAISSE => 'Prêt à la caisse',
            self::TYPE_PRET_AGENT => 'Prêt agent',
            self::TYPE_REMBOURSEMENT => 'Remboursement',
            default => (string) $this->type_mouvement,
        };
    }

    public function diminueLaDette(): bool
    {
        return $this->type_mouvement === self::TYPE_REMBOURSEMENT;
    }
}
