<?php

namespace App\Services;

use App\Models\DemandeAvanceGestCamions;
use App\Models\PaiementAgentGestCamions;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DemandeAvancePaymentService
{
    public function __construct(
        private readonly CaisseService $caisseService,
        private readonly FinancementService $financementService,
    ) {}

    /**
     * Paie une demande d'avance API : débite la caisse Unipalm,
     * crédite le financement agent et réduit le solde du chef de groupe.
     */
    public function payer(DemandeAvanceGestCamions $demande, Utilisateur $caissier): void
    {
        if (! $demande->isEnAttente()) {
            throw new InvalidArgumentException('Cette demande d\'avance n\'est plus en attente.');
        }

        $montant = (float) $demande->montant;
        if ($montant <= 0) {
            throw new InvalidArgumentException('Montant de la demande invalide.');
        }

        $montantUtilisable = $this->caisseService->getMontantUtilisable();
        if ($montant > $montantUtilisable) {
            throw new InvalidArgumentException(
                'Montant utilisable insuffisant. Disponible : '
                .number_format($montantUtilisable, 0, ',', ' ')
                .' FCFA.'
            );
        }

        DB::transaction(function () use ($demande, $caissier, $montant): void {
            /** @var DemandeAvanceGestCamions $locked */
            $locked = DemandeAvanceGestCamions::query()
                ->whereKey($demande->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isEnAttente()) {
                throw new InvalidArgumentException('Cette demande d\'avance n\'est plus en attente.');
            }

            $soldeCaisse = $this->caisseService->getSolde();
            if ($montant > $this->caisseService->getMontantUtilisable()) {
                throw new InvalidArgumentException('Montant utilisable insuffisant.');
            }

            $soldeApres = $soldeCaisse - $montant;

            DB::table('transactions')->insertGetId([
                'type_transaction' => 'paiement',
                'montant' => $montant,
                'date_transaction' => now(),
                'motifs' => 'Avance agent #'.$locked->id_agent
                    .' — demande #'.$locked->id
                    .' (gest-camions)',
                'source' => 'Avance gest-camions',
                'id_utilisateur' => $caissier->id,
                'solde' => $soldeApres,
                'numero_cheque' => null,
            ]);

            $this->caisseService->debiterUtilisable($montant);

            $paiement = PaiementAgentGestCamions::query()->create([
                'id_agent' => $locked->id_agent,
                'id_bordereau' => null,
                'montant' => (int) round($montant),
                'date_paiement' => now()->toDateString(),
                'mode_paiement' => $locked->mode_paiement ?: 'Caisse groupe',
                'caisse' => 'api',
                'reference' => $locked->reference,
                'commentaire' => $locked->commentaire ?: ('Avance API #'.$locked->id),
                'numero_recu' => now()->format('Ymd').sprintf('%04d', random_int(1, 9999)),
            ]);

            $payeur = trim((string) ($caissier->full_name ?? trim(($caissier->nom ?? '').' '.($caissier->prenoms ?? ''))));
            if ($payeur === '') {
                $payeur = 'caissier Unipalm';
            }

            // AVANCE-PAIEMENT-{id} conserve l'idempotence sync gest-camions.
            $this->financementService->create(
                (int) $locked->id_agent,
                $montant,
                'Avance Unipalm payé par '.$payeur.' — AVANCE-PAIEMENT-'.$paiement->id,
            );

            $locked->update([
                'statut' => 'payee',
                'paiement_agent_id' => $paiement->id,
                'payee_at' => now(),
                'payee_par' => $payeur,
            ]);
        });
    }
}
