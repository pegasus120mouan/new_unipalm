<?php

namespace App\Services;

use App\Models\Commis;
use App\Models\PontBascule;
use Illuminate\Validation\ValidationException;

class CommisService
{
    public function __construct(
        private readonly AgentService $agentService,
    ) {}

    public function formatPersonName(?string $value): string
    {
        return $this->agentService->formatPersonName($value);
    }

    /**
     * @return list<array{id_pont: int, label: string}>
     */
    public function pontsDisponiblesPourAgent(int $idAgent, ?int $excludeCommisId = null): array
    {
        $pontsOccupes = Commis::query()
            ->whereNull('date_suppression')
            ->when($excludeCommisId, fn ($q) => $q->where('id_commis', '!=', $excludeCommisId))
            ->pluck('id_pont');

        return PontBascule::query()
            ->where('id_agent', $idAgent)
            ->whereNotIn('id_pont', $pontsOccupes)
            ->orderBy('code_pont')
            ->get(['id_pont', 'code_pont', 'nom_pont'])
            ->map(fn (PontBascule $pont) => [
                'id_pont' => (int) $pont->id_pont,
                'label' => trim($pont->code_pont.' — '.$pont->nom_pont),
            ])
            ->values()
            ->all();
    }

    public function assertPontAppartientAgent(int $idPont, int $idAgent): void
    {
        $ok = PontBascule::query()
            ->where('id_pont', $idPont)
            ->where('id_agent', $idAgent)
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'id_pont' => 'Ce pont n’est pas rattaché à l’agent sélectionné.',
            ]);
        }
    }

    public function generateUniquePin(): string
    {
        do {
            $pin = $this->agentService->generatePin();
        } while (
            Commis::query()
                ->whereNull('date_suppression')
                ->where('code_pin', $pin)
                ->exists()
        );

        return $pin;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function commisPourAgent(int $idAgent, ?int $idPontCourant = null): array
    {
        return Commis::query()
            ->with('pont')
            ->whereNull('date_suppression')
            ->where('id_agent', $idAgent)
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get()
            ->map(function (Commis $commis) use ($idPontCourant) {
                $label = $commis->full_name;
                if ($commis->pont && (int) $commis->id_pont !== (int) $idPontCourant) {
                    $label .= ' — '.$commis->pont->nom_pont;
                }

                return [
                    'id' => (int) $commis->id_commis,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    public function assignToPont(PontBascule $pont, ?int $idCommis): void
    {
        if ($idCommis === null) {
            Commis::query()
                ->whereNull('date_suppression')
                ->where('id_pont', $pont->id_pont)
                ->update([
                    'date_suppression' => now(),
                    'date_modification' => now(),
                ]);

            return;
        }

        if (! $pont->id_agent) {
            throw ValidationException::withMessages([
                'id_commis' => 'Un gérant doit être associé au pont avant d’assigner un commis.',
            ]);
        }

        $commis = Commis::query()
            ->whereNull('date_suppression')
            ->findOrFail($idCommis);

        if ((int) $commis->id_agent !== (int) $pont->id_agent) {
            throw ValidationException::withMessages([
                'id_commis' => 'Ce commis n’appartient pas au gérant du pont.',
            ]);
        }

        Commis::query()
            ->whereNull('date_suppression')
            ->where('id_pont', $pont->id_pont)
            ->where('id_commis', '!=', $commis->id_commis)
            ->update([
                'date_suppression' => now(),
                'date_modification' => now(),
            ]);

        $commis->update([
            'id_pont' => $pont->id_pont,
            'id_agent' => $pont->id_agent,
            'date_modification' => now(),
        ]);
    }
}
