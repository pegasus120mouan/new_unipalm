<?php

namespace App\Http\Controllers;

use App\Services\ZoneApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ZoneController extends Controller
{
    public function __construct(
        private readonly ZoneApiService $zoneApi,
    ) {}

    public function index(): View
    {
        return view('plantations.zones.index');
    }

    public function api(Request $request): JsonResponse
    {
        try {
            if ($request->isMethod('post')) {
                return response()->json($this->zoneApi->post($request->all()));
            }

            return response()->json($this->zoneApi->get($request->query()));
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }
    }
}
