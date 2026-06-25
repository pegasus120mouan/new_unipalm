<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CaisseService
{
    public function getSolde(): float
    {
        $row = DB::table('transactions')
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type_transaction = 'approvisionnement' THEN montant
                WHEN type_transaction = 'paiement' THEN -montant
                ELSE 0
            END), 0) AS solde")
            ->first();

        return (float) ($row->solde ?? 0);
    }
}
