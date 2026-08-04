<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsineFinancement extends Model
{
    protected $table = 'usine_financements';

    protected $fillable = [
        'code_financement',
        'id_usine',
        'montant',
        'motif',
        'date_financement',
        'id_banque',
        'mode_paiement',
        'reference_paiement',
        'id_utilisateur',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_financement' => 'date',
        ];
    }

    public function usine(): BelongsTo
    {
        return $this->belongsTo(Usine::class, 'id_usine', 'id_usine');
    }

    public function banque(): BelongsTo
    {
        return $this->belongsTo(Banque::class, 'id_banque', 'id_banque');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function getCodeAfficheAttribute(): string
    {
        return (string) ($this->code_financement ?: '#'.$this->id);
    }

    public static function genererCode(): string
    {
        $annee = date('Y');
        $mois = date('m');
        $pattern = "FIN-US-{$annee}{$mois}-%";

        $dernier = static::query()
            ->where('code_financement', 'like', $pattern)
            ->orderByDesc('code_financement')
            ->value('code_financement');

        $numero = 1;
        if (is_string($dernier) && preg_match('/-(\d+)$/', $dernier, $matches)) {
            $numero = (int) $matches[1] + 1;
        }

        return sprintf('FIN-US-%s%s-%04d', $annee, $mois, $numero);
    }
}
