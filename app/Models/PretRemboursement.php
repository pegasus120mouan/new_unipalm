<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PretRemboursement extends Model
{
    protected $table = 'pret_remboursements';

    protected $primaryKey = 'id_remboursement';

    protected $fillable = [
        'id_pret',
        'id_mouvement',
        'montant',
        'date_remboursement',
        'libelle',
        'id_utilisateur',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_remboursement' => 'datetime',
        ];
    }

    public function pret(): BelongsTo
    {
        return $this->belongsTo(Pret::class, 'id_pret', 'id_pret');
    }
}
