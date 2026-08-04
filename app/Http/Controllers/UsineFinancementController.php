<?php

namespace App\Http\Controllers;

use App\Models\Banque;
use App\Models\Usine;
use App\Services\BanqueService;
use App\Services\UsineFinancementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsineFinancementController extends Controller
{
    public function __construct(
        private readonly UsineFinancementService $financementService,
        private readonly BanqueService $banqueService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $usines = $this->financementService->paginatedUsineSummaries(['search' => $search]);

        return view('usines.financements.index', compact('usines', 'search'));
    }

    public function show(Request $request, Usine $usine): View
    {
        $filters = [
            'date_debut' => trim((string) $request->query('date_debut', '')),
            'date_fin' => trim((string) $request->query('date_fin', '')),
        ];

        $stats = $this->financementService->statsForUsine((int) $usine->id_usine);
        $financements = $this->financementService->paginatedHistory((int) $usine->id_usine, $filters);
        $banques = Banque::query()
            ->where('actif', true)
            ->orderBy('nom_banque')
            ->get();

        return view('usines.financements.show', compact(
            'usine',
            'stats',
            'financements',
            'filters',
            'banques',
        ));
    }

    public function store(Request $request, Usine $usine): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'id_banque' => [
                'required',
                'integer',
                Rule::exists('banques', 'id_banque')->where('actif', true),
            ],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_financement' => ['required', 'date'],
            'mode_paiement' => ['required', 'string', 'max:100'],
            'reference_paiement' => ['nullable', 'string', 'max:255'],
            'motif' => ['nullable', 'string', 'max:1000'],
        ]);

        $banque = Banque::query()->findOrFail($validated['id_banque']);

        try {
            $this->banqueService->financementUsine(
                $banque,
                $usine,
                (float) $validated['montant'],
                $validated['date_financement'],
                $validated['mode_paiement'],
                $validated['reference_paiement'] ?? null,
                $validated['motif'] ?? null,
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['financement' => $e->getMessage()])
                ->withInput()
                ->with('open_financement_modal', true);
        }

        return redirect()
            ->route('usines.financements.show', $usine)
            ->with('success', 'Financement de '
                .number_format((float) $validated['montant'], 0, ',', ' ')
                .' FCFA enregistré pour '.$usine->nom_usine
                .' et crédité sur '.$banque->nom_banque.'.');
    }
}
