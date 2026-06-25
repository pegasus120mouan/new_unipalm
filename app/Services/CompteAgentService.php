<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Bordereau;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompteAgentService
{
    /**
     * @param  array<string, string>  $filters
     */
    public function agentSummariesQuery(array $filters): Builder
    {
        $query = Agent::query()
            ->select([
                'agents.id_agent',
                'agents.nom',
                'agents.prenom',
                'agents.contact',
                'agents.date_ajout',
                DB::raw('COALESCE(SUM(b.montant_total), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(b.montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(b.montant_reste), 0) AS reste_a_payer'),
                DB::raw("TRIM(CONCAT(COALESCE(ce.nom, ''), ' ', COALESCE(ce.prenoms, ''))) AS chef_equipe"),
            ])
            ->leftJoin('bordereau as b', 'agents.id_agent', '=', 'b.id_agent')
            ->leftJoin('chef_equipe as ce', 'agents.id_chef', '=', 'ce.id_chef')
            ->whereNull('agents.date_suppression');

        if ($filters['search_nom'] !== '') {
            $query->where('agents.nom', 'like', '%'.$filters['search_nom'].'%');
        }

        if ($filters['search_prenom'] !== '') {
            $query->where('agents.prenom', 'like', '%'.$filters['search_prenom'].'%');
        }

        if ($filters['search_contact'] !== '') {
            $query->where('agents.contact', 'like', '%'.$filters['search_contact'].'%');
        }

        if ($filters['search_chef'] !== '') {
            $term = '%'.$filters['search_chef'].'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('ce.nom', 'like', $term)
                    ->orWhere('ce.prenoms', 'like', $term)
                    ->orWhereRaw("TRIM(CONCAT(COALESCE(ce.nom, ''), ' ', COALESCE(ce.prenoms, ''))) LIKE ?", [$term]);
            });
        }

        return $query
            ->groupBy(
                'agents.id_agent',
                'agents.nom',
                'agents.prenom',
                'agents.contact',
                'agents.date_ajout',
                'ce.nom',
                'ce.prenoms',
            )
            ->orderBy('agents.nom')
            ->orderBy('agents.prenom');
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedAgentSummaries(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->agentSummariesQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{
     *     total_montant: float,
     *     montant_paye: float,
     *     reste_a_payer: float,
     *     pct_paye: int,
     *     pct_reste: int
     * }
     */
    public function globalStats(): array
    {
        $row = Bordereau::query()
            ->select([
                DB::raw('COALESCE(SUM(montant_total), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(montant_reste), 0) AS reste_a_payer'),
            ])
            ->first();

        $total = (float) ($row->total_montant ?? 0);
        $paye = (float) ($row->montant_paye ?? 0);
        $reste = (float) ($row->reste_a_payer ?? 0);

        return [
            'total_montant' => $total,
            'montant_paye' => $paye,
            'reste_a_payer' => $reste,
            'pct_paye' => $total > 0 ? (int) round(($paye / $total) * 100) : 0,
            'pct_reste' => $total > 0 ? (int) round(($reste / $total) * 100) : 0,
        ];
    }

    public function totalAgentsCount(): int
    {
        return Agent::query()->whereNull('date_suppression')->count();
    }

    /**
     * @return array{
     *     total_montant: float,
     *     montant_paye: float,
     *     reste_a_payer: float,
     *     solde_global: float
     * }
     */
    public function financialStatsForAgent(int $agentId, float $soldeFinancement): array
    {
        $row = Bordereau::query()
            ->where('id_agent', $agentId)
            ->whereNotNull('date_validation_boss')
            ->select([
                DB::raw('COALESCE(SUM(montant_total), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(montant_reste), 0) AS reste_a_payer'),
            ])
            ->first();

        $reste = (float) ($row->reste_a_payer ?? 0);

        return [
            'total_montant' => (float) ($row->total_montant ?? 0),
            'montant_paye' => (float) ($row->montant_paye ?? 0),
            'reste_a_payer' => $reste,
            'solde_global' => $reste - $soldeFinancement,
        ];
    }

    /**
     * @return array{tickets: int, bordereaux: int}
     */
    public function countsForAgent(int $agentId): array
    {
        return [
            'tickets' => Ticket::query()
                ->where('id_agent', $agentId)
                ->validated()
                ->count(),
            'bordereaux' => Bordereau::query()
                ->where('id_agent', $agentId)
                ->whereNotNull('date_validation_boss')
                ->count(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedAgentTickets(int $agentId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Ticket::query()
            ->with(['usine'])
            ->where('id_agent', $agentId)
            ->validated()
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket');

        $this->applyTicketStatusFilter($query, $filters['statut_ticket'] ?? '');

        return $query->paginate($perPage, ['*'], 'page_tickets')->withQueryString();
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginatedAgentBordereaux(int $agentId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Bordereau::query()
            ->where('id_agent', $agentId)
            ->whereNotNull('date_validation_boss')
            ->withCount(['tickets' => fn ($q) => $q->validated()])
            ->orderByDesc('created_at')
            ->orderByDesc('id_bordereau');

        $this->applyBordereauStatusFilter($query, $filters['statut_bordereau'] ?? '');

        return $query->paginate($perPage, ['*'], 'page_bordereaux')->withQueryString();
    }

    public function ticketPaymentStatusKey(?float $montantPaye, ?float $montantReste, mixed $datePaie): string
    {
        $paye = (float) ($montantPaye ?? 0);

        if ($montantReste !== null) {
            $reste = (float) $montantReste;
            if ($reste <= 0 && $paye > 0) {
                return 'solde';
            }
            if ($paye > 0 && $reste > 0) {
                return 'en_cours';
            }

            return 'non_paye';
        }

        return $datePaie ? 'solde' : 'non_paye';
    }

    public function ticketPaymentStatusLabel(string $key): string
    {
        return match ($key) {
            'solde' => 'Soldé',
            'en_cours' => 'En cours de paiement',
            default => 'Non payé',
        };
    }

    public function bordereauPaymentStatusKey(?float $montantTotal, ?float $montantPaye, ?float $montantReste, ?string $statutBordereau): string
    {
        $total = (float) ($montantTotal ?? 0);
        $paye = (float) ($montantPaye ?? 0);

        if ($montantReste !== null) {
            $reste = (float) $montantReste;
            if ($reste <= 0 && $total > 0) {
                return 'solde';
            }
            if ($paye > 0 && $reste > 0) {
                return 'en_cours';
            }

            return 'non_paye';
        }

        return strtolower((string) $statutBordereau) === 'soldé' ? 'solde' : 'non_paye';
    }

    /**
     * @param  Builder<\App\Models\Ticket>  $query
     */
    private function applyTicketStatusFilter(Builder $query, string $statut): void
    {
        if ($statut === '') {
            return;
        }

        if ($statut === 'solde') {
            $query->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('montant_reste')
                        ->where('montant_reste', '<=', 0)
                        ->where('montant_payer', '>', 0);
                })->orWhere(function (Builder $inner) {
                    $inner->whereNotNull('date_paie')
                        ->where(function (Builder $nullReste) {
                            $nullReste->whereNull('montant_reste');
                        });
                });
            });

            return;
        }

        if ($statut === 'en_cours') {
            $query->where('montant_payer', '>', 0)
                ->where('montant_reste', '>', 0);

            return;
        }

        if ($statut === 'non_paye') {
            $query->where(function (Builder $q) {
                $q->whereNull('montant_payer')->orWhere('montant_payer', '<=', 0);
            })->where(function (Builder $q) {
                $q->whereNull('montant_reste')->orWhere('montant_reste', '>', 0);
            })->whereNull('date_paie');
        }
    }

    /**
     * @param  Builder<Bordereau>  $query
     */
    private function applyBordereauStatusFilter(Builder $query, string $statut): void
    {
        if ($statut === '') {
            return;
        }

        if ($statut === 'solde') {
            $query->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('montant_reste')
                        ->where('montant_reste', '<=', 0)
                        ->where('montant_total', '>', 0);
                })->orWhere('statut_bordereau', 'soldé');
            });

            return;
        }

        if ($statut === 'en_cours') {
            $query->where('montant_payer', '>', 0)
                ->where('montant_reste', '>', 0);

            return;
        }

        if ($statut === 'non_paye') {
            $query->where(function (Builder $q) {
                $q->whereNull('montant_payer')->orWhere('montant_payer', '<=', 0);
            })->where(function (Builder $q) {
                $q->whereNull('montant_reste')
                    ->orWhere('montant_reste', '>', 0);
            })->where(function (Builder $q) {
                $q->whereNull('statut_bordereau')
                    ->orWhere('statut_bordereau', '!=', 'soldé');
            });
        }
    }
}
