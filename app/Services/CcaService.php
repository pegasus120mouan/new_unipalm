<?php

namespace App\Services;

use App\Models\Banque;
use App\Models\Cca;
use App\Models\CcaMouvement;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;

class CcaService
{
    public function __construct(
        private readonly ApprovisionnementService $approvisionnementService,
        private readonly CaisseService $caisseService,
        private readonly BanqueService $banqueService,
    ) {}

    /**
     * Le CCA prête de l'argent à la caisse : la caisse est créditée et la dette envers l'associé augmente.
     */
    public function pretCaisse(Cca $cca, float $montant, Utilisateur $utilisateur): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        DB::transaction(function () use ($cca, $montant, $utilisateur): void {
            $cca = Cca::query()->lockForUpdate()->findOrFail($cca->id_cca);

            $idTransaction = $this->approvisionnementService->createManual(
                $montant,
                ApprovisionnementService::SOURCE_CCA_PREFIX.$cca->nom,
                $utilisateur,
            );

            $nouveauSolde = $cca->resteARembourser() + $montant;
            $cca->update(['solde' => $nouveauSolde]);

            CcaMouvement::create([
                'id_cca' => $cca->id_cca,
                'type_mouvement' => CcaMouvement::TYPE_PRET_CAISSE,
                'montant' => $montant,
                'source' => CcaMouvement::SOURCE_CAISSE,
                'solde_apres' => $nouveauSolde,
                'libelle' => 'Prêt pour approvisionnement caisse',
                'id_transaction' => $idTransaction,
                'id_utilisateur' => $utilisateur->id,
                'date_mouvement' => now(),
            ]);
        });
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function rembourser(
        Cca $cca,
        float $montant,
        string $source,
        ?Banque $banque,
        ?string $modePaiement,
        ?string $numeroCheque,
        Utilisateur $utilisateur,
    ): void {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        DB::transaction(function () use ($cca, $montant, $source, $banque, $modePaiement, $numeroCheque, $utilisateur): void {
            $cca = Cca::query()->lockForUpdate()->findOrFail($cca->id_cca);
            $reste = $cca->resteARembourser();

            if ($montant > $reste + 0.009) {
                throw new \InvalidArgumentException(
                    'Le montant dépasse le reste à rembourser à '.$cca->nom.' ('
                    .number_format($reste, 0, ',', ' ')
                    .' FCFA).',
                );
            }

            $libelle = 'Remboursement CCA '.$cca->nom;
            $idTransaction = null;

            if ($source === CcaMouvement::SOURCE_BANQUE) {
                $reference = $modePaiement === CcaMouvement::MODE_CHEQUE
                    ? 'Chèque n° '.$numeroCheque
                    : CcaMouvement::MODE_VIREMENT;

                $this->banqueService->remboursementCca($banque, $montant, $libelle, $reference, $utilisateur);
            } else {
                $this->caisseService->debiterUtilisable($montant);

                $idTransaction = (int) DB::table('transactions')->insertGetId([
                    'type_transaction' => 'paiement',
                    'montant' => $montant,
                    'date_transaction' => now(),
                    'motifs' => $libelle,
                    'source' => ApprovisionnementService::SOURCE_CCA_PREFIX.$cca->nom,
                    'id_utilisateur' => $utilisateur->id,
                    'solde' => $this->caisseService->getSolde() - $montant,
                    'numero_cheque' => null,
                ]);
            }

            $nouveauSolde = $reste - $montant;
            $cca->update(['solde' => $nouveauSolde]);

            CcaMouvement::create([
                'id_cca' => $cca->id_cca,
                'type_mouvement' => CcaMouvement::TYPE_REMBOURSEMENT,
                'montant' => $montant,
                'source' => $source,
                'id_banque' => $source === CcaMouvement::SOURCE_BANQUE ? $banque?->id_banque : null,
                'mode_paiement' => $source === CcaMouvement::SOURCE_BANQUE ? $modePaiement : null,
                'numero_cheque' => $source === CcaMouvement::SOURCE_BANQUE && $modePaiement === CcaMouvement::MODE_CHEQUE ? $numeroCheque : null,
                'solde_apres' => $nouveauSolde,
                'libelle' => $libelle,
                'id_transaction' => $idTransaction,
                'id_utilisateur' => $utilisateur->id,
                'date_mouvement' => now(),
            ]);
        });
    }
}
