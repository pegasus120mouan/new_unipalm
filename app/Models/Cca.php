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
            ->withSum(['mouvements as total_prete' => fn ($q) => $q->where('type_mouvement', CcaMouvement::TYPE_PRET_CAISSE)], 'montant')
            ->withSum(['mouvements as total_rembourse' => fn ($q) => $q->where('type_mouvement', CcaMouvement::TYPE_REMBOURSEMENT)], 'montant');
    }

    public function resteARembourser(): float
    {
        $totaux = CcaMouvement::query()
            ->where('id_cca', $this->id_cca)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) AS prete,
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) AS rembourse
            ', [CcaMouvement::TYPE_PRET_CAISSE, CcaMouvement::TYPE_REMBOURSEMENT])
            ->first();

        return max(0, (float) $totaux->prete - (float) $totaux->rembourse);
    }
}
