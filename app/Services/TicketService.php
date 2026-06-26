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

        return Ticket::query()->create([
            'numero_ticket' => $data['numero_ticket'],
            'id_usine' => $data['id_usine'],
            'date_ticket' => $data['date_ticket'],
            'id_agent' => $data['id_agent'],
            'vehicule_id' => $data['vehicule_id'],
            'poids' => $data['poids'],
            'id_utilisateur' => $data['id_utilisateur'],
            'prix_unitaire' => $prixInfo['found'] ? $prixInfo['prix'] : null,
            'statut_ticket' => 'non soldé',
            'created_at' => now(),
        ]);
    }

    public function validate(Ticket $ticket, float $prixUnitaire): Ticket
    {
        $ticket->update([
            'prix_unitaire' => $prixUnitaire,
            'date_validation_boss' => now(),
            'montant_paie' => $prixUnitaire * (float) $ticket->poids,
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
            'vehicule_id' => $data['vehicule_id'],
            'poids' => $data['poids'],
        ]);

        if ($ticket->hasPrixUnitaire()) {
            $ticket->montant_paie = (float) $ticket->prix_unitaire * (float) $ticket->poids;
        }

        $ticket->save();

        return $ticket->fresh();
    }

    /**
     * @param  array<int>  $ticketIds
     * @return array{validated: int, usines_updated: array<int>}
     */
    public function validateMany(array $ticketIds, float $prixUnitaire, bool $updateAllUsine = false): array
    {
        $tickets = Ticket::query()
            ->whereIn('id_ticket', $ticketIds)
            ->pending()
            ->withoutPrixUnitaire()
            ->get();

        $validated = 0;
        $usineIds = [];

        foreach ($tickets as $ticket) {
            $this->validate($ticket, $prixUnitaire);
            $validated++;

            if ($updateAllUsine) {
                $usineIds[] = $ticket->id_usine;
            }
        }

        $usineIds = array_values(array_unique($usineIds));

        if ($updateAllUsine && $usineIds !== []) {
            Ticket::query()
                ->whereIn('id_usine', $usineIds)
                ->whereNull('date_validation_boss')
                ->whereNull('prix_unitaire')
                ->whereNotIn('id_ticket', $tickets->pluck('id_ticket'))
                ->update([
                    'prix_unitaire' => $prixUnitaire,
                    'updated_at' => now(),
                ]);
        }

        return [
            'validated' => $validated,
            'usines_updated' => $usineIds,
        ];
    }
}
