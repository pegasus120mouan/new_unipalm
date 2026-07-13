<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\BordereauAgentGestCamions;
use App\Models\Financement;
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
        $items = $this->attachDemandesPaiementEnAttente($items);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $all->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @param  Collection<int, object>  $groupes
     * @return Collection<int, object>
     */
    public function attachDemandesPaiementEnAttente(Collection $groupes): Collection
    {
        if ($groupes->isEmpty()) {
            return $groupes;
        }

        $chefIds = $groupes->pluck('id_chef')->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($chefIds->isEmpty()) {
            return $groupes->map(function ($groupe) {
                $groupe->demandes_paiement_en_attente = 0;

                return $groupe;
            });
        }

        $agents = Agent::query()
            ->whereIn('id_chef', $chefIds->all())
            ->whereNull('date_suppression')
            ->get(['id_agent', 'id_chef']);

        $agentIdsByChef = $agents->groupBy(fn ($a) => (int) $a->id_chef)->map(
            fn (Collection $rows) => $rows->pluck('id_agent')->map(fn ($id) => (int) $id)->all()
        );

        $allAgentIds = $agents->pluck('id_agent')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $countsByAgent = [];

        if ($allAgentIds !== []) {
            try {
                $raw = \App\Models\DemandeAvanceGestCamions::query()
                    ->whereIn('id_agent', $allAgentIds)
                    ->where('statut', 'en_attente')
                    ->selectRaw('id_agent, COUNT(*) as total')
                    ->groupBy('id_agent')
                    ->get();

                foreach ($raw as $row) {
                    $countsByAgent[(int) $row->id_agent] = (int) $row->total;
                }
            } catch (\Throwable $e) {
                report($e);
                $countsByAgent = [];
            }
        }

        return $groupes->map(function ($groupe) use ($agentIdsByChef, $countsByAgent) {
            $chefKey = (int) $groupe->id_chef;
            $agentIds = $agentIdsByChef->get($chefKey, []);
            $total = 0;
            $firstAgentId = null;
            foreach ($agentIds as $agentId) {
                $n = (int) ($countsByAgent[$agentId] ?? 0);
                if ($n > 0 && $firstAgentId === null) {
                    $firstAgentId = $agentId;
                }
                $total += $n;
            }
            $groupe->demandes_paiement_en_attente = $total;
            $groupe->demande_paiement_agent_id = $firstAgentId;

            return $groupe;
        });
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
     * Solde chef d'équipe (tickets validés), même logique que gest-camions / solde_chef_equipe.
     *
     * @return array{
     *     total_montant: float,
     *     montant_paye: float,
     *     reste_a_payer: float,
     *     solde_financement: float,
     *     reste_particuliers: float,
     *     reste_professionnels: float
     * }
     */
    public function soldeForGroupe(int $groupeId): array
    {
        $row = Ticket::query()
            ->join('agents as a', 'tickets.id_agent', '=', 'a.id_agent')
            ->where('a.id_chef', $groupeId)
            ->whereNull('a.date_suppression')
            ->whereNotNull('tickets.montant_paie')
            ->select([
                DB::raw('COALESCE(SUM(tickets.montant_paie), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(tickets.montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(tickets.montant_paie), 0) - COALESCE(SUM(tickets.montant_payer), 0) AS reste_a_payer'),
                DB::raw(
                    "COALESCE(SUM(CASE WHEN a.sous_groupe = 'particulier' THEN tickets.montant_paie ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN a.sous_groupe = 'particulier' THEN COALESCE(tickets.montant_payer, 0) ELSE 0 END), 0)
                    AS reste_particuliers"
                ),
                DB::raw(
                    "COALESCE(SUM(CASE WHEN a.sous_groupe = 'professionnel' THEN tickets.montant_paie ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN a.sous_groupe = 'professionnel' THEN COALESCE(tickets.montant_payer, 0) ELSE 0 END), 0)
                    AS reste_professionnels"
                ),
            ])
            ->first();

        $agentIds = $this->agentIdsForGroupe($groupeId);
        $soldeFinancement = 0.0;

        if ($agentIds->isNotEmpty()) {
            $soldeFinancement = (float) Financement::query()
                ->whereIn('id_agent', $agentIds)
                ->selectRaw('GREATEST(COALESCE(SUM(montant), 0), 0) AS solde')
                ->value('solde');
        }

        return [
            'total_montant' => (float) ($row->total_montant ?? 0),
            'montant_paye' => (float) ($row->montant_paye ?? 0),
            'reste_a_payer' => (float) ($row->reste_a_payer ?? 0),
            'solde_financement' => $soldeFinancement,
            'reste_particuliers' => (float) ($row->reste_particuliers ?? 0),
            'reste_professionnels' => (float) ($row->reste_professionnels ?? 0),
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
        $nombreAgents = Agent::query()
            ->where('id_chef', $groupeId)
            ->whereNull('date_suppression')
            ->count();

        $agentIds = $this->agentIdsForGroupe($groupeId);
        $bordereauxStats = $this->bordereauxFinancialStats($agentIds, $filters);

        return [
            'montant_total' => $bordereauxStats['montant_total'],
            'montant_paye' => $bordereauxStats['montant_paye'],
            'montant_du' => $bordereauxStats['montant_du'],
            'nombre_tickets' => 0,
            'nombre_agents' => $nombreAgents,
        ];
    }

    /**
     * @param  Collection<int, int>  $agentIds
     * @param  array<string, string>  $filters
     * @return array{montant_total: float, montant_paye: float, montant_du: float}
     */
    public function bordereauxFinancialStats(Collection $agentIds, array $filters = []): array
    {
        if ($agentIds->isEmpty()) {
            return [
                'montant_total' => 0.0,
                'montant_paye' => 0.0,
                'montant_du' => 0.0,
            ];
        }

        try {
            $query = BordereauAgentGestCamions::query()
                ->whereIn('id_agent', $agentIds);

            if (($filters['date_debut'] ?? '') !== '') {
                $query->whereDate('date_generation', '>=', $filters['date_debut']);
            }

            if (($filters['date_fin'] ?? '') !== '') {
                $query->whereDate('date_generation', '<=', $filters['date_fin']);
            }

            $row = $query->select([
                DB::raw('COALESCE(SUM(montant_total), 0) AS montant_total'),
                DB::raw('COALESCE(SUM(montant_paye), 0) AS montant_paye'),
            ])->first();

            $total = (float) ($row->montant_total ?? 0);
            $paye = (float) ($row->montant_paye ?? 0);

            return [
                'montant_total' => $total,
                'montant_paye' => $paye,
                'montant_du' => max(0, $total - $paye),
            ];
        } catch (\Throwable) {
            return [
                'montant_total' => 0.0,
                'montant_paye' => 0.0,
                'montant_du' => 0.0,
            ];
        }
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{tickets: int, bordereaux: int, agents: int}
     */
    public function countsForGroupe(int $groupeId, array $filters): array
    {
        $agentIds = $this->agentIdsForGroupe($groupeId);

        return [
            'tickets' => 0,
            'bordereaux' => $this->countBordereauxGestCamions($agentIds),
            'agents' => $agentIds->count(),
        ];
    }

    /**
     * Bordereaux générés dans gest-camions pour tous les agents du chef.
     *
     * @param  array<string, string>  $filters
     */
    public function paginatedBordereauxForGroupe(int $groupeId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $agentIds = $this->agentIdsForGroupe($groupeId);

        if ($agentIds->isEmpty()) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'page_bordereaux'],
            );
        }

        try {
            $query = BordereauAgentGestCamions::query()
                ->whereIn('id_agent', $agentIds)
                ->orderByDesc('date_generation')
                ->orderByDesc('id');

            if (($filters['date_debut'] ?? '') !== '') {
                $query->whereDate('date_generation', '>=', $filters['date_debut']);
            }

            if (($filters['date_fin'] ?? '') !== '') {
                $query->whereDate('date_generation', '<=', $filters['date_fin']);
            }

            $this->applyGestCamionsAgentSearchFilter($query, $filters['search_agent'] ?? '', $groupeId);
            $this->applyGestCamionsBordereauStatusFilter($query, $filters['statut_bordereau'] ?? '');

            return $query->paginate($perPage, ['*'], 'page_bordereaux')->withQueryString();
        } catch (\Throwable) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'page_bordereaux'],
            );
        }
    }

    /**
     * @param  Builder<BordereauAgentGestCamions>  $query
     */
    private function applyGestCamionsAgentSearchFilter(Builder $query, string $search, int $groupeId): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $term = '%'.$search.'%';

        $matchingAgentIds = Agent::query()
            ->where('id_chef', $groupeId)
            ->whereNull('date_suppression')
            ->where(function (Builder $q) use ($term, $search) {
                $q->where('nom', 'like', $term)
                    ->orWhere('prenom', 'like', $term)
                    ->orWhereRaw("TRIM(CONCAT(COALESCE(nom, ''), ' ', COALESCE(prenom, ''))) LIKE ?", [$term])
                    ->orWhere('numero_agent', 'like', $term);

                if (ctype_digit($search)) {
                    $q->orWhere('id_agent', (int) $search);
                }
            })
            ->pluck('id_agent');

        $query->where(function (Builder $q) use ($term, $search, $matchingAgentIds) {
            $q->where('agent_nom', 'like', $term)
                ->orWhere('agent_numero', 'like', $term)
                ->orWhere('numero', 'like', $term);

            if (ctype_digit($search)) {
                $q->orWhere('id_agent', (int) $search);
            }

            if ($matchingAgentIds->isNotEmpty()) {
                $q->orWhereIn('id_agent', $matchingAgentIds);
            }
        });
    }

    /**
     * @return Collection<int, int>
     */
    public function agentIdsForGroupe(int $groupeId): Collection
    {
        return Agent::query()
            ->where('id_chef', $groupeId)
            ->whereNull('date_suppression')
            ->pluck('id_agent');
    }

    /**
     * @param  Collection<int, int>  $agentIds
     */
    private function countBordereauxGestCamions(Collection $agentIds): int
    {
        if ($agentIds->isEmpty()) {
            return 0;
        }

        try {
            return BordereauAgentGestCamions::query()
                ->whereIn('id_agent', $agentIds)
                ->count();
        } catch (\Throwable) {
            // Connexion gest-camions indisponible : ne pas casser la page.
            return 0;
        }
    }

    /**
     * @param  Builder<BordereauAgentGestCamions>  $query
     */
    private function applyGestCamionsBordereauStatusFilter(Builder $query, string $statut): void
    {
        if ($statut === '') {
            return;
        }

        if ($statut === 'solde') {
            $query->whereRaw('COALESCE(montant_paye, 0) >= montant_total')
                ->where('montant_total', '>', 0);

            return;
        }

        if ($statut === 'en_cours') {
            $query->where('montant_paye', '>', 0)
                ->whereRaw('COALESCE(montant_paye, 0) < montant_total');

            return;
        }

        if ($statut === 'non_paye') {
            $query->where(function (Builder $q) {
                $q->whereNull('montant_paye')->orWhere('montant_paye', '<=', 0);
            });
        }
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
