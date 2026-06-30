<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\RecuPaiement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RecuPaiementService
{
    /**
     * @param  array<string, string>  $filters
     */
    public function paginated(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = RecuPaiement::query()
            ->orderByDesc('date_creation')
            ->orderByDesc('id_recu');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{total: int, tickets: int, bordereaux: int, montant_total: float}
     */
    public function stats(array $filters = []): array
    {
        $base = RecuPaiement::query();
        $this->applyFilters($base, $filters);

        $total = (clone $base)->count();
        $tickets = (clone $base)->where('type_document', 'ticket')->count();
        $bordereaux = (clone $base)->where('type_document', 'bordereau')->count();
        $montantTotal = (float) (clone $base)->sum('montant_paye');

        return [
            'total' => $total,
            'tickets' => $tickets,
            'bordereaux' => $bordereaux,
            'montant_total' => $montantTotal,
        ];
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
     * @param  Builder<RecuPaiement>  $query
     * @param  array<string, string>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $type = $filters['type'] ?? 'all';
        if ($type !== '' && $type !== 'all') {
            $query->where('type_document', $type);
        }

        if (($filters['search'] ?? '') !== '') {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term): void {
                $q->where('numero_recu', 'like', '%'.$term.'%')
                    ->orWhere('numero_document', 'like', '%'.$term.'%')
                    ->orWhere('nom_agent', 'like', '%'.$term.'%');
            });
        }

        if (($filters['agent_id'] ?? '') !== '') {
            $query->where('id_agent', $filters['agent_id']);
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_creation', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_creation', '<=', $filters['date_fin']);
        }
    }
}
