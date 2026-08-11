<?php

namespace App\Console\Commands;

use App\Models\PrixUnitaire;
use App\Services\TicketService;
use Illuminate\Console\Command;

class AppliquerPrixUnitairesCommand extends Command
{
    protected $signature = 'prix-unitaires:appliquer
                            {--usine= : Limiter à un id_usine}
                            {--dry-run : Afficher le nombre sans modifier}';

    protected $description = 'Applique rétroactivement les prix unitaires aux tickets non payés de chaque période';

    public function handle(TicketService $ticketService): int
    {
        $usineId = $this->option('usine');
        $dryRun = (bool) $this->option('dry-run');

        $query = PrixUnitaire::query()->orderBy('id_usine')->orderBy('date_debut');

        if ($usineId !== null && $usineId !== '') {
            $query->where('id_usine', (int) $usineId);
        }

        $prixUnitaires = $query->get();

        if ($prixUnitaires->isEmpty()) {
            $this->warn('Aucun prix unitaire trouvé.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($prixUnitaires as $prix) {
            $dateDebut = $prix->date_debut?->format('Y-m-d');
            $dateFin = $prix->date_fin?->format('Y-m-d');

            if (! $dateDebut) {
                continue;
            }

            if ($dryRun) {
                $countQuery = \App\Models\Ticket::query()
                    ->where('id_usine', $prix->id_usine)
                    ->whereNull('date_paie')
                    ->whereDate('date_ticket', '>=', $dateDebut);

                if ($dateFin) {
                    $countQuery->whereDate('date_ticket', '<=', $dateFin);
                }

                $n = $countQuery->count();
            } else {
                $n = $ticketService->applyPrixUnitaireToPendingTickets(
                    (int) $prix->id_usine,
                    (float) $prix->prix,
                    $dateDebut,
                    $dateFin,
                );
            }

            if ($n > 0) {
                $this->line(sprintf(
                    'Usine %d | %s → %s | prix %s | %d ticket(s)%s',
                    $prix->id_usine,
                    $dateDebut,
                    $dateFin ?? '∞',
                    number_format((float) $prix->prix, 0, '', ' '),
                    $n,
                    $dryRun ? ' (dry-run)' : '',
                ));
            }

            $total += $n;
        }

        $this->info($dryRun
            ? "Total à mettre à jour : {$total} ticket(s)."
            : "Total mis à jour : {$total} ticket(s).");

        return self::SUCCESS;
    }
}
