<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExportService
{
    /**
     * @var list<string>
     */
    private const HEADERS = [
        'Date ticket',
        'N° Ticket',
        'Usine',
        'Chargé de mission',
        'Pont',
        'Véhicule',
        'Poids (kg)',
        'Créé par',
        'Date création',
        'Prix unitaire',
        'Validation',
        'Vérification',
        'Montant',
        'Date paie',
        'Statut',
        'N° Bordereau',
    ];

    public function streamAll(): StreamedResponse
    {
        $query = $this->baseQuery()->orderByDesc('created_at')->orderByDesc('id_ticket');

        return $this->streamQuery(
            $query,
            'tickets_tous_'.now()->format('Y-m-d_His').'.csv'
        );
    }

    public function streamPeriod(string $dateDebut, string $dateFin): StreamedResponse
    {
        $debut = Carbon::parse($dateDebut)->toDateString();
        $fin = Carbon::parse($dateFin)->toDateString();

        $query = $this->baseQuery()
            ->whereDate('created_at', '>=', $debut)
            ->whereDate('created_at', '<=', $fin)
            ->orderBy('created_at')
            ->orderBy('id_ticket');

        return $this->streamQuery(
            $query,
            sprintf('tickets_%s_%s.csv', Carbon::parse($debut)->format('Ymd'), Carbon::parse($fin)->format('Ymd'))
        );
    }

    /**
     * @return Builder<Ticket>
     */
    private function baseQuery(): Builder
    {
        return Ticket::query()
            ->visibleToCurrentUser()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur', 'pont']);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    private function streamQuery(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::HEADERS, ';');

            $query->chunkById(500, function ($tickets) use ($handle) {
                foreach ($tickets as $ticket) {
                    fputcsv($handle, $this->mapTicketRow($ticket), ';');
                }
            }, 'id_ticket');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<string>
     */
    private function mapTicketRow(Ticket $ticket): array
    {
        $prixUnitaire = blank($ticket->prix_unitaire) || (float) $ticket->prix_unitaire == 0
            ? 'En attente'
            : number_format((float) $ticket->prix_unitaire, 0, '', ' ');

        $montant = blank($ticket->montant_paie)
            ? 'En attente'
            : number_format((float) $ticket->montant_paie, 0, '', ' ');

        return [
            $ticket->date_ticket?->format('d/m/Y') ?? '',
            $ticket->numero_ticket ?? '',
            $ticket->usine?->nom_usine ?? '',
            $ticket->agent?->full_name ?? '',
            $ticket->pont
                ? trim(($ticket->pont->code_pont ? $ticket->pont->code_pont.' — ' : '').$ticket->pont->nom_pont)
                : '',
            $ticket->vehicule?->matricule_vehicule ?? '',
            $ticket->poids ? number_format((float) $ticket->poids, 0, '', ' ') : '',
            $ticket->utilisateur?->full_name ?? '',
            $ticket->created_at?->format('d/m/Y H:i') ?? '',
            $prixUnitaire,
            $ticket->date_validation_boss?->format('d/m/Y') ?? 'En cours',
            $ticket->isVerified() ? 'Vérifié' : 'Non vérifié',
            $montant,
            $ticket->date_paie?->format('d/m/Y') ?? 'Non payé',
            $ticket->statut_ticket === 'soldé' ? 'Soldé' : 'Non soldé',
            $ticket->numero_bordereau ?? '',
        ];
    }
}
