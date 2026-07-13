<?php

namespace App\Http\Controllers;

use App\Services\PlanteurApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DoublonPlanteurController extends Controller
{
    public function __construct(
        private readonly PlanteurApiService $planteurApi,
    ) {}

    public function index(): View
    {
        return view('plantations.doublons.index');
    }

    public function api(Request $request): JsonResponse
    {
        try {
            if ($request->isMethod('post')) {
                return response()->json($this->planteurApi->post($request->all()));
            }

            return response()->json($this->planteurApi->getDoublons());
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }
    }
}
