<?php

namespace App\Services;

use App\Models\CaisseParametre;
use App\Models\Utilisateur;
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

    public function getMontantUtilisable(): float
    {
        $param = CaisseParametre::instance();

        return min((float) $param->montant_utilisable, $this->getSolde());
    }

    public function resume(): array
    {
        $solde = $this->getSolde();
        $utilisable = $this->getMontantUtilisable();

        return [
            'solde_caisse' => $solde,
            'montant_utilisable' => $utilisable,
            'montant_reserve' => max(0, $solde - $utilisable),
        ];
    }

    public function augmenterMontantUtilisable(float $nouveauMontant, Utilisateur $utilisateur): void
    {
        $solde = $this->getSolde();
        $param = CaisseParametre::instance();
        $actuel = (float) $param->montant_utilisable;

        if ($nouveauMontant > $solde) {
            throw new \InvalidArgumentException(
                'Le montant utilisable ne peut pas dépasser le solde actuel ('
                .number_format($solde, 0, ',', ' ')
                .' FCFA).',
            );
        }

        if ($nouveauMontant < $actuel) {
            throw new \InvalidArgumentException('Le montant utilisable ne peut être qu\'augmenté.');
        }

        if (abs($nouveauMontant - $actuel) < 0.01) {
            return;
        }

        DB::transaction(function () use ($param, $nouveauMontant, $actuel, $utilisateur): void {
            $param->update(['montant_utilisable' => $nouveauMontant]);

            DB::table('caisse_utilisable_logs')->insert([
                'montant_avant' => $actuel,
                'montant_apres' => $nouveauMontant,
                'id_utilisateur' => $utilisateur->id,
                'created_at' => now(),
            ]);
        });
    }

    public function debiterUtilisable(float $montant): void
    {
        if ($montant <= 0) {
            return;
        }

        $param = CaisseParametre::instance();
        $actuel = (float) $param->montant_utilisable;

        if ($actuel < $montant) {
            throw new \InvalidArgumentException(
                'Montant utilisable insuffisant. Disponible : '
                .number_format($actuel, 0, ',', ' ')
                .' FCFA (solde total : '
                .number_format($this->getSolde(), 0, ',', ' ')
                .' FCFA).',
            );
        }

        $param->update(['montant_utilisable' => $actuel - $montant]);
    }

    public function crediterUtilisable(float $montant): void
    {
        if ($montant <= 0) {
            return;
        }

        $param = CaisseParametre::instance();
        $nouveau = min((float) $param->montant_utilisable + $montant, $this->getSolde());

        $param->update(['montant_utilisable' => $nouveau]);
    }
}
