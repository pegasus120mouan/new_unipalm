<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Usine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TicketUsinePdfService
{
    public function stream(int $idUsine, string $dateDebut, string $dateFin): Response
    {
        $data = $this->buildViewData($idUsine, $dateDebut, $dateFin);

        $filename = sprintf(
            'Liste_tickets_%s_%s_%s.pdf',
            preg_replace('/[^A-Za-z0-9_-]+/', '_', $data['usineName']),
            Carbon::parse($dateDebut)->format('Ymd'),
            Carbon::parse($dateFin)->format('Ymd'),
        );

        return Pdf::loadView('tickets.usine-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream($filename, ['Attachment' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(int $idUsine, string $dateDebut, string $dateFin): array
    {
        $usine = Usine::query()->find($idUsine);

        if (! $usine) {
            throw new NotFoundHttpException('Usine non trouvée.');
        }

        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = Carbon::parse($dateFin)->endOfDay();

        $tickets = Ticket::query()
            ->visibleToCurrentUser()
            ->with(['vehicule'])
            ->where('id_usine', $idUsine)
            ->whereDate('created_at', '>=', $debut->toDateString())
            ->whereDate('created_at', '<=', $fin->toDateString())
            ->orderBy('created_at')
            ->orderBy('id_ticket')
            ->get();

        $rows = $tickets->map(fn (Ticket $ticket) => [
                'date_creation' => $ticket->created_at?->format('d/m/y') ?? '-',
                'date_ticket' => $ticket->date_ticket?->format('d/m/y') ?? '-',
                'vehicule' => $ticket->vehicule?->matricule_vehicule ?? '-',
                'numero_ticket' => $ticket->numero_ticket,
                'poids' => number_format((float) $ticket->poids, 0, '', ' '),
            ])
            ->values()
            ->all();

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'usineName' => $usine->nom_usine,
            'dateDebut' => $debut->format('d/m/y'),
            'dateFin' => $fin->format('d/m/y'),
            'tickets' => $rows,
            'totalPoids' => number_format((float) $tickets->sum('poids'), 0, '', ' '),
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ];
    }
}
