<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'code_banque',
    'nom_banque',
    'numero_compte',
    'solde',
    'actif',
])]
class Banque extends Model
{
    protected $table = 'banques';

    protected $primaryKey = 'id_banque';

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
            'actif' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function nomBanque(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : self::normalizeNom($value),
            set: fn (?string $value) => $value === null ? null : self::normalizeNom($value),
        );
    }

    public static function normalizeNom(string $nom): string
    {
        return mb_strtoupper(trim($nom));
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(BanqueMouvement::class, 'id_banque', 'id_banque');
    }

    public static function genererCodeBanque(): string
    {
        $annee = date('Y');
        $mois = date('m');
        $pattern = "BNQ-{$annee}{$mois}-%";

        $dernierNumero = (int) static::query()
            ->where('code_banque', 'like', $pattern)
            ->max(DB::raw('CAST(SUBSTRING_INDEX(code_banque, "-", -1) AS UNSIGNED)'));

        return sprintf('BNQ-%s%s-%04d', $annee, $mois, $dernierNumero + 1);
    }
}
