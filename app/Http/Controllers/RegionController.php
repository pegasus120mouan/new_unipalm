<?php

namespace App\Http\Controllers;

use App\Services\RegionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class RegionController extends Controller
{
    public function __construct(
        private readonly RegionApiService $regionApi,
    ) {}

    public function index(): View
    {
        return view('plantations.regions.index');
    }

    public function api(Request $request): JsonResponse
    {
        try {
            if ($request->isMethod('post')) {
                return response()->json($this->regionApi->post($request->all()));
            }

            return response()->json($this->regionApi->get($request->query()));
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }
    }
}
