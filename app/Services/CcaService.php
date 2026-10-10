<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Banque;
use App\Models\Cca;
use App\Models\CcaMouvement;
use App\Models\Pret;
use App\Models\PretRemboursement;
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
     * Agents qui ont encore un prêt financé par un CCA.
     *
     * @return array<int, list<array{id_agent: int, nom: string, reste: float}>>
     */
    public function agentsPretesParCca(): array
    {
        $prets = Pret::query()
            ->with('agent')
            ->where('origine', 'cca')
            ->whereNotNull('id_cca')
            ->where('montant_restant', '>', 0)
            ->orderBy('id_pret')
            ->get();

        $groupes = [];

        foreach ($prets as $pret) {
            $ccaId = (int) $pret->id_cca;
            $agentId = (int) $pret->id_agent;
            $groupes[$ccaId][$agentId] ??= [
                'id_agent' => $agentId,
                'nom' => $pret->agent?->full_name ?: 'Agent',
                'reste' => 0.0,
            ];
            $groupes[$ccaId][$agentId]['reste'] += (float) $pret->montant_restant;
        }

        return array_map(
            fn (array $agents) => array_values($agents),
            $groupes,
        );
    }

    /**
     * Le prêt agent est financé par le CCA : le montant est reversé sur son compte.
     * La caisse n'est pas mouvementée.
     */
    public function reverserPretAgent(Cca $cca, float $montant, string $agentNom, Utilisateur $utilisateur): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        $cca = Cca::query()->lockForUpdate()->findOrFail($cca->id_cca);
        $nouveauSolde = $cca->resteARembourser() + $montant;
        $cca->update(['solde' => $nouveauSolde]);

        CcaMouvement::create([
            'id_cca' => $cca->id_cca,
            'type_mouvement' => CcaMouvement::TYPE_PRET_AGENT,
            'montant' => $montant,
            'solde_apres' => $nouveauSolde,
            'libelle' => 'Prêt agent reversé : '.$agentNom,
            'id_utilisateur' => $utilisateur->id,
            'date_mouvement' => now(),
        ]);
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
        ?int $idAgent = null,
    ): void {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        DB::transaction(function () use ($cca, $montant, $source, $banque, $modePaiement, $numeroCheque, $utilisateur, $idAgent): void {
            $cca = Cca::query()->lockForUpdate()->findOrFail($cca->id_cca);
            $reste = $cca->resteARembourser();
            $depuisSociete = $source !== CcaMouvement::SOURCE_PRET_AGENT;
            $plafond = $depuisSociete ? $cca->restePretCaisse() : $reste;

            if ($montant > $plafond + 0.009) {
                throw new \InvalidArgumentException($depuisSociete
                    ? 'La banque et la caisse ne remboursent que le prêt accordé à la caisse ('
                        .number_format($plafond, 0, ',', ' ')
                        .' FCFA). Le prêt d\'un agent se rembourse depuis sa fiche.'
                    : 'Le montant dépasse le reste à rembourser à '.$cca->nom.' ('
                        .number_format($reste, 0, ',', ' ')
                        .' FCFA).');
            }

            $libelle = 'Remboursement CCA '.$cca->nom;
            $idTransaction = null;
            $applicationsPret = [];

            if ($source === CcaMouvement::SOURCE_BANQUE) {
                $reference = $modePaiement === CcaMouvement::MODE_CHEQUE
                    ? 'Chèque n° '.$numeroCheque
                    : CcaMouvement::MODE_VIREMENT;

                $this->banqueService->remboursementCca($banque, $montant, $libelle, $reference, $utilisateur);
            } elseif ($source === CcaMouvement::SOURCE_PRET_AGENT) {
                $reglement = $this->solderPretAgent($cca, (int) $idAgent, $montant);
                $libelle = 'Remboursement via prêt agent : '.$reglement['nom'];
                $applicationsPret = $reglement['applications'];
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

            $mouvement = CcaMouvement::create([
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

            foreach ($applicationsPret as $application) {
                PretRemboursement::create([
                    'id_pret' => $application['id_pret'],
                    'id_mouvement' => $mouvement->id_mouvement,
                    'montant' => $application['montant'],
                    'date_remboursement' => $mouvement->date_mouvement,
                    'libelle' => $libelle,
                    'id_utilisateur' => $utilisateur->id,
                ]);
            }
        });
    }

    /**
     * @return array{nom: string, applications: list<array{id_pret: int, montant: float}>}
     */
    private function solderPretAgent(Cca $cca, int $idAgent, float $montant): array
    {
        $prets = Pret::query()
            ->where('id_cca', $cca->id_cca)
            ->where('id_agent', $idAgent)
            ->where('origine', 'cca')
            ->where('montant_restant', '>', 0)
            ->orderBy('date_octroi')
            ->orderBy('id_pret')
            ->lockForUpdate()
            ->get();

        $disponible = (float) $prets->sum(fn (Pret $pret) => (float) $pret->montant_restant);

        if ($prets->isEmpty() || $montant > $disponible + 0.009) {
            throw new \InvalidArgumentException(
                'Le montant dépasse le prêt restant de cet agent ('
                .number_format($disponible, 0, ',', ' ')
                .' FCFA).',
            );
        }

        $reste = $montant;
        $applications = [];

        foreach ($prets as $pret) {
            if ($reste <= 0.009) {
                break;
            }

            $du = (float) $pret->montant_restant;
            $applique = min($du, $reste);
            $nouveau = round($du - $applique, 2);
            $pret->montant_restant = $nouveau <= 0.009 ? 0 : $nouveau;
            if ((float) $pret->montant_restant <= 0) {
                $pret->statut = 'termine';
            }
            $pret->save();
            $applications[] = [
                'id_pret' => (int) $pret->id_pret,
                'montant' => $applique,
            ];
            $reste -= $applique;
        }

        return [
            'nom' => Agent::query()->find($idAgent)?->full_name ?: 'Agent',
            'applications' => $applications,
        ];
    }
}
