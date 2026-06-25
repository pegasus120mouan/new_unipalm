<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Pret;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PretService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function agentSummariesQuery(array $filters): Builder
    {
        $query = Agent::query()
            ->select([
                'agents.id_agent',
                'agents.nom',
                'agents.prenom',
                DB::raw('COALESCE(SUM(p.montant_initial), 0) AS montant_initial'),
                DB::raw('COALESCE(SUM(p.montant_initial - COALESCE(p.montant_restant, 0)), 0) AS montant_rembourse'),
                DB::raw('COALESCE(SUM(COALESCE(p.montant_restant, 0)), 0) AS solde_restant'),
                DB::raw('COALESCE(COUNT(p.id_pret), 0) AS nombre_prets'),
            ])
            ->join('prets as p', 'agents.id_agent', '=', 'p.id_agent')
            ->whereNull('agents.date_suppression');

        $this->applySummaryFilters($query, $filters);

        return $query
            ->groupBy('agents.id_agent', 'agents.nom', 'agents.prenom')
            ->orderByDesc('solde_restant')
            ->orderBy('agents.nom')
            ->orderBy('agents.prenom');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedAgentSummaries(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->agentSummariesQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  iterable<object>  $summaries
     * @return array{total_prets: float, total_remboursements: float, solde_global: float, nb_agents: int}
     */
    public function globalStats(iterable $summaries): array
    {
        $totalPrets = 0.0;
        $totalRemboursements = 0.0;
        $soldeGlobal = 0.0;
        $nbAgents = 0;

        foreach ($summaries as $summary) {
            if ((float) $summary->montant_initial > 0 || (float) $summary->montant_rembourse > 0) {
                $nbAgents++;
            }
            $totalPrets += (float) $summary->montant_initial;
            $totalRemboursements += (float) $summary->montant_rembourse;
            $soldeGlobal += (float) $summary->solde_restant;
        }

        return [
            'total_prets' => $totalPrets,
            'total_remboursements' => $totalRemboursements,
            'solde_global' => $soldeGlobal,
            'nb_agents' => $nbAgents,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Pret>
     */
    public function detailedList(array $filters): Collection
    {
        $query = Pret::query()
            ->with('agent')
            ->orderByDesc('id_pret');

        $this->applyListFilters($query, $filters);

        return $query->get();
    }

    /**
     * @return array{montant_initial: float, montant_rembourse: float, solde_restant: float, nombre_prets: int}
     */
    public function statsForAgent(int $agentId): array
    {
        $rows = Pret::query()->where('id_agent', $agentId)->get();

        $montantInitial = 0.0;
        $soldeRestant = 0.0;

        foreach ($rows as $pret) {
            $montantInitial += (float) $pret->montant_initial;
            $soldeRestant += (float) ($pret->montant_restant ?? 0);
        }

        return [
            'montant_initial' => $montantInitial,
            'montant_rembourse' => max($montantInitial - $soldeRestant, 0),
            'solde_restant' => $soldeRestant,
            'nombre_prets' => $rows->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedAgentPrets(int $agentId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Pret::query()
            ->where('id_agent', $agentId)
            ->orderByDesc('date_octroi')
            ->orderByDesc('id_pret');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('id_pret', 'like', '%'.$search.'%')
                    ->orWhere('motif', 'like', '%'.$search.'%')
                    ->orWhere('montant_initial', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_octroi', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_octroi', '<=', $filters['date_fin']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(int $agentId, float $montantInitial, ?string $motif = null): Pret
    {
        return Pret::create([
            'id_agent' => $agentId,
            'montant_initial' => $montantInitial,
            'montant_restant' => $montantInitial,
            'date_octroi' => now()->toDateString(),
            'date_echeance' => null,
            'statut' => 'en_cours',
            'motif' => $motif,
        ]);
    }

    /**
     * @param  Builder<Agent>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySummaryFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['agent_id'])) {
            $query->where('agents.id_agent', (int) $filters['agent_id']);
        }

        if (! empty($filters['date_debut']) && ! empty($filters['date_fin'])) {
            $query->whereBetween(DB::raw('DATE(p.date_octroi)'), [
                $filters['date_debut'],
                $filters['date_fin'],
            ]);
        } elseif (! empty($filters['date_debut'])) {
            $query->whereDate('p.date_octroi', '>=', $filters['date_debut']);
        } elseif (! empty($filters['date_fin'])) {
            $query->whereDate('p.date_octroi', '<=', $filters['date_fin']);
        }

        if (! empty($filters['statut'])) {
            $query->where('p.statut', $filters['statut']);
        }
    }

    /**
     * @param  Builder<Pret>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyListFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('id_pret', 'like', '%'.$search.'%')
                    ->orWhere('motif', 'like', '%'.$search.'%')
                    ->orWhereHas('agent', function (Builder $agentQuery) use ($search) {
                        $agentQuery->whereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ['%'.$search.'%']);
                    });
            });
        }

        if (! empty($filters['agent_id'])) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if (! empty($filters['date_debut']) && ! empty($filters['date_fin'])) {
            $query->whereBetween(DB::raw('DATE(date_octroi)'), [
                $filters['date_debut'],
                $filters['date_fin'],
            ]);
        } elseif (! empty($filters['date_debut'])) {
            $query->whereDate('date_octroi', '>=', $filters['date_debut']);
        } elseif (! empty($filters['date_fin'])) {
            $query->whereDate('date_octroi', '<=', $filters['date_fin']);
        }

        if (! empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }
    }
}
