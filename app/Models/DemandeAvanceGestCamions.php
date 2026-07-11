<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Demandes d'avance créées dans gest-camions (table demandes_avance).
 */
class DemandeAvanceGestCamions extends Model
{
    protected $connection = 'gest_camions';

    protected $table = 'demandes_avance';

    protected $guarded = [];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_demande' => 'date',
        'payee_at' => 'datetime',
    ];

    public function isEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }
}
