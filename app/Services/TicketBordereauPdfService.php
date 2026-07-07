<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TicketBordereauPdfService
{
    public function stream(int $idAgent, string $dateDebut, string $dateFin): Response
    {
        $data = $this->buildViewData($idAgent, $dateDebut, $dateFin);

        $filename = sprintf(
            'Bordereau_%s_%s_%s.pdf',
            preg_replace('/[^A-Za-z0-9_-]+/', '_', $data['agentName']),
            Carbon::parse($dateDebut)->format('Ymd'),
            Carbon::parse($dateFin)->format('Ymd'),
        );

        return Pdf::loadView('tickets.bordereau-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream($filename, ['Attachment' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(int $idAgent, string $dateDebut, string $dateFin): array
    {
        $agent = Agent::query()
            ->whereNull('date_suppression')
            ->find($idAgent);

        if (! $agent) {
            throw new NotFoundHttpException('Agent non trouvé.');
        }

        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = Carbon::parse($dateFin)->endOfDay();

        $tickets = Ticket::query()
            ->visibleToCurrentUser()
            ->with(['usine', 'vehicule'])
            ->where('id_agent', $idAgent)
            ->whereDate('created_at', '>=', $debut->toDateString())
            ->whereDate('created_at', '<=', $fin->toDateString())
            ->get()
            ->sortBy([
                fn (Ticket $ticket) => $ticket->usine?->nom_usine ?? '',
                fn (Ticket $ticket) => $ticket->created_at?->format('Y-m-d H:i:s') ?? '',
                fn (Ticket $ticket) => $ticket->id_ticket,
            ])
            ->values();

        $groups = [];
        $totalPoids = 0.0;
        $totalTickets = 0;

        foreach ($tickets as $ticket) {
            $usine = $ticket->usine?->nom_usine ?? 'N/A';
            $poids = (float) $ticket->poids;

            if (! isset($groups[$usine])) {
                $groups[$usine] = [
                    'usine' => $usine,
                    'tickets' => [],
                    'poids' => 0.0,
                    'count' => 0,
                ];
            }

            $groups[$usine]['tickets'][] = [
                'date_reception' => $ticket->created_at?->format('d/m/y') ?? '-',
                'date_ticket' => $ticket->date_ticket?->format('d/m/y') ?? '-',
                'vehicule' => $ticket->vehicule?->matricule_vehicule ?? '-',
                'numero_ticket' => $ticket->numero_ticket,
                'poids' => number_format($poids, 0, '', ' '),
            ];

            $groups[$usine]['poids'] += $poids;
            $groups[$usine]['count']++;
            $totalPoids += $poids;
            $totalTickets++;
        }

        foreach ($groups as &$group) {
            $group['poids_formatted'] = number_format($group['poids'], 0, '', ' ');
        }
        unset($group);

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'agentName' => mb_strtoupper($agent->full_name, 'UTF-8'),
            'dateDebut' => $debut->format('d/m/Y'),
            'dateFin' => $fin->format('d/m/Y'),
            'generatedAt' => now()->format('d/m/Y'),
            'groups' => array_values($groups),
            'totalPoids' => number_format($totalPoids, 0, '', ' '),
            'totalTickets' => $totalTickets,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ];
    }
}
