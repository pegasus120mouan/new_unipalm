<?php

namespace App\Http\Controllers;

use App\Models\SortieDiverse;
use App\Services\SortieDiverseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SortieDiverseController extends Controller
{
    public function __construct(
        private readonly SortieDiverseService $sortieDiverseService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $dateDebut = $request->query('date_debut');
        $dateFin = $request->query('date_fin');

        $query = SortieDiverse::query()
            ->orderByDesc('date_sortie')
            ->orderByDesc('id_sorties');

        if ($dateDebut) {
            $query->whereDate('date_sortie', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->whereDate('date_sortie', '<=', $dateFin);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('numero_sorties', 'like', '%'.$search.'%')
                    ->orWhere('motifs', 'like', '%'.$search.'%');
            });
        }

        $sorties = $query->paginate(15)->withQueryString();
        $stats = $this->sortieDiverseService->stats();

        return view('sorties.diverses.index', compact(
            'sorties',
            'search',
            'dateDebut',
            'dateFin',
            'stats',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'motifs' => ['required', 'string', 'max:2000'],
        ]);

        $sortie = $this->sortieDiverseService->create(
            (float) $validated['montant'],
            $validated['motifs'],
            $request->user(),
        );

        return redirect()
            ->route('sorties.diverses.index')
            ->with('success', 'Sortie diverse '.$sortie->numero_sorties.' de '
                .number_format((float) $sortie->montant, 0, ',', ' ').' FCFA enregistrée avec succès.');
    }

    public function destroy(SortieDiverse $sortieDiverse, Request $request): RedirectResponse
    {
        $numero = $sortieDiverse->numero_sorties;

        $this->sortieDiverseService->delete($sortieDiverse, $request->user());

        return redirect()
            ->route('sorties.diverses.index')
            ->with('success', "La sortie diverse {$numero} a été supprimée.");
    }
}
