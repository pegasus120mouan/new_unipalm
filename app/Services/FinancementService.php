<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Financement;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancementService
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
                DB::raw('COALESCE(SUM(CASE WHEN f.montant > 0 THEN f.montant ELSE 0 END), 0) AS montant_initial'),
                DB::raw('COALESCE(-SUM(CASE WHEN f.montant < 0 THEN f.montant ELSE 0 END), 0) AS montant_rembourse'),
                DB::raw('GREATEST(COALESCE(SUM(f.montant), 0), 0) AS solde_financement'),
                DB::raw('COALESCE(COUNT(f.Numero_financement), 0) AS nombre_financements'),
            ])
            ->leftJoin('financement as f', function ($join) {
                $join->on('agents.id_agent', '=', 'f.id_agent')
                    ->where(function ($q) {
                        $q->where('f.statut', Financement::STATUT_VALIDE)
                            ->orWhereNull('f.statut')
                            ->orWhere('f.statut', '');
                    });
            })
            ->whereNull('agents.date_suppression');

        if (! empty($filters['agent_id'])) {
            $query->where('agents.id_agent', (int) $filters['agent_id']);
        }

        if (! empty($filters['agent'])) {
            $term = '%'.$filters['agent'].'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('agents.nom', 'like', $term)
                    ->orWhere('agents.prenom', 'like', $term)
                    ->orWhereRaw("TRIM(CONCAT(agents.nom, ' ', agents.prenom)) LIKE ?", [$term]);
            });
        }

        if (! empty($filters['date_debut']) && ! empty($filters['date_fin'])) {
            $query->whereBetween(DB::raw('DATE(f.date_financement)'), [
                $filters['date_debut'],
                $filters['date_fin'],
            ]);
        } elseif (! empty($filters['date_debut'])) {
            $query->whereDate('f.date_financement', '>=', $filters['date_debut']);
        } elseif (! empty($filters['date_fin'])) {
            $query->whereDate('f.date_financement', '<=', $filters['date_fin']);
        }

        return $query
            ->groupBy('agents.id_agent', 'agents.nom', 'agents.prenom')
            ->orderByDesc('solde_financement')
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
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Financement>
     */
    public function detailedList(array $filters): Collection
    {
        $query = Financement::query()
            ->with('agent')
            ->orderByDesc('Numero_financement');

        $this->applyListFilters($query, $filters);

        return $query->get();
    }

    /**
     * @return array{montant_initial: float, montant_rembourse: float, solde_financement: float, total_operations: int}
     */
    public function statsForAgent(int $agentId): array
    {
        $rows = Financement::query()
            ->where('id_agent', $agentId)
            ->valides()
            ->get(['montant']);

        $montantInitial = 0.0;
        $montantRembourse = 0.0;
        $solde = 0.0;

        foreach ($rows as $row) {
            $montant = (float) $row->montant;
            if ($montant > 0) {
                $montantInitial += $montant;
            } else {
                $montantRembourse += abs($montant);
            }
            $solde += $montant;
        }

        return [
            'montant_initial' => $montantInitial,
            'montant_rembourse' => $montantRembourse,
            'solde_financement' => max(0, $solde),
            'total_operations' => $rows->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedAgentHistory(int $agentId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Financement::query()
            ->where('id_agent', $agentId)
            ->orderByDesc('date_financement')
            ->orderByDesc('Numero_financement');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('Numero_financement', 'like', '%'.$search.'%')
                    ->orWhere('code_financement', 'like', '%'.$search.'%')
                    ->orWhere('motif', 'like', '%'.$search.'%');
            });
        }

        if ($filters['type_filter'] === 'financement') {
            $query->where('montant', '>', 0);
        } elseif ($filters['type_filter'] === 'remboursement') {
            $query->where('montant', '<', 0);
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_financement', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_financement', '<=', $filters['date_fin']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Financement>
     */
    public function historyForPeriod(int $agentId, string $dateDebut, string $dateFin): Collection
    {
        return Financement::query()
            ->where('id_agent', $agentId)
            ->whereBetween('date_financement', [
                $dateDebut.' 00:00:00',
                $dateFin.' 23:59:59',
            ])
            ->orderByDesc('date_financement')
            ->orderByDesc('Numero_financement')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Financement>  $financements
     * @return array{total_financements: float, total_remboursements: float, solde_periode: float, nb_financements: int, nb_remboursements: int}
     */
    public function periodStats(Collection $financements): array
    {
        $totalFinancements = 0.0;
        $totalRemboursements = 0.0;
        $solde = 0.0;
        $nbFinancements = 0;
        $nbRemboursements = 0;

        foreach ($financements as $financement) {
            $montant = (float) $financement->montant;
            if ($montant > 0) {
                $totalFinancements += $montant;
                $nbFinancements++;
            } elseif ($montant < 0) {
                $totalRemboursements += abs($montant);
                $nbRemboursements++;
            }
            $solde += $montant;
        }

        return [
            'total_financements' => $totalFinancements,
            'total_remboursements' => $totalRemboursements,
            'solde_periode' => $solde,
            'nb_financements' => $nbFinancements,
            'nb_remboursements' => $nbRemboursements,
        ];
    }

    public function create(
        int $agentId,
        float $montant,
        string $motif,
        string $statut = Financement::STATUT_VALIDE,
    ): Financement {
        $statut = $statut === Financement::STATUT_EN_ATTENTE
            ? Financement::STATUT_EN_ATTENTE
            : Financement::STATUT_VALIDE;

        return DB::transaction(function () use ($agentId, $montant, $motif, $statut) {
            Financement::query()
                ->orderByDesc('Numero_financement')
                ->lockForUpdate()
                ->first();

            $nextNumero = ((int) Financement::query()->max('Numero_financement')) + 1;
            $agent = Agent::query()->find($agentId);

            return Financement::create([
                'Numero_financement' => $nextNumero,
                'code_financement' => $this->generateCodeFinancement($agent),
                'id_agent' => $agentId,
                'montant' => $montant,
                'motif' => $motif,
                'statut' => $statut,
                'date_financement' => now(),
            ]);
        });
    }

    public function createDemande(int $agentId, float $montant, string $motif): Financement
    {
        return $this->create($agentId, $montant, $motif, Financement::STATUT_EN_ATTENTE);
    }

    public function valider(Financement $financement): Financement
    {
        if (! $financement->isEnAttente()) {
            throw new InvalidArgumentException('Ce financement n\'est pas en attente de validation.');
        }

        $montant = (float) $financement->montant;
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du financement doit être supérieur à 0.');
        }

        return DB::transaction(function () use ($financement, $montant) {
            /** @var Financement $locked */
            $locked = Financement::query()
                ->whereKey($financement->Numero_financement)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isEnAttente()) {
                throw new InvalidArgumentException('Ce financement n\'est pas en attente de validation.');
            }

            $agent = Agent::query()->find($locked->id_agent);
            if (! $agent || $agent->date_suppression !== null) {
                throw new InvalidArgumentException('Agent introuvable pour ce financement.');
            }

            $chefId = (int) ($agent->id_chef ?? 0);
            if ($chefId <= 0) {
                throw new InvalidArgumentException('Cet agent n\'est rattaché à aucun chef de groupe.');
            }

            $this->debiterSoldeChef($chefId, $montant, (int) $agent->id_agent);

            $locked->update([
                'statut' => Financement::STATUT_VALIDE,
                'date_financement' => now(),
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Réduit le solde du chef (tickets non soldés) du montant validé.
     * Priorité aux tickets de l'agent demandeur, puis aux autres agents du groupe.
     */
    private function debiterSoldeChef(int $chefId, float $montant, int $agentPrioritaireId): void
    {
        $resteChef = (float) Ticket::query()
            ->join('agents as a', 'tickets.id_agent', '=', 'a.id_agent')
            ->where('a.id_chef', $chefId)
            ->whereNull('a.date_suppression')
            ->whereNotNull('tickets.montant_paie')
            ->where('tickets.montant_paie', '>', 0)
            ->selectRaw('COALESCE(SUM(tickets.montant_paie - COALESCE(tickets.montant_payer, 0)), 0) AS reste')
            ->value('reste');

        if ($resteChef + 0.0001 < $montant) {
            throw new InvalidArgumentException(
                'Solde du chef de groupe insuffisant. Disponible : '
                .number_format(max(0, $resteChef), 0, '', ' ')
                .' FCFA, demandé : '
                .number_format($montant, 0, '', ' ')
                .' FCFA.'
            );
        }

        $tickets = Ticket::query()
            ->join('agents as a', 'tickets.id_agent', '=', 'a.id_agent')
            ->where('a.id_chef', $chefId)
            ->whereNull('a.date_suppression')
            ->whereNotNull('tickets.montant_paie')
            ->where('tickets.montant_paie', '>', 0)
            ->whereRaw('COALESCE(tickets.montant_paie, 0) > COALESCE(tickets.montant_payer, 0)')
            ->select('tickets.*')
            ->orderByRaw('CASE WHEN tickets.id_agent = ? THEN 0 ELSE 1 END', [$agentPrioritaireId])
            ->orderBy('tickets.date_ticket')
            ->orderBy('tickets.id_ticket')
            ->lockForUpdate()
            ->get();

        $remaining = $montant;
        $datePaie = now();

        foreach ($tickets as $ticket) {
            if ($remaining <= 0) {
                break;
            }

            $montantTicket = (float) $ticket->montant_paie;
            $dejaPaye = (float) ($ticket->montant_payer ?? 0);
            $resteTicket = max($montantTicket - $dejaPaye, 0);

            if ($resteTicket <= 0) {
                continue;
            }

            $toApply = min($remaining, $resteTicket);
            $nouveauPaye = $dejaPaye + $toApply;
            $nouveauReste = max($montantTicket - $nouveauPaye, 0);

            $ticket->update([
                'montant_payer' => $nouveauPaye,
                'montant_reste' => $nouveauReste,
                'date_paie' => $nouveauReste <= 0 ? $datePaie : ($ticket->date_paie ?: null),
                'statut_ticket' => $nouveauReste <= 0 ? 'soldé' : 'non soldé',
            ]);

            $remaining -= $toApply;
        }

        if ($remaining > 0.0001) {
            throw new InvalidArgumentException(
                'Impossible d\'imputer le financement sur le solde du chef de groupe ('
                .number_format($remaining, 0, '', ' ')
                .' FCFA non couverts).'
            );
        }
    }

    public function countEnAttenteValidation(): int
    {
        return (int) Financement::query()->enAttente()->count();
    }

    /**
     * @return Collection<int, Financement>
     */
    public function pendingValidations(): Collection
    {
        return Financement::query()
            ->with('agent')
            ->enAttente()
            ->orderByDesc('date_financement')
            ->orderByDesc('Numero_financement')
            ->get();
    }

    public function generateCodeFinancement(?Agent $agent): string
    {
        $initials = $this->agentInitials($agent);
        $prefix = 'FIN-'.$initials;

        $existing = Financement::query()
            ->where('code_financement', 'like', $prefix.'-%')
            ->pluck('code_financement');

        $maxSeq = 0;
        foreach ($existing as $code) {
            if (preg_match('/-(\d{4})$/', (string) $code, $m)) {
                $maxSeq = max($maxSeq, (int) $m[1]);
            }
        }

        return $prefix.'-'.sprintf('%04d', $maxSeq + 1);
    }

    private function agentInitials(?Agent $agent): string
    {
        $nom = trim((string) ($agent?->nom ?? ''));
        $prenom = trim((string) ($agent?->prenom ?? ''));
        $parts = array_values(array_filter(preg_split('/\s+/u', trim($nom.' '.$prenom)) ?: []));
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';

        $letter = function (string $word): string {
            if ($word === '') {
                return '';
            }
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
            $src = is_string($ascii) && $ascii !== '' ? $ascii : $word;

            return strtoupper(substr($src, 0, 1));
        };

        $initials = $letter($first).$letter($second);

        return $initials !== '' ? $initials : 'XX';
    }

    /**
     * @param  Builder<Financement>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyListFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('Numero_financement', 'like', '%'.$search.'%')
                    ->orWhere('code_financement', 'like', '%'.$search.'%')
                    ->orWhere('motif', 'like', '%'.$search.'%')
                    ->orWhereHas('agent', function (Builder $agentQuery) use ($search) {
                        $agentQuery->whereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ['%'.$search.'%']);
                    });
            });
        }

        if (! empty($filters['agent_id'])) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if (! empty($filters['agent'])) {
            $term = '%'.$filters['agent'].'%';
            $query->whereHas('agent', function (Builder $agentQuery) use ($term) {
                $agentQuery->where('nom', 'like', $term)
                    ->orWhere('prenom', 'like', $term)
                    ->orWhereRaw("TRIM(CONCAT(nom, ' ', prenom)) LIKE ?", [$term]);
            });
        }

        if (! empty($filters['date_debut']) && ! empty($filters['date_fin'])) {
            $query->whereBetween(DB::raw('DATE(date_financement)'), [
                $filters['date_debut'],
                $filters['date_fin'],
            ]);
        } elseif (! empty($filters['date_debut'])) {
            $query->whereDate('date_financement', '>=', $filters['date_debut']);
        } elseif (! empty($filters['date_fin'])) {
            $query->whereDate('date_financement', '<=', $filters['date_fin']);
        }
    }
}
