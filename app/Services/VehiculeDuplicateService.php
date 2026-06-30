<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Vehicule;
use Illuminate\Support\Collection;

class VehiculeDuplicateService
{
    private const MIN_PREFIX_LENGTH = 6;

    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    /**
     * @return list<list<int>>
     */
    public function duplicateGroups(): array
    {
        $vehicules = Vehicule::query()
            ->orderBy('vehicules_id')
            ->get(['vehicules_id', 'matricule_vehicule']);

        if ($vehicules->count() < 2) {
            return [];
        }

        $parent = [];

        foreach ($vehicules as $vehicule) {
            $parent[(int) $vehicule->vehicules_id] = (int) $vehicule->vehicules_id;
        }

        $items = $vehicules->map(fn (Vehicule $vehicule) => [
            'id' => (int) $vehicule->vehicules_id,
            'normalized' => $vehicule->normalizedMatricule(),
        ])->filter(fn (array $item) => $item['normalized'] !== '')->values();

        for ($i = 0; $i < $items->count(); $i++) {
            for ($j = $i + 1; $j < $items->count(); $j++) {
                if ($this->matriculesAreSimilar($items[$i]['normalized'], $items[$j]['normalized'])) {
                    $this->union($parent, $items[$i]['id'], $items[$j]['id']);
                }
            }
        }

        $groups = [];

        foreach ($items as $item) {
            $root = $this->find($parent, $item['id']);
            $groups[$root] ??= [];
            $groups[$root][] = $item['id'];
        }

        return array_values(array_filter($groups, fn (array $group) => count($group) > 1));
    }

    /**
     * @return list<int>
     */
    public function duplicateVehiculeIds(): array
    {
        return collect($this->duplicateGroups())
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function deletableDuplicateIds(): array
    {
        $ids = [];

        foreach ($this->duplicateGroups() as $groupIds) {
            $keeper = $this->keeperForGroup($groupIds);

            if ($keeper === null) {
                continue;
            }

            foreach ($groupIds as $id) {
                if ($id !== (int) $keeper->vehicules_id) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    public function isDuplicate(Vehicule $vehicule): bool
    {
        return in_array((int) $vehicule->vehicules_id, $this->duplicateVehiculeIds(), true);
    }

    public function isDuplicateEligibleForDeletion(Vehicule $vehicule): bool
    {
        return in_array((int) $vehicule->vehicules_id, $this->deletableDuplicateIds(), true);
    }

    public function keeperForVehicule(Vehicule $vehicule): ?Vehicule
    {
        foreach ($this->duplicateGroups() as $groupIds) {
            if (! in_array((int) $vehicule->vehicules_id, $groupIds, true)) {
                continue;
            }

            return $this->keeperForGroup($groupIds);
        }

        return null;
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

    /**
     * @param  list<int>  $groupIds
     */
    public function keeperForGroup(array $groupIds): ?Vehicule
    {
        if ($groupIds === []) {
            return null;
        }

        return Vehicule::query()
            ->withCount('tickets')
            ->whereIn('vehicules_id', $groupIds)
            ->orderByDesc('tickets_count')
            ->orderBy('vehicules_id')
            ->first();
    }

    public function duplicateGroupCount(): int
    {
        return count($this->duplicateGroups());
    }

    private function matriculesAreSimilar(string $left, string $right): bool
    {
        if ($left === $right) {
            return true;
        }

        [$shorter, $longer] = strlen($left) <= strlen($right)
            ? [$left, $right]
            : [$right, $left];

        if (strlen($shorter) < self::MIN_PREFIX_LENGTH) {
            return false;
        }

        return str_starts_with($longer, $shorter);
    }

    /**
     * @param  array<int, int>  $parent
     */
    private function find(array &$parent, int $id): int
    {
        if ($parent[$id] !== $id) {
            $parent[$id] = $this->find($parent, $parent[$id]);
        }

        return $parent[$id];
    }

    /**
     * @param  array<int, int>  $parent
     */
    private function union(array &$parent, int $left, int $right): void
    {
        $rootLeft = $this->find($parent, $left);
        $rootRight = $this->find($parent, $right);

        if ($rootLeft !== $rootRight) {
            $parent[$rootRight] = $rootLeft;
        }
    }

    private function canDeleteTicket(Ticket $ticket): bool
    {
        return ! $ticket->isSold();
    }
}
