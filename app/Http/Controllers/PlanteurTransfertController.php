<?php

namespace App\Http\Controllers;

use App\Models\PlanteurTransfert;
use App\Services\PlanteurTransfertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PlanteurTransfertController extends Controller
{
    public function __construct(
        private readonly PlanteurTransfertService $transferts,
    ) {}

    public function collecteurs(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => ['collecteurs' => $this->transferts->collecteurs()],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['success' => false, 'error' => $exception->getMessage()], 502);
        }
    }

    public function index(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'transferts' => $this->transferts->historique($id)->map(fn (PlanteurTransfert $t) => $this->present($t))->values(),
            ],
        ]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nouveau_collecteur_id' => ['required', 'integer', 'min:1'],
            'motif' => ['nullable', 'string', 'max:1000'],
        ], [
            'nouveau_collecteur_id.required' => 'Choisissez le nouveau collecteur.',
        ]);

        try {
            $transfert = $this->transferts->transferer(
                $id,
                (int) $validated['nouveau_collecteur_id'],
                $validated['motif'] ?? null,
                $request->user(),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['success' => false, 'error' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => sprintf(
                '%s transféré à %s avec ses %d champ(s).',
                $transfert->planteur_nom ?: 'Le planteur',
                $transfert->nouveau_collecteur_nom,
                $transfert->nb_champs,
            ),
            'data' => ['transfert' => $this->present($transfert)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PlanteurTransfert $transfert): array
    {
        return [
            'id' => $transfert->id,
            'date' => $transfert->created_at?->format('d/m/Y H:i'),
            'created_at' => $transfert->created_at?->toIso8601String(),
            'ancien_collecteur_id' => $transfert->ancien_collecteur_id,
            'ancien_collecteur' => $transfert->ancien_collecteur_nom ?: '—',
            'nouveau_collecteur_id' => $transfert->nouveau_collecteur_id,
            'nouveau_collecteur' => $transfert->nouveau_collecteur_nom ?: '—',
            'motif' => $transfert->motif,
            'nb_champs' => $transfert->nb_champs,
            'superficie_totale' => (float) $transfert->superficie_totale,
            'champs' => $transfert->champs ?? [],
            'utilisateur' => $transfert->utilisateur_nom ?: '—',
        ];
    }
}
