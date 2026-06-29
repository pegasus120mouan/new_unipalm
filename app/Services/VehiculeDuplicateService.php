<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Vehicule;

class VehiculeDuplicateService
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    /**
     * @param  list<string>  $duplicateMatricules
     * @return list<int>
     */
    public function deletableDuplicateIds(array $duplicateMatricules): array
    {
        if ($duplicateMatricules === []) {
            return [];
        }

        $ids = [];

        foreach ($duplicateMatricules as $normalized) {
            $keeper = $this->keeperForNormalizedMatricule($normalized);

            if ($keeper === null) {
                continue;
            }

            $group = Vehicule::query()
                ->whereRaw('UPPER(REPLACE(matricule_vehicule, " ", "")) = ?', [$normalized])
                ->where('vehicules_id', '!=', $keeper->vehicules_id)
                ->pluck('vehicules_id');

            foreach ($group as $id) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * @return array{deleted: bool, tickets_deleted: int, tickets_reassigned: int}
     */
    public function deleteDuplicate(Vehicule $vehicule, Vehicule $keeper): array
    {
        $ticketsDeleted = 0;
        $ticketsReassigned = 0;

        $tickets = Ticket::query()
            ->where('vehicule_id', $vehicule->vehicules_id)
            ->get();

        foreach ($tickets as $ticket) {
            if ($this->canDeleteTicket($ticket)) {
                try {
                    $this->ticketService->delete($ticket);
                    $ticketsDeleted++;
                } catch (\InvalidArgumentException) {
                    $ticket->update(['vehicule_id' => $keeper->vehicules_id]);
                    $ticketsReassigned++;
                }
            } else {
                $ticket->update(['vehicule_id' => $keeper->vehicules_id]);
                $ticketsReassigned++;
            }
        }

        $vehicule->delete();

        return [
            'deleted' => true,
            'tickets_deleted' => $ticketsDeleted,
            'tickets_reassigned' => $ticketsReassigned,
        ];
    }

    public function keeperForNormalizedMatricule(string $normalized): ?Vehicule
    {
        return Vehicule::query()
            ->withCount('tickets')
            ->whereRaw('UPPER(REPLACE(matricule_vehicule, " ", "")) = ?', [$normalized])
            ->orderByDesc('tickets_count')
            ->orderBy('vehicules_id')
            ->first();
    }

    public function isDuplicateEligibleForDeletion(Vehicule $vehicule, array $duplicateMatricules): bool
    {
        if (! in_array($vehicule->normalizedMatricule(), $duplicateMatricules, true)) {
            return false;
        }

        $keeper = $this->keeperForNormalizedMatricule($vehicule->normalizedMatricule());

        return $keeper !== null && $keeper->vehicules_id !== $vehicule->vehicules_id;
    }

    private function canDeleteTicket(Ticket $ticket): bool
    {
        return ! $ticket->isSold();
    }
}
