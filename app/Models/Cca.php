<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nom',
    'solde',
    'created_by',
])]
class Cca extends Model
{
    protected $table = 'ccas';

    protected $primaryKey = 'id_cca';

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
        ];
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(CcaMouvement::class, 'id_cca', 'id_cca');
    }

    public function scopeWithTotaux(Builder $query): Builder
    {
        return $query
            ->withSum(['mouvements as total_prete' => fn ($q) => $q->whereIn('type_mouvement', CcaMouvement::typesAvance())], 'montant')
            ->withSum(['mouvements as total_rembourse' => fn ($q) => $q->where('type_mouvement', CcaMouvement::TYPE_REMBOURSEMENT)], 'montant')
            ->withSum(['mouvements as total_pret_caisse' => fn ($q) => $q->where('type_mouvement', CcaMouvement::TYPE_PRET_CAISSE)], 'montant')
            ->withSum(['mouvements as total_rembourse_societe' => fn ($q) => $q
                ->where('type_mouvement', CcaMouvement::TYPE_REMBOURSEMENT)
                ->whereIn('source', [CcaMouvement::SOURCE_BANQUE, CcaMouvement::SOURCE_CAISSE])], 'montant');
    }

    /**
     * Part du prêt caisse encore due. La banque et la caisse ne règlent que cette part.
     */
    public function restePretCaisse(): float
    {
        if ($this->offsetExists('total_pret_caisse') || $this->offsetExists('total_rembourse_societe')) {
            return max(0, (float) $this->total_pret_caisse - (float) $this->total_rembourse_societe);
        }

        $prete = (float) $this->mouvements()
            ->where('type_mouvement', CcaMouvement::TYPE_PRET_CAISSE)
            ->sum('montant');
        $rembourse = (float) $this->mouvements()
            ->where('type_mouvement', CcaMouvement::TYPE_REMBOURSEMENT)
            ->whereIn('source', [CcaMouvement::SOURCE_BANQUE, CcaMouvement::SOURCE_CAISSE])
            ->sum('montant');

        return max(0, $prete - $rembourse);
    }

    public function resteARembourser(): float
    {
        $typesAvance = CcaMouvement::typesAvance();
        $avancePlaceholders = implode(', ', array_fill(0, count($typesAvance), '?'));

        $totaux = CcaMouvement::query()
            ->where('id_cca', $this->id_cca)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type_mouvement IN ('.$avancePlaceholders.') THEN montant ELSE 0 END), 0) AS prete,
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) AS rembourse
            ', [...$typesAvance, CcaMouvement::TYPE_REMBOURSEMENT])
            ->first();

        return max(0, (float) $totaux->prete - (float) $totaux->rembourse);
    }
}
