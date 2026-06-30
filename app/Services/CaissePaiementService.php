<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\DemandeSortie;
use App\Models\SortieDiverse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CaissePaiementService
{
    public function __construct(
        private readonly FinancementService $financementService,
    ) {}

    /**
     * @param  array<int>  $agentIds
     * @return array<int, array<string, mixed>>
     */
    public function financementStatsForAgents(array $agentIds): array
    {
        $result = [];

        foreach (array_unique(array_filter($agentIds)) as $agentId) {
            $result[(int) $agentId] = $this->financementService->statsForAgent((int) $agentId);
        }

        return $result;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Agent>
     */
    public function agentsForFilter()
    {
        return Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id_agent', 'nom', 'prenom']);
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedDemandesAPayer(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = DemandeSortie::query()
            ->with(['approbateur'])
            ->whereIn('statut', [DemandeSortie::STATUT_APPROUVE, DemandeSortie::STATUT_PAYE])
            ->orderByDesc('date_demande')
            ->orderByDesc('id_demande');

        if (($filters['search'] ?? '') !== '') {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term): void {
                $q->where('numero_demande', 'like', '%'.$term.'%')
                    ->orWhere('motif', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_demande', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_demande', '<=', $filters['date_fin']);
        }

        if (($filters['statut'] ?? '') === 'non_solde') {
            $query->where(function (Builder $q): void {
                $q->whereNull('montant_reste')
                    ->orWhere('montant_reste', '>', 0);
            });
        } elseif (($filters['statut'] ?? '') === 'solde') {
            $query->where('montant_reste', '<=', 0)
                ->where('montant_payer', '>', 0);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedSortiesDiverses(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = SortieDiverse::query()
            ->orderByDesc('date_sortie')
            ->orderByDesc('id_sorties');

        if (($filters['search'] ?? '') !== '') {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term): void {
                $q->where('numero_sorties', 'like', '%'.$term.'%')
                    ->orWhere('motifs', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_sortie', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_sortie', '<=', $filters['date_fin']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
