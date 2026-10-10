<?php

namespace App\Services;

use App\Models\BordereauBismar;
use App\Models\PrimeBismar;
use App\Models\Ticket;
use App\Models\TicketParametre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BordereauBismarService
{
    /**
     * BISMAR tickets of the agent over the creation period, each with its `prime_applicable`
     * (null when no prime BISMAR covers the ticket date).
     *
     * @return Collection<int, Ticket>
     */
    public function candidateTickets(int $idAgent, string $dateDebut, string $dateFin): Collection
    {
        $tickets = Ticket::query()
            ->with(['usine', 'vehicule'])
            ->eligibleForBordereauBismar($idAgent, $dateDebut, $dateFin)
            ->orderByDesc('created_at')
            ->orderByDesc('id_ticket')
            ->get();

        return $this->attachPrimes($tickets);
    }

    /**
     * @param  array<int>  $ticketIds
     */
    public function createFromTickets(int $idAgent, string $dateDebut, string $dateFin, array $ticketIds): BordereauBismar
    {
        $ticketIds = array_values(array_unique(array_map('intval', $ticketIds)));

        if ($ticketIds === []) {
            throw new \InvalidArgumentException('Sélectionnez au moins un ticket pour créer le bordereau BISMAR.');
        }

        $tickets = $this->attachPrimes(
            Ticket::query()
                ->eligibleForBordereauBismar($idAgent, $dateDebut, $dateFin)
                ->whereIn('id_ticket', $ticketIds)
                ->get()
        );

        if ($tickets->count() !== count($ticketIds)) {
            throw new \InvalidArgumentException(
                TicketParametre::requiresVerification()
                    ? 'Un ou plusieurs tickets sélectionnés ne sont plus disponibles (vérification ou bordereau BISMAR).'
                    : 'Un ou plusieurs tickets sélectionnés ne sont plus disponibles (déjà dans un bordereau BISMAR).'
            );
        }

        $sansPrime = $tickets->filter(fn (Ticket $ticket) => $ticket->prime_applicable === null);
        if ($sansPrime->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'Aucune prime BISMAR ne couvre la date du ou des ticket(s) : '
                .$sansPrime->pluck('numero_ticket')->implode(', ').'.'
            );
        }

        $poidsTotal = $tickets->sum(fn (Ticket $ticket) => (float) $ticket->poids);
        $montantTotal = $tickets->sum(fn (Ticket $ticket) => $ticket->prime_applicable * (float) $ticket->poids);
        $numero = $this->generateNumero();

        return DB::transaction(function () use ($idAgent, $dateDebut, $dateFin, $tickets, $poidsTotal, $montantTotal, $numero) {
            $bordereau = BordereauBismar::query()->create([
                'numero_bordereau' => $numero,
                'id_agent' => $idAgent,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'poids_total' => $poidsTotal,
                'montant_total' => $montantTotal,
                'montant_payer' => 0,
                'montant_reste' => $montantTotal,
                'statut_bordereau' => 'non soldé',
                'created_by' => auth()->id(),
            ]);

            foreach ($tickets->groupBy('prime_applicable') as $prime => $group) {
                Ticket::query()
                    ->whereIn('id_ticket', $group->pluck('id_ticket'))
                    ->update([
                        'numero_bordereau_bismar' => $numero,
                        'prime_bismar' => (float) $prime,
                        'updated_at' => now(),
                    ]);
            }

            return $bordereau->fresh(['agent']);
        });
    }

    public function delete(BordereauBismar $bordereau): void
    {
        DB::transaction(function () use ($bordereau) {
            Ticket::query()
                ->where('numero_bordereau_bismar', $bordereau->numero_bordereau)
                ->update([
                    'numero_bordereau_bismar' => null,
                    'prime_bismar' => null,
                    'updated_at' => now(),
                ]);

            $bordereau->delete();
        });
    }

    public function validate(BordereauBismar $bordereau): BordereauBismar
    {
        if ($bordereau->isValidated()) {
            throw new \InvalidArgumentException('Ce bordereau BISMAR est déjà validé.');
        }

        $bordereau->update(['date_validation_boss' => now()]);

        return $bordereau->fresh(['agent']);
    }

    /**
     * @return array<string, mixed>
     */
    public function pdfData(string $numero): array
    {
        $bordereau = BordereauBismar::query()
            ->with('agent')
            ->where('numero_bordereau', $numero)
            ->first();

        if (! $bordereau) {
            throw new NotFoundHttpException('Bordereau BISMAR non trouvé.');
        }

        $tickets = Ticket::query()
            ->with(['usine', 'vehicule'])
            ->where('numero_bordereau_bismar', $numero)
            ->get()
            ->sortBy([
                fn (Ticket $ticket) => $ticket->usine?->nom_usine ?? '',
                fn (Ticket $ticket) => $ticket->date_ticket?->format('Y-m-d') ?? '',
                fn (Ticket $ticket) => $ticket->created_at?->format('Y-m-d H:i:s') ?? '',
            ])
            ->values();

        $groups = [];
        $totalPoids = 0.0;
        $totalMontant = 0.0;

        foreach ($tickets as $ticket) {
            $usine = $ticket->usine?->nom_usine ?? 'N/A';
            $poids = (float) $ticket->poids;
            $prime = (float) $ticket->prime_bismar;
            $montant = $poids * $prime;

            $groups[$usine] ??= ['usine' => $usine, 'tickets' => [], 'poids' => 0.0, 'montant' => 0.0];
            $groups[$usine]['tickets'][] = [
                'date' => $ticket->date_ticket?->format('d/m/Y') ?? '-',
                'numero' => $ticket->numero_ticket,
                'code_transfert' => $ticket->code_transfert,
                'vehicule' => $ticket->vehicule?->matricule_vehicule ?? '-',
                'poids' => $poids,
                'prime' => $prime,
                'montant' => $montant,
            ];
            $groups[$usine]['poids'] += $poids;
            $groups[$usine]['montant'] += $montant;
            $totalPoids += $poids;
            $totalMontant += $montant;
        }

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'bordereau' => $bordereau,
            'agentName' => $bordereau->agent?->full_name ?? '-',
            'groups' => array_values($groups),
            'totalPoids' => $totalPoids,
            'totalMontant' => $totalMontant,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return Collection<int, Ticket>
     */
    private function attachPrimes(Collection $tickets): Collection
    {
        if ($tickets->isEmpty()) {
            return $tickets;
        }

        $dates = $tickets->map(fn (Ticket $ticket) => $ticket->date_ticket?->format('Y-m-d'))->filter();
        $primes = $dates->isEmpty()
            ? collect()
            : PrimeBismar::query()->overlapping($dates->min(), $dates->max())->get();

        return $tickets->each(function (Ticket $ticket) use ($primes) {
            $date = $ticket->date_ticket?->format('Y-m-d');
            $prime = $date === null ? null : $primes->first(
                fn (PrimeBismar $prime) => $prime->date_debut->format('Y-m-d') <= $date
                    && $prime->date_fin->format('Y-m-d') >= $date
            );

            $ticket->prime_applicable = $prime ? (float) $prime->prix : null;
        });
    }

    private function generateNumero(): string
    {
        return 'BISM-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
