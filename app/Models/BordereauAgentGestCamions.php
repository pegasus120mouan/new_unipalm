<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bordereaux agents générés dans gest-camions (table bordereaux_agent).
 */
class BordereauAgentGestCamions extends Model
{
    protected $connection = 'gest_camions';

    protected $table = 'bordereaux_agent';

    protected $guarded = [];

    protected $casts = [
        'date_generation' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'poids_total' => 'decimal:2',
        'fiches_data' => 'array',
    ];

    public function getResteAPayerAttribute(): float
    {
        return max(0, (float) $this->montant_total - (float) ($this->montant_paye ?? 0));
    }

    public function getNombreFichesAttribute(): int
    {
        $fiches = $this->fiches_data;

        return is_array($fiches) ? count($fiches) : 0;
    }

    public function paymentStatusKey(): string
    {
        $total = (float) $this->montant_total;
        $paye = (float) ($this->montant_paye ?? 0);
        $reste = $this->reste_a_payer;

        if ($total > 0 && $reste <= 0 && $paye > 0) {
            return 'solde';
        }

        if ($paye > 0 && $reste > 0) {
            return 'en_cours';
        }

        return 'non_paye';
    }
}
