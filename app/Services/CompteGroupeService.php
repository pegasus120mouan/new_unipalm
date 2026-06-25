<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Bordereau;
use App\Models\Groupe;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompteGroupeService
{
    /**
     * @param  array<string, string>  $filters
     */
    public function groupeSummariesQuery(array $filters): Builder
    {
        $query = Groupe::query()
            ->select([
                'chef_equipe.id_chef',
                'chef_equipe.nom',
                'chef_equipe.prenoms',
                DB::raw("TRIM(CONCAT(chef_equipe.nom, ' ', chef_equipe.prenoms)) AS nom_chef"),
                DB::raw('COUNT(DISTINCT a.id_agent) AS nombre_agents'),
                DB::raw('COUNT(t.id_ticket) AS nombre_tickets'),
                DB::raw('COALESCE(SUM(t.montant_paie), 0) AS montant_total'),
                DB::raw('COALESCE(SUM(t.montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(t.montant_reste), 0) AS montant_du'),
                DB::raw('COUNT(CASE WHEN COALESCE(t.montant_reste, 0) > 0 THEN 1 END) AS tickets_non_payes'),
            ])
            ->leftJoin('agents as a', function ($join) {
                $join->on('chef_equipe.id_chef', '=', 'a.id_chef')
                    ->whereNull('a.date_suppression');
            })
            ->leftJoin('tickets as t', function ($join) {
                $join->on('a.id_agent', '=', 't.id_agent')
                    ->whereNotNull('t.date_validation_boss')
                    ->where('t.prix_unitaire', '>', 0);
            });

        $this->applyTicketDateFilters($query, $filters, 't.date_ticket');

        if ($filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('chef_equipe.nom', 'like', $term)
                    ->orWhere('chef_equipe.prenoms', 'like', $term)
                    ->orWhereRaw("TRIM(CONCAT(chef_equipe.nom, ' ', chef_equipe.prenoms)) LIKE ?", [$term]);
            });
        }

        if ($filters['chef_id'] !== '') {
            $query->where('chef_equipe.id_chef', (int) $filters['chef_id']);
        }

        $query->groupBy('chef_equipe.id_chef', 'chef_equipe.nom', 'chef_equipe.prenoms')
            ->orderByDesc('montant_du')
            ->orderBy('chef_equipe.nom')
            ->orderBy('chef_equipe.prenoms');

        return $query;
    }

    /**
     * @param  array<string, string>  $filters
     * @return Collection<int, object>
     */
    public function filteredGroupeSummaries(array $filters): Collection
    {
        $summaries = $this->groupeSummariesQuery($filters)->get();

        if ($filters['statut_paiement'] === 'du') {
            return $summaries->filter(fn ($row) => (float) $row->montant_du > 0)->values();
        }

        if ($filters['statut_paiement'] === 'solde') {
            return $summaries->filter(fn ($row) => (float) $row->montant_du <= 0)->values();
        }

        return $summaries;
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedGroupeSummaries(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $all = $this->filteredGroupeSummaries($filters);
        $page = max(1, (int) request()->query('page', 1));
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $all->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @param  Collection<int, object>  $summaries
     * @return array{
     *     montant_total: float,
     *     montant_paye: float,
     *     montant_du: float,
     *     nombre_tickets: int
     * }
     */
    public function globalStatsFromSummaries(Collection $summaries): array
    {
        return [
            'montant_total' => (float) $summaries->sum(fn ($row) => (float) $row->montant_total),
            'montant_paye' => (float) $summaries->sum(fn ($row) => (float) $row->montant_paye),
            'montant_du' => (float) $summaries->sum(fn ($row) => (float) $row->montant_du),
            'nombre_tickets' => (int) $summaries->sum(fn ($row) => (int) $row->nombre_tickets),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{
     *     montant_total: float,
     *     montant_paye: float,
     *     montant_du: float,
     *     nombre_tickets: int,
     *     nombre_agents: int
     * }
     */
    public function statsForGroupe(int $groupeId, array $filters): array
    {
        $query = Ticket::query()
            ->join('agents as a', 'tickets.id_agent', '=', 'a.id_agent')
            ->where('a.id_chef', $groupeId)
            ->whereNull('a.date_suppression')
            ->validated();

        $this->applyTicketDateFilters($query, $filters, 'tickets.date_ticket');

        $row = $query->select([
            DB::raw('COUNT(tickets.id_ticket) AS nombre_tickets'),
            DB::raw('COALESCE(SUM(tickets.montant_paie), 0) AS montant_total'),
            DB::raw('COALESCE(SUM(tickets.montant_payer), 0) AS montant_paye'),
            DB::raw('COALESCE(SUM(tickets.montant_reste), 0) AS montant_du'),
        ])->first();

        $nombreAgents = Agent::query()
            ->where('id_chef', $groupeId)
            ->whereNull('date_suppression')
            ->count();

        return [
            'montant_total' => (float) ($row->montant_total ?? 0),
            'montant_paye' => (float) ($row->montant_paye ?? 0),
            'montant_du' => (float) ($row->montant_du ?? 0),
            'nombre_tickets' => (int) ($row->nombre_tickets ?? 0),
            'nombre_agents' => $nombreAgents,
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{tickets: int, bordereaux: int, agents: int}
     */
    public function countsForGroupe(int $groupeId, array $filters): array
    {
        $stats = $this->statsForGroupe($groupeId, $filters);

        $agentIds = Agent::query()
            ->where('id_chef', $groupeId)
            ->whereNull('date_suppression')
            ->pluck('id_agent');

        return [
            'tickets' => $stats['nombre_tickets'],
            'bordereaux' => $agentIds->isEmpty()
                ? 0
                : Bordereau::query()
                    ->whereIn('id_agent', $agentIds)
                    ->whereNotNull('date_validation_boss')
                    ->count(),
            'agents' => $stats['nombre_agents'],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedTicketsForGroupe(int $groupeId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Ticket::query()
            ->with(['usine', 'agent'])
            ->join('agents as a', 'tickets.id_agent', '=', 'a.id_agent')
            ->where('a.id_chef', $groupeId)
            ->whereNull('a.date_suppression')
            ->select('tickets.*')
            ->validated()
            ->orderByDesc('tickets.date_ticket')
            ->orderByDesc('tickets.id_ticket');

        $this->applyTicketDateFilters($query, $filters, 'tickets.date_ticket');
        $this->applyTicketStatutFilter($query, $filters['statut'] ?? '');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedAgentSummariesForGroupe(int $groupeId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Agent::query()
            ->select([
                'agents.id_agent',
                'agents.nom',
                'agents.prenom',
                DB::raw('COUNT(t.id_ticket) AS nombre_tickets'),
                DB::raw('COALESCE(SUM(t.montant_paie), 0) AS montant_total'),
                DB::raw('COALESCE(SUM(t.montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(t.montant_reste), 0) AS montant_du'),
                DB::raw('COUNT(CASE WHEN COALESCE(t.montant_reste, 0) > 0 THEN 1 END) AS tickets_non_payes'),
            ])
            ->leftJoin('tickets as t', function ($join) {
                $join->on('agents.id_agent', '=', 't.id_agent')
                    ->whereNotNull('t.date_validation_boss')
                    ->where('t.prix_unitaire', '>', 0);
            })
            ->where('agents.id_chef', $groupeId)
            ->whereNull('agents.date_suppression');

        $this->applyTicketDateFilters($query, $filters, 't.date_ticket');

        if ($filters['statut'] === 'paye') {
            $query->havingRaw('COALESCE(SUM(t.montant_reste), 0) <= 0')
                ->havingRaw('COALESCE(SUM(t.montant_payer), 0) > 0');
        } elseif ($filters['statut'] === 'non_paye') {
            $query->havingRaw('COALESCE(SUM(t.montant_reste), 0) > 0');
        }

        return $query
            ->groupBy('agents.id_agent', 'agents.nom', 'agents.prenom')
            ->orderBy('agents.nom')
            ->orderBy('agents.prenom')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyTicketStatutFilter(Builder $query, string $statut): void
    {
        if ($statut === 'paye') {
            $query->where(function (Builder $q) {
                $q->whereNotNull('tickets.date_paie')
                    ->orWhere(function (Builder $inner) {
                        $inner->whereNotNull('tickets.montant_reste')
                            ->where('tickets.montant_reste', '<=', 0)
                            ->where('tickets.montant_payer', '>', 0);
                    });
            });

            return;
        }

        if ($statut === 'non_paye') {
            $query->whereNotNull('tickets.montant_paie')
                ->where(function (Builder $q) {
                    $q->whereNull('tickets.date_paie')
                        ->where(function (Builder $inner) {
                            $inner->whereNull('tickets.montant_reste')
                                ->orWhere('tickets.montant_reste', '>', 0);
                        });
                });
        }
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyTicketDateFilters(Builder $query, array $filters, string $dateColumn): void
    {
        if ($filters['date_debut'] !== '' && $filters['date_fin'] !== '') {
            $query->whereBetween(DB::raw("DATE({$dateColumn})"), [
                $filters['date_debut'],
                $filters['date_fin'],
            ]);
        } elseif ($filters['date_debut'] !== '') {
            $query->whereDate($dateColumn, '>=', $filters['date_debut']);
        } elseif ($filters['date_fin'] !== '') {
            $query->whereDate($dateColumn, '<=', $filters['date_fin']);
        }
    }
}
