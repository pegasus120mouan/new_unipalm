<?php

namespace App\Http\Controllers;

use App\Services\ApprovisionnementService;
use App\Services\CaisseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaisseSoldeController extends Controller
{
    public function __construct(
        private readonly ApprovisionnementService $approvisionnementService,
        private readonly CaisseService $caisseService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'origine' => (string) $request->query('origine', 'all'),
            'search' => trim((string) $request->query('search', '')),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
        ];

        $stats = $this->approvisionnementService->stats();
        $approvisionnements = $this->approvisionnementService->paginatedApprovisionnements($filters);

        return view('caisse.solde.index', compact('stats', 'approvisionnements', 'filters'));
    }

    public function storeApprovisionnement(Request $request): RedirectResponse
    {
        $request->merge([
            'montant' => preg_replace('/\s+/', '', (string) $request->input('montant', '')),
        ]);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $this->approvisionnementService->createManual(
            (float) $validated['montant'],
            'Approvisionnement caisse',
            $request->user(),
        );

        $redirectTo = $validated['redirect_to'] ?? route('caisse.solde.index');
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, url('/'))) {
            $redirectTo = route('caisse.solde.index');
        }

        $message = 'Approvisionnement de '
            .number_format((float) $validated['montant'], 0, ',', ' ')
            .' FCFA enregistré sur la caisse.';

        return redirect($redirectTo)->with('success', $message);
    }

    public function updateMontantUtilisable(Request $request): RedirectResponse
    {
        $request->merge([
            'montant_utilisable' => preg_replace('/\s+/', '', (string) $request->input('montant_utilisable', '')),
        ]);

        $validated = $request->validate([
            'montant_utilisable' => ['required', 'numeric', 'min:0.01'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        try {
            $this->caisseService->augmenterMontantUtilisable(
                (float) $validated['montant_utilisable'],
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['montant_utilisable' => $e->getMessage()])
                ->withInput()
                ->with('open_utilisable_modal', true);
        }

        $redirectTo = $validated['redirect_to'] ?? route('caisse.solde.index');
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, url('/'))) {
            $redirectTo = route('caisse.solde.index');
        }

        return redirect($redirectTo)
            ->with('success', 'Montant utilisable porté à '
                .number_format((float) $validated['montant_utilisable'], 0, ',', ' ')
                .' FCFA.');
    }
}
