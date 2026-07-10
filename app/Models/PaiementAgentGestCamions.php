<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementAgentGestCamions extends Model
{
    protected $connection = 'gest_camions';

    protected $table = 'paiements_agent';

    protected $fillable = [
        'id_agent',
        'id_bordereau',
        'montant',
        'date_paiement',
        'mode_paiement',
        'reference',
        'commentaire',
        'numero_recu',
    ];

    protected $casts = [
        'date_paiement' => 'date',
        'montant' => 'integer',
    ];
}
