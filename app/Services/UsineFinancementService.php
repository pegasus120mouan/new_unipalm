<?php

namespace App\Services;

use App\Models\Usine;
use App\Models\UsineFinancement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UsineFinancementService
{
    /**
     * @param  array{search?: string}  $filters
     */
    public function paginatedUsineSummaries(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Usine::query()
            ->select([
                'usines.id_usine',
                'usines.nom_usine',
                DB::raw('COALESCE(SUM(f.montant), 0) AS total_financement'),
                DB::raw('COALESCE(COUNT(f.id), 0) AS nombre_financements'),
            ])
            ->leftJoin('usine_financements as f', 'usines.id_usine', '=', 'f.id_usine')
            ->groupBy('usines.id_usine', 'usines.nom_usine')
            ->orderBy('usines.nom_usine');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where('usines.nom_usine', 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{total_financement: float, nombre_financements: int}
     */
    public function statsForUsine(int $idUsine): array
    {
        $row = UsineFinancement::query()
            ->where('id_usine', $idUsine)
            ->selectRaw('COALESCE(SUM(montant), 0) AS total_financement, COUNT(*) AS nombre_financements')
            ->first();

        return [
            'total_financement' => (float) ($row->total_financement ?? 0),
            'nombre_financements' => (int) ($row->nombre_financements ?? 0),
        ];
    }

    public function solde(int $idUsine): float
    {
        return (float) UsineFinancement::query()
            ->where('id_usine', $idUsine)
            ->sum('montant');
    }

    /**
     * Déduit un montant du solde de financement (écriture négative).
     *
     * @throws \InvalidArgumentException
     */
    public function deduire(
        Usine $usine,
        float $montant,
        string $dateFinancement,
        ?string $referencePaiement = null,
        ?\App\Models\Utilisateur $utilisateur = null,
        string $motif = 'Déduction paiement livraisons',
    ): UsineFinancement {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant à déduire doit être supérieur à 0.');
        }

        $solde = $this->solde((int) $usine->id_usine);
        if ($montant > $solde + 0.009) {
            throw new \InvalidArgumentException(
                'Le montant dépasse le financement disponible ('
                .number_format($solde, 0, ',', ' ')
                .' FCFA).'
            );
        }

        return UsineFinancement::query()->create([
            'code_financement' => UsineFinancement::genererCode(),
            'id_usine' => $usine->id_usine,
            'montant' => -abs($montant),
            'motif' => $motif,
            'date_financement' => $dateFinancement,
            'id_banque' => null,
            'mode_paiement' => 'Financement',
            'reference_paiement' => $referencePaiement,
            'id_utilisateur' => $utilisateur?->id,
        ]);
    }

    /**
     * @param  array{date_debut?: string|null, date_fin?: string|null}  $filters
     */
    public function historyQuery(int $idUsine, array $filters = []): Builder
    {
        $query = UsineFinancement::query()
            ->with(['banque', 'utilisateur'])
            ->where('id_usine', $idUsine)
            ->orderByDesc('date_financement')
            ->orderByDesc('id');

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_financement', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_financement', '<=', $filters['date_fin']);
        }

        return $query;
    }

    /**
     * @param  array{date_debut?: string|null, date_fin?: string|null}  $filters
     */
    public function paginatedHistory(int $idUsine, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->historyQuery($idUsine, $filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, UsineFinancement>
     */
    public function recentForUsine(int $idUsine, int $limit = 10): Collection
    {
        return UsineFinancement::query()
            ->with('banque')
            ->where('id_usine', $idUsine)
            ->orderByDesc('date_financement')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
