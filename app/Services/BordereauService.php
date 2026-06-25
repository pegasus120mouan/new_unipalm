<?php

namespace App\Services;

use App\Models\Bordereau;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class BordereauService
{
    public function eligibleTickets(int $idAgent, string $dateDebut, string $dateFin)
    {
        return Ticket::query()
            ->with(['usine', 'vehicule'])
            ->eligibleForBordereau($idAgent, $dateDebut, $dateFin)
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->get();
    }

    public function create(int $idAgent, string $dateDebut, string $dateFin): Bordereau
    {
        $tickets = $this->eligibleTickets($idAgent, $dateDebut, $dateFin);

        if ($tickets->isEmpty()) {
            throw new \InvalidArgumentException(
                'Aucun ticket validé disponible pour cet agent sur la période sélectionnée (date ticket).'
            );
        }

        return $this->createFromTickets(
            $idAgent,
            $dateDebut,
            $dateFin,
            $tickets->pluck('id_ticket')->all(),
        );
    }

    /**
     * @param  array<int>  $ticketIds
     */
    public function createFromTickets(int $idAgent, string $dateDebut, string $dateFin, array $ticketIds): Bordereau
    {
        $ticketIds = array_values(array_unique(array_map('intval', $ticketIds)));

        if ($ticketIds === []) {
            throw new \InvalidArgumentException('Sélectionnez au moins un ticket pour créer le bordereau.');
        }

        $tickets = Ticket::query()
            ->eligibleForBordereau($idAgent, $dateDebut, $dateFin)
            ->whereIn('id_ticket', $ticketIds)
            ->get();

        if ($tickets->count() !== count($ticketIds)) {
            throw new \InvalidArgumentException(
                'Un ou plusieurs tickets sélectionnés ne sont plus disponibles pour ce bordereau.'
            );
        }

        $poidsTotal = $tickets->sum(fn (Ticket $ticket) => (float) $ticket->poids);
        $montantTotal = $tickets->sum(fn (Ticket $ticket) => (float) $ticket->prix_unitaire * (float) $ticket->poids);
        $numeroBordereau = $this->generateNumeroBordereau();

        return DB::transaction(function () use (
            $idAgent,
            $dateDebut,
            $dateFin,
            $tickets,
            $poidsTotal,
            $montantTotal,
            $numeroBordereau,
        ) {
            $bordereau = Bordereau::query()->create([
                'numero_bordereau' => $numeroBordereau,
                'id_agent' => $idAgent,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'poids_total' => $poidsTotal,
                'montant_total' => $montantTotal,
                'montant_payer' => 0,
                'montant_reste' => $montantTotal,
                'statut_bordereau' => 'non soldé',
                'created_at' => now(),
            ]);

            Ticket::query()
                ->whereIn('id_ticket', $tickets->pluck('id_ticket'))
                ->update([
                    'numero_bordereau' => $numeroBordereau,
                    'statut_ticket' => 'non soldé',
                    'updated_at' => now(),
                ]);

            return $bordereau->fresh(['agent']);
        });
    }

    public function delete(Bordereau $bordereau): void
    {
        DB::transaction(function () use ($bordereau) {
            Ticket::query()
                ->where('numero_bordereau', $bordereau->numero_bordereau)
                ->update([
                    'numero_bordereau' => null,
                    'updated_at' => now(),
                ]);

            $bordereau->delete();
        });
    }

    public function validate(Bordereau $bordereau): Bordereau
    {
        if ($bordereau->isValidated()) {
            throw new \InvalidArgumentException('Ce bordereau est déjà validé.');
        }

        $bordereau->update([
            'date_validation_boss' => now(),
        ]);

        return $bordereau->fresh(['agent']);
    }

    private function generateNumeroBordereau(): string
    {
        return 'BORD-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
