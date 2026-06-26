<?php

namespace App\Http\Controllers;

use App\Services\PlanteurApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PlantationController extends Controller
{
    public function __construct(
        private readonly PlanteurApiService $planteurApi,
    ) {}

    public function index(): View
    {
        return view('plantations.index');
    }

    public function api(Request $request): JsonResponse
    {
        try {
            if ($request->isMethod('post')) {
                return response()->json($this->planteurApi->post($request->all()));
            }

            $action = (string) $request->query('action', 'planteurs');

            if ($action === 'regions') {
                return response()->json($this->planteurApi->getRegions());
            }

            if ($action === 'stats') {
                return response()->json($this->planteurApi->getGlobalStats());
            }

            return response()->json($this->planteurApi->getPlanteurs($request->query()));
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }
    }
}
