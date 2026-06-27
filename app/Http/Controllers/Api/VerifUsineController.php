<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifUsineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min(100, (int) config('verif.usines_per_page', 20)));

        $query = Usine::query()->orderBy('nom_usine');

        if ($search !== '') {
            $query->where('nom_usine', 'like', '%'.$search.'%');
        }

        $paginator = $query
            ->paginate($perPage, ['id_usine', 'nom_usine'], 'page', max(1, (int) $request->query('page', 1)));

        $usines = $paginator->getCollection()
            ->map(fn (Usine $usine) => [
                'id_usine' => $usine->id_usine,
                'nom_usine' => $usine->nom_usine,
            ])
            ->values()
            ->all();

        return response()->json([
            'usines' => $usines,
            'pagination' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }
}
