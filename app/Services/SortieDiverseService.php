<?php

namespace App\Services;

use App\Models\SortieDiverse;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;

class SortieDiverseService
{
    public function __construct(
        private readonly CaisseService $caisseService,
    ) {}

    public function stats(): array
    {
        $row = SortieDiverse::query()
            ->selectRaw('COUNT(*) as total_sorties, COALESCE(SUM(montant), 0) as total_montant')
            ->first();

        return [
            'total_sorties' => (int) ($row->total_sorties ?? 0),
            'total_montant' => (float) ($row->total_montant ?? 0),
            'solde_caisse' => $this->caisseService->getSolde(),
        ];
    }

    public function create(float $montant, string $motifs, Utilisateur $utilisateur): SortieDiverse
    {
        return DB::transaction(function () use ($montant, $motifs, $utilisateur): SortieDiverse {
            $utilisable = $this->caisseService->getMontantUtilisable();

            if ($montant > $utilisable) {
                throw new \InvalidArgumentException(
                    'Montant utilisable insuffisant. Disponible : '
                    .number_format($utilisable, 0, ',', ' ')
                    .' FCFA.',
                );
            }

            $numero = SortieDiverse::genererNumeroSortie();
            $soldeActuel = $this->caisseService->getSolde();
            $nouveauSolde = $soldeActuel - $montant;

            $sortie = SortieDiverse::query()->create([
                'numero_sorties' => $numero,
                'montant' => $montant,
                'date_sortie' => now(),
                'motifs' => $motifs,
            ]);

            DB::table('transactions')->insert([
                'type_transaction' => 'paiement',
                'montant' => $montant,
                'date_transaction' => now(),
                'motifs' => 'Sortie diverse: '.$motifs,
                'id_utilisateur' => $utilisateur->id,
                'solde' => $nouveauSolde,
                'numero_cheque' => null,
            ]);

            $this->caisseService->debiterUtilisable($montant);

            return $sortie;
        });
    }

    public function delete(SortieDiverse $sortie, Utilisateur $utilisateur): void
    {
        DB::transaction(function () use ($sortie, $utilisateur): void {
            $montant = (float) $sortie->montant;
            $numero = $sortie->numero_sorties;
            $motifs = $sortie->motifs;

            $sortie->delete();

            $soldeActuel = $this->caisseService->getSolde();
            $nouveauSolde = $soldeActuel + $montant;

            DB::table('transactions')->insert([
                'type_transaction' => 'approvisionnement',
                'montant' => $montant,
                'date_transaction' => now(),
                'motifs' => 'Annulation sortie diverse '.$numero.' : '.$motifs,
                'id_utilisateur' => $utilisateur->id,
                'solde' => $nouveauSolde,
                'numero_cheque' => null,
            ]);

            $this->caisseService->crediterUtilisable($montant);
        });
    }
}
