<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['libelle'])]
class TypePont extends Model
{
    protected $table = 'types_pont';

    protected $primaryKey = 'id_type_pont';

    public function ponts(): HasMany
    {
        return $this->hasMany(PontBascule::class, 'id_type_pont', 'id_type_pont');
    }

    public function pontsCount(): int
    {
        return $this->ponts()->count();
    }

    public static function normalizeLibelle(string $libelle): string
    {
        $libelle = trim(preg_replace('/\s+/u', ' ', $libelle));

        if ($libelle === '') {
            return '';
        }

        $libelle = preg_replace('/(\d)\s*(m[eè]tre?s?)/ui', '$1 mètres', $libelle);
        $libelle = preg_replace('/\b(m[eè]tre?s?)\b/ui', 'mètres', $libelle);
        $libelle = preg_replace('/(\d)\s+(mètres)/ui', '$1 $2', $libelle);

        return trim($libelle);
    }

    protected function libelle(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => self::normalizeLibelle($value ?? ''),
        );
    }
}
