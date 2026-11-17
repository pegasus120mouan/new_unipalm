<?php

namespace App\Console\Commands;

use App\Models\PrixUnitaire;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Console\Command;

class AppliquerPrixUnitairesCommand extends Command
{
    protected $signature = 'prix-unitaires:appliquer
                            {--usine= : Limiter à un id_usine}
                            {--only-mismatch : Uniquement les tickets dont le prix diffère du catalogue}
                            {--dry-run : Afficher le détail sans modifier}';

    protected $description = 'Réapplique les prix catalogue aux tickets non payés et recalcule les bordereaux liés';

    public function handle(TicketService $ticketService): int
    {
        $usineId = $this->option('usine');
        $onlyMismatch = (bool) $this->option('only-mismatch');
        $dryRun = (bool) $this->option('dry-run');

        $query = PrixUnitaire::query()
            ->with('usine')
            ->orderBy('id_usine')
            ->orderBy('date_debut');

        if ($usineId !== null && $usineId !== '') {
            $query->where('id_usine', (int) $usineId);
        }

        $prixUnitaires = $query->get();

        if ($prixUnitaires->isEmpty()) {
            $this->warn('Aucun prix unitaire trouvé.');

            return self::SUCCESS;
        }

        $totalTickets = 0;
        $bordereaux = [];

        foreach ($prixUnitaires as $prix) {
            $dateDebut = $prix->date_debut?->format('Y-m-d');
            $dateFin = $prix->date_fin?->format('Y-m-d');

            if (! $dateDebut) {
                continue;
            }

            $ticketsQuery = Ticket::query()
                ->where('id_usine', $prix->id_usine)
                ->whereNull('date_paie')
                ->whereDate('date_ticket', '>=', $dateDebut);

            if ($dateFin) {
                $ticketsQuery->whereDate('date_ticket', '<=', $dateFin);
            }

            if ($onlyMismatch) {
                $ticketsQuery->where(function ($q) use ($prix) {
                    $q->whereNull('prix_unitaire')
                        ->orWhere('prix_unitaire', 0)
                        ->orWhere('prix_unitaire', '!=', (float) $prix->prix);
                });
            }

            if ($dryRun) {
                $n = (clone $ticketsQuery)->count();
                if ($n === 0) {
                    continue;
                }

                $samples = (clone $ticketsQuery)
                    ->orderBy('id_ticket')
                    ->limit(5)
                    ->get(['numero_ticket', 'prix_unitaire', 'date_ticket', 'numero_bordereau']);

                $this->line(sprintf(
                    'Usine %s (%d) | %s → %s | catalogue %s | %d ticket(s) (dry-run)',
                    $prix->usine?->nom_usine ?? '?',
                    $prix->id_usine,
                    $dateDebut,
                    $dateFin ?? '∞',
                    number_format((float) $prix->prix, 0, '', ' '),
                    $n,
                ));

                foreach ($samples as $ticket) {
                    $this->line(sprintf(
                        '  - %s | date %s | prix actuel %s | bordereau %s',
                        $ticket->numero_ticket,
                        $ticket->date_ticket?->format('Y-m-d') ?? '-',
                        $ticket->prix_unitaire !== null ? number_format((float) $ticket->prix_unitaire, 0, '', ' ') : '—',
                        $ticket->numero_bordereau ?: '—',
                    ));
                }

                $totalTickets += $n;

                continue;
            }

            $result = $ticketService->applyPrixUnitaireToPendingTickets(
                (int) $prix->id_usine,
                (float) $prix->prix,
                $dateDebut,
                $dateFin,
                $onlyMismatch,
            );

            $n = $result['tickets'];
            foreach ($result['bordereaux'] as $numero) {
                $bordereaux[$numero] = true;
            }

            if ($n > 0) {
                $this->line(sprintf(
                    'Usine %s (%d) | %s → %s | prix %s | %d ticket(s)',
                    $prix->usine?->nom_usine ?? '?',
                    $prix->id_usine,
                    $dateDebut,
                    $dateFin ?? '∞',
                    number_format((float) $prix->prix, 0, '', ' '),
                    $n,
                ));
            }

            $totalTickets += $n;
        }

        if ($dryRun) {
            $this->info("Total à mettre à jour : {$totalTickets} ticket(s).");
        } else {
            $this->info("Total mis à jour : {$totalTickets} ticket(s).");
            $this->info('Bordereaux recalculés : '.count($bordereaux).'.');
        }

        return self::SUCCESS;
    }
}
