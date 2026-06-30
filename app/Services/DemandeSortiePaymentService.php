<?php

namespace App\Services;

use App\Models\DemandeSortie;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DemandeSortiePaymentService
{
    public function __construct(
        private readonly CaisseService $caisseService,
    ) {}

    public function pay(DemandeSortie $demande, Utilisateur $caissier, float $montant): void
    {
        DB::transaction(function () use ($demande, $caissier, $montant): void {
            /** @var DemandeSortie $locked */
            $locked = DemandeSortie::query()
                ->whereKey($demande->id_demande)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->statut, [DemandeSortie::STATUT_APPROUVE, DemandeSortie::STATUT_PAYE], true)) {
                throw new InvalidArgumentException('Cette demande n\'est pas approuvée.');
            }

            $montantTotal = (float) $locked->montant;
            $montantPrecedent = (float) ($locked->montant_payer ?? 0);
            $reste = $locked->montant_reste !== null
                ? (float) $locked->montant_reste
                : max($montantTotal - $montantPrecedent, 0);

            if ($reste <= 0) {
                throw new InvalidArgumentException('Cette demande est déjà soldée.');
            }

            if ($montant <= 0) {
                throw new InvalidArgumentException('Le montant doit être supérieur à 0.');
            }

            if ($montant > $reste) {
                throw new InvalidArgumentException('Le montant dépasse le reste à payer.');
            }

            $montantUtilisable = $this->caisseService->getMontantUtilisable();
            if ($montant > $montantUtilisable) {
                throw new InvalidArgumentException(
                    'Montant utilisable insuffisant. Disponible : '
                    .number_format($montantUtilisable, 0, '', ' ')
                    .' FCFA'
                );
            }

            $soldeActuel = $this->caisseService->getSolde();
            $nouveauSolde = $soldeActuel - $montant;
            $nomCaissier = trim($caissier->nom.' '.$caissier->prenoms);

            DB::table('transactions')->insert([
                'type_transaction' => 'paiement',
                'montant' => $montant,
                'date_transaction' => now(),
                'motifs' => 'Paiement de la demande '.$locked->numero_demande.' - Par '.$nomCaissier,
                'id_utilisateur' => $caissier->id,
                'solde' => $nouveauSolde,
                'numero_cheque' => null,
            ]);

            $nouveauMontantPaye = $montantPrecedent + $montant;
            $nouveauMontantReste = max($montantTotal - $nouveauMontantPaye, 0);
            $nouveauStatut = $nouveauMontantReste <= 0
                ? DemandeSortie::STATUT_PAYE
                : DemandeSortie::STATUT_APPROUVE;

            $locked->update([
                'montant_payer' => $nouveauMontantPaye,
                'montant_reste' => $nouveauMontantReste,
                'statut' => $nouveauStatut,
                'date_paiement' => now(),
                'paye_par' => $caissier->id,
            ]);

            $this->caisseService->debiterUtilisable($montant);
        });
    }
}
