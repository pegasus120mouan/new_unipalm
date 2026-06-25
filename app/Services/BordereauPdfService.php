<?php

namespace App\Services;

use App\Models\Bordereau;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BordereauPdfService
{
    public function stream(string $numeroBordereau): Response
    {
        $data = $this->buildViewData($numeroBordereau);

        return Pdf::loadView('bordereaux.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream('Bordereau_'.$numeroBordereau.'.pdf', ['Attachment' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(string $numeroBordereau): array
    {
        $bordereau = Bordereau::query()
            ->with('agent')
            ->where('numero_bordereau', $numeroBordereau)
            ->first();

        if (! $bordereau) {
            throw new NotFoundHttpException('Bordereau non trouvé.');
        }

        $tickets = Ticket::query()
            ->with(['usine', 'vehicule'])
            ->where('numero_bordereau', $numeroBordereau)
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
            $montant = $poids * (float) $ticket->prix_unitaire;

            if (! isset($groups[$usine])) {
                $groups[$usine] = [
                    'usine' => $usine,
                    'tickets' => [],
                    'poids' => 0.0,
                    'montant' => 0.0,
                ];
            }

            $groups[$usine]['tickets'][] = [
                'date' => $ticket->date_ticket?->format('d/m/Y') ?? '-',
                'numero' => $ticket->numero_ticket,
                'usine' => $usine,
                'vehicule' => $ticket->vehicule?->matricule_vehicule ?? '-',
                'poids' => $poids,
                'prix_unitaire' => (float) $ticket->prix_unitaire,
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
            'totalMontant' => $totalMontant > 0 ? $totalMontant : (float) $bordereau->montant_total,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ];
    }
}
