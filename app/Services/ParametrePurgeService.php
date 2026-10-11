<?php

namespace App\Services;

use App\Models\Banque;
use App\Models\CaisseParametre;
use App\Models\Cca;
use App\Models\CcaMouvement;
use App\Models\Financement;
use App\Models\Pret;
use App\Models\PretRemboursement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParametrePurgeService
{
    public function __construct(
        private readonly CaisseService $caisseService,
    ) {}

    /**
     * @return array{financements: int, prets: int, banques: int, mouvements_caisse: int, solde_caisse: float}
     */
    public function compteurs(): array
    {
        return [
            'financements' => Financement::query()->count(),
            'prets' => Pret::query()->count(),
            'banques' => Banque::query()->count(),
            'mouvements_caisse' => (int) DB::table('transactions')->count(),
            'solde_caisse' => $this->caisseService->getSolde(),
            'utilisable_caisse' => $this->caisseService->getMontantUtilisable(),
            'ccas' => Cca::query()->count(),
            'mouvements_cca' => CcaMouvement::query()->count(),
            'solde_cca' => (float) Cca::query()->sum('solde'),
        ];
    }

    public function viderFinancements(): int
    {
        return (int) DB::transaction(fn () => Financement::query()->delete());
    }

    public function viderPrets(): int
    {
        return (int) DB::transaction(function () {
            PretRemboursement::query()->delete();

            return Pret::query()->delete();
        });
    }

    public function viderBanques(): int
    {
        return (int) DB::transaction(fn () => Banque::query()->delete());
    }

    public function viderCaisse(): int
    {
        return (int) DB::transaction(function () {
            $nombre = DB::table('transactions')->delete();

            if (Schema::hasTable('caisse_utilisable_logs')) {
                DB::table('caisse_utilisable_logs')->delete();
            }

            CaisseParametre::query()->update(['montant_utilisable' => 0]);

            return $nombre;
        });
    }

    public function viderCcas(): int
    {
        return (int) DB::transaction(function () {
            $nombre = CcaMouvement::query()->delete();
            Cca::query()->update(['solde' => 0]);

            return $nombre;
        });
    }
}
