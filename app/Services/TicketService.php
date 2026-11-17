<?php

namespace App\Services;

use App\Models\PrixUnitaire;
use App\Models\Ticket;

class TicketService
{
    /**
     * @return array{total: int, en_attente: int, valides: int, valides_non_payes: int}
     */
    public function getDashboardStats(): array
    {
        $query = Ticket::query()->visibleToCurrentUser();

        return [
            'total' => (clone $query)->count(),
            'en_attente' => (clone $query)->pending()->count(),
            'valides' => (clone $query)->validated()->count(),
            'valides_non_payes' => (clone $query)
                ->validated()
                ->whereNull('date_paie')
                ->count(),
        ];
    }

    /**
     * @return array{prix: ?float, found: bool}
     */
    public function getPrixUnitaireByDateAndUsine(string $dateTicket, int $idUsine): array
    {
        $prix = PrixUnitaire::query()
            ->where('id_usine', $idUsine)
            ->whereDate('date_debut', '<=', $dateTicket)
            ->where(function ($query) use ($dateTicket) {
                $query->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>=', $dateTicket);
            })
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->value('prix');

        if ($prix === null) {
            return ['prix' => null, 'found' => false];
        }

        return ['prix' => (float) $prix, 'found' => true];
    }

    public function create(array $data): Ticket
    {
        if (Ticket::query()->where('numero_ticket', $data['numero_ticket'])->exists()) {
            throw new \InvalidArgumentException(
                'Le ticket numéro '.$data['numero_ticket'].' existe déjà.'
            );
        }

        $prixInfo = $this->getPrixUnitaireByDateAndUsine($data['date_ticket'], (int) $data['id_usine']);
        $prix = $prixInfo['found'] ? $prixInfo['prix'] : null;
        $poids = (float) $data['poids'];

        return Ticket::query()->create([
            'numero_ticket' => $data['numero_ticket'],
            'id_usine' => $data['id_usine'],
            'date_ticket' => $data['date_ticket'],
            'id_agent' => $data['id_agent'],
            'id_pont' => $data['id_pont'] ?? null,
            'vehicule_id' => $data['vehicule_id'],
            'poids' => $poids,
            'bismar' => (bool) ($data['bismar'] ?? false),
            'code_transfert' => $data['code_transfert'] ?? null,
            'id_utilisateur' => $data['id_utilisateur'],
            'prix_unitaire' => $prix,
            'montant_paie' => $prix !== null ? $prix * $poids : 0,
            'statut_ticket' => 'non soldé',
            'created_at' => now(),
        ]);
    }

    /**
     * Prix catalogue usine + date ticket en priorité ; sinon prix saisi / déjà présent.
     */
    public function resolvePrixForTicket(Ticket $ticket, ?float $fallback = null): float
    {
        $dateTicket = $ticket->date_ticket instanceof \Carbon\CarbonInterface
            ? $ticket->date_ticket->format('Y-m-d')
            : (string) $ticket->date_ticket;

        $catalog = $this->getPrixUnitaireByDateAndUsine($dateTicket, (int) $ticket->id_usine);

        if ($catalog['found']) {
            return (float) $catalog['prix'];
        }

        if ($fallback !== null && $fallback > 0) {
            return $fallback;
        }

        if ($ticket->hasPrixUnitaire()) {
            return (float) $ticket->prix_unitaire;
        }

        $usine = $ticket->usine?->nom_usine ?? ('#'.$ticket->id_usine);

        throw new \InvalidArgumentException(
            "Aucun prix unitaire défini pour l'usine {$usine} à la date du ticket ({$dateTicket})."
        );
    }

    public function validate(Ticket $ticket, ?float $prixUnitaire = null): Ticket
    {
        $prix = $this->resolvePrixForTicket($ticket, $prixUnitaire);

        $ticket->update([
            'prix_unitaire' => $prix,
            'date_validation_boss' => now(),
            'montant_paie' => $prix * (float) $ticket->poids,
        ]);

        return $ticket->fresh();
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        if ($ticket->date_paie !== null) {
            throw new \InvalidArgumentException(
                'Ce ticket a déjà été payé. Modifications impossibles.'
            );
        }

        $ticket->fill([
            'date_ticket' => $data['date_ticket'],
            'numero_ticket' => $data['numero_ticket'],
            'id_usine' => $data['id_usine'],
            'id_agent' => $data['id_agent'],
            'id_pont' => array_key_exists('id_pont', $data) ? $data['id_pont'] : $ticket->id_pont,
            'vehicule_id' => $data['vehicule_id'],
            'poids' => $data['poids'],
        ]);

        if (array_key_exists('prix_unitaire', $data) && $data['prix_unitaire'] !== null && $data['prix_unitaire'] !== '') {
            $ticket->prix_unitaire = $data['prix_unitaire'];
        } elseif (! $ticket->hasPrixUnitaire()) {
            $prixInfo = $this->getPrixUnitaireByDateAndUsine(
                (string) $data['date_ticket'],
                (int) $data['id_usine'],
            );
            if ($prixInfo['found']) {
                $ticket->prix_unitaire = $prixInfo['prix'];
            }
        }

        if (! empty($data['created_at'])) {
            $ticket->created_at = \Illuminate\Support\Carbon::parse($data['created_at'])->startOfDay();
        }

        if ($ticket->hasPrixUnitaire()) {
            $ticket->montant_paie = (float) $ticket->prix_unitaire * (float) $ticket->poids;
        }

        $ticket->save();

        return $ticket->fresh();
    }

    /**
     * Applique rétroactivement un prix unitaire à tous les tickets non payés
     * de l'usine dont la date_ticket est dans la période.
     *
     * @return array{tickets: int, bordereaux: list<string>}
     */
    public function applyPrixUnitaireToPendingTickets(
        int $idUsine,
        float $prix,
        string $dateDebut,
        ?string $dateFin = null,
        bool $onlyMismatch = false,
    ): array {
        $query = Ticket::query()
            ->where('id_usine', $idUsine)
            ->whereNull('date_paie')
            ->whereDate('date_ticket', '>=', $dateDebut);

        if ($dateFin) {
            $query->whereDate('date_ticket', '<=', $dateFin);
        }

        if ($onlyMismatch) {
            $query->where(function ($q) use ($prix) {
                $q->whereNull('prix_unitaire')
                    ->orWhere('prix_unitaire', 0)
                    ->orWhere('prix_unitaire', '!=', $prix);
            });
        }

        $updated = 0;
        $bordereaux = [];

        $query->orderBy('id_ticket')->chunkById(100, function ($tickets) use ($prix, &$updated, &$bordereaux) {
            foreach ($tickets as $ticket) {
                $montantPaie = round($prix * (float) $ticket->poids, 2);
                $montantPayer = (float) ($ticket->montant_payer ?? 0);
                $montantReste = max(0, $montantPaie - $montantPayer);

                $ticket->update([
                    'prix_unitaire' => $prix,
                    'montant_paie' => $montantPaie,
                    'montant_reste' => $montantReste,
                ]);
                $updated++;

                if (! empty($ticket->numero_bordereau)) {
                    $bordereaux[$ticket->numero_bordereau] = true;
                }
            }
        }, 'id_ticket');

        $bordereauNumeros = array_keys($bordereaux);
        if ($bordereauNumeros !== []) {
            $this->recalculateBordereauxMontants($bordereauNumeros);
        }

        return [
            'tickets' => $updated,
            'bordereaux' => $bordereauNumeros,
        ];
    }

    /**
     * @param  list<string>  $numeros
     */
    public function recalculateBordereauxMontants(array $numeros): int
    {
        $updated = 0;

        foreach (array_unique($numeros) as $numero) {
            $bordereau = \App\Models\Bordereau::query()
                ->where('numero_bordereau', $numero)
                ->whereNull('date_paie')
                ->first();

            if (! $bordereau) {
                continue;
            }

            $tickets = Ticket::query()
                ->where('numero_bordereau', $numero)
                ->get(['poids', 'prix_unitaire']);

            $montantTotal = round($tickets->sum(
                fn (Ticket $ticket) => (float) $ticket->prix_unitaire * (float) $ticket->poids
            ), 2);
            $montantPayer = (float) ($bordereau->montant_payer ?? 0);

            $bordereau->update([
                'montant_total' => $montantTotal,
                'montant_reste' => max(0, $montantTotal - $montantPayer),
            ]);
            $updated++;
        }

        return $updated;
    }

    /**
     * @param  array<int>  $ticketIds
     * @return array{validated: int, validated_ids: list<int>, usines_updated: list<int>}
     */
    public function validateMany(array $ticketIds, ?float $prixUnitaire = null, bool $updateAllUsine = false): array
    {
        $tickets = Ticket::query()
            ->with('usine')
            ->whereIn('id_ticket', $ticketIds)
            ->pending()
            ->get();

        $validated = 0;
        $validatedIds = [];
        $usineIds = [];

        foreach ($tickets as $ticket) {
            $this->validate($ticket, $prixUnitaire);
            $validated++;
            $validatedIds[] = $ticket->id_ticket;

            if ($updateAllUsine) {
                $usineIds[] = (int) $ticket->id_usine;
            }
        }

        $usineIds = array_values(array_unique($usineIds));

        if ($updateAllUsine && $usineIds !== []) {
            $pending = Ticket::query()
                ->whereIn('id_usine', $usineIds)
                ->whereNull('date_validation_boss')
                ->where(function ($query) {
                    $query->whereNull('prix_unitaire')
                        ->orWhere('prix_unitaire', 0);
                })
                ->whereNotIn('id_ticket', $validatedIds)
                ->orderBy('id_ticket')
                ->get();

            foreach ($pending as $ticket) {
                try {
                    $prix = $this->resolvePrixForTicket($ticket, $prixUnitaire);
                } catch (\InvalidArgumentException) {
                    continue;
                }

                $ticket->update([
                    'prix_unitaire' => $prix,
                    'montant_paie' => round($prix * (float) $ticket->poids, 2),
                    'updated_at' => now(),
                ]);
            }
        }

        return [
            'validated' => $validated,
            'validated_ids' => $validatedIds,
            'usines_updated' => $usineIds,
        ];
    }

    public function delete(Ticket $ticket): void
    {
        if ($ticket->isSold()) {
            throw new \InvalidArgumentException('Impossible de supprimer un ticket soldé.');
        }

        $ticket->delete();
    }
}
