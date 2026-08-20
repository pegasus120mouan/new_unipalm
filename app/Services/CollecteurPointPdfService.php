<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use RuntimeException;

class CollecteurPointPdfService
{
    public function __construct(
        private readonly CollecteurApiService $collecteurApi,
        private readonly PlanteurApiService $planteurApi,
    ) {}

    public function download(array $collecteur, int $collecteurId, string $dateDebut, string $dateFin): Response
    {
        $data = $this->buildViewData($collecteur, $collecteurId, $dateDebut, $dateFin);

        $fullName = trim(($collecteur['nom'] ?? '').' '.($collecteur['prenoms'] ?? ''));
        $safeName = preg_replace('/[^\w\-]+/u', '_', $fullName !== '' ? $fullName : 'collecteur_'.$collecteurId) ?: 'collecteur';

        $filename = 'Point_Plantations_'.$safeName.'_'.$dateDebut.'_'.$dateFin.'.pdf';

        return Pdf::loadView('plantations.collecteurs.point-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(array $collecteur, int $collecteurId, string $dateDebut, string $dateFin): array
    {
        $planteurs = $this->fetchPlanteursForPeriod($collecteurId, $dateDebut, $dateFin);

        try {
            $statsData = $this->collecteurApi->getStats($collecteurId, $dateDebut, $dateFin);
            $stats = $statsData['stats'];
        } catch (RuntimeException) {
            $stats = $this->computeStatsFromPlanteurs($planteurs);
        }

        $logoPath = public_path('assets/images/logo/logo.png');

        return [
            'collecteur' => $collecteur,
            'collecteurId' => $collecteurId,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'planteurs' => $planteurs,
            'stats' => $stats,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'generatedAt' => now(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPlanteursForPeriod(int $collecteurId, string $dateDebut, string $dateFin): array
    {
        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = Carbon::parse($dateFin)->endOfDay();
        $all = [];
        $page = 1;
        $pageSize = 100;

        do {
            $json = $this->planteurApi->getPlanteurs([
                'collecteur_id' => $collecteurId,
                'page' => $page,
                'limit' => $pageSize,
            ]);

            if (($json['success'] ?? false) !== true) {
                throw new RuntimeException($json['error'] ?? $json['message'] ?? 'Erreur lors de la récupération des plantations.');
            }

            $batch = is_array($json['data']['planteurs'] ?? null) ? $json['data']['planteurs'] : [];
            foreach ($batch as $planteur) {
                if (! is_array($planteur)) {
                    continue;
                }
                $date = $this->resolvePlanteurDate($planteur);
                if ($date === null) {
                    continue;
                }
                if ($date->gte($debut) && $date->lte($fin)) {
                    $all[] = $planteur;
                }
            }

            $totalPages = max(1, (int) ($json['data']['total_pages'] ?? 1));
            $page++;
        } while ($page <= $totalPages);

        usort($all, function (array $a, array $b): int {
            $da = $this->resolvePlanteurDate($a);
            $db = $this->resolvePlanteurDate($b);

            return ($db?->timestamp ?? 0) <=> ($da?->timestamp ?? 0);
        });

        return $all;
    }

    /**
     * @param  array<string, mixed>  $planteur
     */
    private function resolvePlanteurDate(array $planteur): ?Carbon
    {
        foreach (['date_enregistrement', 'created_at'] as $key) {
            $value = $planteur[$key] ?? null;
            if (! filled($value)) {
                continue;
            }
            try {
                return Carbon::parse((string) $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $planteurs
     * @return array{nombre_exploitants: int, superficie_totale: float, nombre_parcelles: int}
     */
    private function computeStatsFromPlanteurs(array $planteurs): array
    {
        $superficie = 0.0;
        $parcelles = 0;

        foreach ($planteurs as $planteur) {
            $cultures = is_array($planteur['cultures'] ?? null) ? $planteur['cultures'] : [];
            foreach ($cultures as $culture) {
                if (! is_array($culture)) {
                    continue;
                }
                $superficie += (float) ($culture['superficie_ha'] ?? 0);
                $cultureParcelles = is_array($culture['parcelles'] ?? null) ? $culture['parcelles'] : [];
                $parcelles += count($cultureParcelles);
            }

            if ($cultures === [] && is_array($planteur['parcelles'] ?? null)) {
                $parcelles += count($planteur['parcelles']);
            }
        }

        return [
            'nombre_exploitants' => count($planteurs),
            'superficie_totale' => $superficie,
            'nombre_parcelles' => $parcelles,
        ];
    }
}
