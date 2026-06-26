<?php

namespace App\Http\Controllers;

use App\Services\CollecteurApiService;
use App\Services\ZoneApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CollecteurController extends Controller
{
    public function __construct(
        private readonly CollecteurApiService $collecteurApi,
        private readonly ZoneApiService $zoneApi,
    ) {}

    public function index(): View
    {
        return view('plantations.collecteurs.index');
    }

    public function api(Request $request): JsonResponse
    {
        try {
            if ($request->isMethod('post')) {
                $action = $request->input('action', 'create');

                return match ($action) {
                    'update' => response()->json($this->collecteurApi->updateUtilisateur($request->except('action'))),
                    'delete' => response()->json($this->collecteurApi->deleteUtilisateur((int) $request->input('id'))),
                    default => response()->json($this->collecteurApi->createUtilisateur($request->except('action'))),
                };
            }

            if ($request->query('action') === 'zones') {
                return response()->json($this->zoneApi->get(['action' => 'list']));
            }

            return response()->json($this->collecteurApi->getUtilisateurs($request->query()));
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        try {
            return response()->json(
                $this->collecteurApi->updatePhoto(
                    (int) $request->input('user_id'),
                    $request->file('photo')
                )
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }
    }
}
