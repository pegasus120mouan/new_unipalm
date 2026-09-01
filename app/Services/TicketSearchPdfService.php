<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Ticket;
use App\Models\Usine;
use App\Models\Vehicule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TicketSearchPdfService
{
    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  array<string, mixed>  $filters
     */
    public function stream(Collection $tickets, array $filters): Response
    {
        $data = $this->buildViewData($tickets, $filters);

        return Pdf::loadView('tickets.search-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->stream('recherche_tickets_'.now()->format('Y-m-d_His').'.pdf', [
                'Attachment' => false,
            ]);
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildViewData(Collection $tickets, array $filters): array
    {
        $rows = $tickets->map(fn (Ticket $ticket) => [
            'date_reception' => $ticket->created_at?->format('d/m/Y') ?? '-',
            'date_ticket' => $ticket->date_ticket?->format('d/m/Y') ?? '-',
            'numero_ticket' => $ticket->numero_ticket ?? '-',
            'usine' => $ticket->usine?->nom_usine ?? '-',
            'poids' => $ticket->poids ? number_format((float) $ticket->poids, 0, '', ' ') : '-',
            'prix_unitaire' => blank($ticket->prix_unitaire) || (float) $ticket->prix_unitaire == 0
                ? 'En attente'
                : number_format((float) $ticket->prix_unitaire, 2, '.', ''),
            'agent' => $ticket->agent?->full_name ?? '-',
            'pont' => $ticket->pont?->nom_pont ?? '—',
            'vehicule' => $ticket->vehicule?->matricule_vehicule ?? '-',
        ])->values()->all();

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'tickets' => $rows,
            'totalTickets' => $tickets->count(),
            'totalPoids' => number_format((float) $tickets->sum('poids'), 0, '', ' '),
            'periode' => $this->periodeLabel($tickets, $filters),
            'criteria' => $this->criteriaLabels($filters),
            'printedAt' => now()->format('d/m/Y H:i'),
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  array<string, mixed>  $filters
     */
    private function periodeLabel(Collection $tickets, array $filters): string
    {
        if (! empty($filters['date_debut']) || ! empty($filters['date_fin'])) {
            $debut = ! empty($filters['date_debut'])
                ? Carbon::parse($filters['date_debut'])->format('d/m/Y')
                : '…';
            $fin = ! empty($filters['date_fin'])
                ? Carbon::parse($filters['date_fin'])->format('d/m/Y')
                : '…';

            return $debut.' au '.$fin;
        }

        $datedTickets = $tickets->filter(fn (Ticket $ticket) => $ticket->date_ticket !== null);

        if ($datedTickets->isEmpty()) {
            return 'Non définie';
        }

        $debut = $datedTickets->min('date_ticket');
        $fin = $datedTickets->max('date_ticket');

        return Carbon::parse($debut)->format('d/m/Y').' au '.Carbon::parse($fin)->format('d/m/Y');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    private function criteriaLabels(array $filters): array
    {
        $labels = [];

        if (($filters['numero_ticket'] ?? '') !== '') {
            $labels[] = 'N° ticket : '.$filters['numero_ticket'];
        }

        if (! empty($filters['agent_id'])) {
            $agent = Agent::query()->find($filters['agent_id']);
            $labels[] = 'Agent : '.($agent?->full_name ?? $filters['agent_id']);
        }

        if (! empty($filters['usine_id'])) {
            $usine = Usine::query()->find($filters['usine_id']);
            $labels[] = 'Usine : '.($usine?->nom_usine ?? $filters['usine_id']);
        }

        if (! empty($filters['vehicule_id'])) {
            $vehicule = Vehicule::query()->find($filters['vehicule_id']);
            $labels[] = 'Véhicule : '.($vehicule?->matricule_vehicule ?? $filters['vehicule_id']);
        }

        if ($labels === []) {
            $labels[] = 'Tous les tickets';
        }

        return $labels;
    }
}
