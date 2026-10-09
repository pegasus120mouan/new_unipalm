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

    public const TYPE_REMBOURSEMENT = 'remboursement';

    public const SOURCE_BANQUE = 'banque';

    public const SOURCE_CAISSE = 'caisse';

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
}
