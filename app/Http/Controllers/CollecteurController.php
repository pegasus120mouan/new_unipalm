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

    public function show(Request $request, int $id): View|\Illuminate\Http\RedirectResponse
    {
        $collecteur = $this->collecteurApi->getUtilisateur($id);

        if (! $collecteur) {
            return redirect()
                ->route('plantations.collecteurs')
                ->withErrors(['collecteur' => 'Collecteur introuvable.']);
        }

        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $filtreActif = filled($dateDebut) || filled($dateFin);

        try {
            $statsData = $this->collecteurApi->getStats(
                $id,
                $dateDebut ?: null,
                $dateFin ?: null,
            );
        } catch (RuntimeException $exception) {
            $statsData = [
                'stats' => [
                    'nombre_exploitants' => 0,
                    'superficie_totale' => 0,
                    'nombre_parcelles' => 0,
                ],
                'repartition_cultures' => [],
                'evolution_mensuelle' => [],
                'derniers_planteurs' => [],
            ];
            session()->flash('warning', $exception->getMessage());
        }

        return view('plantations.collecteurs.show', [
            'collecteur' => $collecteur,
            'collecteurId' => $id,
            'dateDebut' => $dateDebut ?? '',
            'dateFin' => $dateFin ?? '',
            'filtreActif' => $filtreActif,
            'stats' => $statsData['stats'],
            'statsParCulture' => $statsData['repartition_cultures'],
            'statsMensuel' => $statsData['evolution_mensuelle'],
            'derniersExploitants' => $statsData['derniers_planteurs'],
        ]);
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
