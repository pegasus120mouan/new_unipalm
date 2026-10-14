<?php

namespace App\Http\Controllers;

use App\Services\PlantationExportService;
use App\Services\PlanteurApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlantationController extends Controller
{
    public function __construct(
        private readonly PlanteurApiService $planteurApi,
        private readonly PlantationExportService $exports,
    ) {}

    public function index(): View
    {
        return view('plantations.index');
    }

    public function create(): View
    {
        return view('plantations.create');
    }

    public function plantations(): View
    {
        return view('plantations.plantations');
    }

    public function export(Request $request, string $format): StreamedResponse|Response|JsonResponse
    {
        try {
            $query = $request->only(['q', 'region', 'village']);

            return match ($format) {
                'csv' => $this->exports->csv($query),
                'xls' => $this->exports->xls($query),
                'pdf' => $this->exports->pdf($query),
                default => response()->json([
                    'success' => false,
                    'error' => 'Format d\'export inconnu.',
                ], 404),
            };
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'error' => 'Impossible d\'exporter les plantations. Réessayez dans quelques instants.',
            ], 502);
        }
    }

    public function show(int $id): View
    {
        return view('plantations.show', ['planteurId' => $id]);
    }

    public function champs(int $id): View
    {
        return view('plantations.champs', ['planteurId' => $id]);
    }

    public function champShow(int $id, int $champId): View
    {
        return view('plantations.champ-show', [
            'planteurId' => $id,
            'champId' => $champId,
        ]);
    }

    public function edit(int $id): View
    {
        return view('plantations.edit', ['planteurId' => $id]);
    }

    public function api(Request $request): JsonResponse
    {
        try {
            if ($request->isMethod('post')) {
                return response()->json($this->planteurApi->post($request->all()));
            }

            $action = (string) $request->query('action', 'planteurs');

            if ($action === 'search') {
                return response()->json($this->planteurApi->searchPlanteurs($request->query()));
            }

            if ($action === 'plantations') {
                return response()->json($this->planteurApi->getPlantations($request->query()));
            }

            if ($action === 'regions') {
                return response()->json($this->planteurApi->getRegions());
            }

            if ($action === 'stats') {
                return response()->json($this->planteurApi->getGlobalStats());
            }

            if ($action === 'champ') {
                $id = (int) $request->query('id', 0);
                $champId = (int) $request->query('champ_id', 0);
                if ($id <= 0 || $champId <= 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Identifiant planteur ou champ manquant.',
                    ], 422);
                }

                return response()->json($this->planteurApi->getChampForPlanteur($id, $champId));
            }

            if ($action === 'champs' || $action === 'fiches') {
                $id = (int) $request->query('id', 0);
                if ($id <= 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Identifiant planteur manquant.',
                    ], 422);
                }

                return response()->json($this->planteurApi->getChampsForPlanteur($id));
            }

            if ($action === 'check_doublon') {
                return response()->json($this->planteurApi->checkDoublon($request->query()));
            }

            return response()->json($this->planteurApi->getPlanteurs($request->query()));
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'error' => 'Impossible de joindre l\'API planteurs. Réessayez dans quelques instants.',
            ], 502);
        }
    }
}
