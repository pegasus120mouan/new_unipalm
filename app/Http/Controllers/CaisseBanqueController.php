<?php

namespace App\Http\Controllers;

use App\Models\Banque;
use App\Models\Usine;
use App\Services\BanqueService;
use App\Services\UsinePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaisseBanqueController extends Controller
{
    public function __construct(
        private readonly BanqueService $banqueService,
        private readonly UsinePaymentService $usinePaymentService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Banque::query()
            ->orderBy('nom_banque');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('nom_banque', 'like', '%'.$search.'%')
                    ->orWhere('numero_compte', 'like', '%'.$search.'%')
                    ->orWhere('code_banque', 'like', '%'.$search.'%');
            });
        }

        $banques = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Banque::query()->count(),
            'actives' => Banque::query()->where('actif', true)->count(),
            'solde_total' => (float) Banque::query()->sum('solde'),
        ];

        return view('caisse.banques.index', compact('banques', 'search', 'stats'));
    }

    public function show(Request $request, Banque $banque): View
    {
        $filters = [
            'type' => (string) $request->query('type', 'all'),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
        ];

        $stats = $this->banqueService->statsForBanque($banque);
        $mouvements = $this->banqueService->paginatedMouvements($banque, $filters);

        $usines = Usine::query()
            ->orderBy('nom_usine')
            ->get()
            ->map(function (Usine $usine): Usine {
                $usine->setAttribute(
                    'reste_a_payer',
                    $this->usinePaymentService->resteAPayer($usine->id_usine),
                );

                return $usine;
            });

        return view('caisse.banques.show', compact('banque', 'stats', 'mouvements', 'filters', 'usines'));
    }

    public function approvisionnementCaisseIndex(Request $request): View
    {
        $filters = [
            'id_banque' => $request->query('id_banque'),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
        ];

        $stats = $this->banqueService->statsApprovisionnementsCaisse();
        $banques = Banque::query()
            ->where('actif', true)
            ->orderBy('nom_banque')
            ->get();
        $historique = $this->banqueService->paginatedApprovisionnementsCaisse($filters);

        return view('caisse.banques.approvisionnement-caisse', compact('stats', 'banques', 'historique', 'filters'));
    }

    public function storeApprovisionnementCaisse(Request $request): RedirectResponse
    {
        $request->merge([
            'montant' => $this->normalizedSoldeInput($request, 'montant'),
        ]);

        $validated = $request->validate([
            'id_banque' => [
                'required',
                'integer',
                Rule::exists('banques', 'id_banque')->where('actif', true),
            ],
            'montant' => ['required', 'numeric', 'min:1'],
        ]);

        $banque = Banque::query()->findOrFail($validated['id_banque']);

        try {
            $this->banqueService->approvisionnementCaisse(
                $banque,
                (float) $validated['montant'],
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('caisse.banques.approvisionnement-caisse.index')
                ->withErrors(['approvisionnement_caisse' => $e->getMessage()])
                ->withInput()
                ->with('open_appro_caisse_modal', true);
        }

        return redirect()
            ->route('caisse.banques.approvisionnement-caisse.index')
            ->with('success', 'Approvisionnement de '
                .number_format((float) $validated['montant'], 0, ',', ' ')
                .' FCFA effectué depuis '.$banque->nom_banque.' vers la caisse.');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'solde' => $this->normalizedSoldeInput($request),
            'nom_banque' => Banque::normalizeNom((string) $request->input('nom_banque', '')),
        ]);

        $validated = $request->validate([
            'nom_banque' => ['required', 'string', 'max:150', 'unique:banques,nom_banque'],
            'numero_compte' => ['nullable', 'string', 'max:100'],
            'solde' => ['nullable', 'numeric', 'min:0'],
        ]);

        $banque = Banque::query()->create([
            'code_banque' => Banque::genererCodeBanque(),
            'nom_banque' => $validated['nom_banque'],
            'numero_compte' => $validated['numero_compte'] ?? null,
            'solde' => 0,
            'actif' => true,
        ]);

        $soldeInitial = (float) ($validated['solde'] ?? 0);
        if ($soldeInitial > 0) {
            $this->banqueService->enregistrerSoldeInitial($banque, $soldeInitial, $request->user());
        }

        return redirect()
            ->route('caisse.banques.show', $banque)
            ->with('success', 'La banque « '.$banque->nom_banque.' » a été ajoutée (code '.$banque->code_banque.').');
    }

    public function storeApprovisionnement(Request $request, Banque $banque): RedirectResponse
    {
        $request->merge([
            'montant' => $this->normalizedSoldeInput($request, 'montant'),
        ]);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'libelle' => ['required', 'string', 'max:2000'],
        ]);

        $this->banqueService->approvisionnementManuel(
            $banque,
            (float) $validated['montant'],
            $validated['libelle'],
            $request->user(),
        );

        return redirect()
            ->route('caisse.banques.show', $banque)
            ->with('success', 'Approvisionnement de '
                .number_format((float) $validated['montant'], 0, ',', ' ')
                .' FCFA enregistré sur '.$banque->nom_banque.'.');
    }

    public function storePaiementUsine(Request $request, Banque $banque): RedirectResponse
    {
        $request->merge([
            'montant' => $this->normalizedSoldeInput($request, 'montant'),
        ]);

        $validated = $request->validate([
            'id_usine' => ['required', 'integer', 'exists:usines,id_usine'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', 'string', 'max:100'],
            'reference_paiement' => ['nullable', 'string', 'max:255'],
        ]);

        $usine = Usine::query()->findOrFail($validated['id_usine']);

        try {
            $this->banqueService->paiementUsine(
                $banque,
                $usine,
                (float) $validated['montant'],
                $validated['date_paiement'],
                $validated['mode_paiement'],
                $validated['reference_paiement'] ?? null,
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('caisse.banques.show', $banque)
                ->withErrors(['paiement_usine' => $e->getMessage()])
                ->withInput()
                ->with('open_usine_modal', true);
        }

        return redirect()
            ->route('caisse.banques.show', $banque)
            ->with('success', 'Paiement usine de '
                .number_format((float) $validated['montant'], 0, ',', ' ')
                .' FCFA enregistré pour '.$usine->nom_usine.' et crédité sur '.$banque->nom_banque.'.');
    }

    public function update(Request $request, Banque $banque): RedirectResponse
    {
        $request->merge([
            'solde' => $this->normalizedSoldeInput($request),
            'nom_banque' => Banque::normalizeNom((string) $request->input('nom_banque', '')),
        ]);

        $validated = $request->validate([
            'nom_banque' => [
                'required',
                'string',
                'max:150',
                Rule::unique('banques', 'nom_banque')->ignore($banque->id_banque, 'id_banque'),
            ],
            'numero_compte' => ['nullable', 'string', 'max:100'],
            'solde' => ['required', 'numeric', 'min:0'],
            'actif' => ['sometimes', 'boolean'],
        ]);

        $banque->update([
            'nom_banque' => $validated['nom_banque'],
            'numero_compte' => $validated['numero_compte'] ?? null,
            'solde' => (float) $validated['solde'],
            'actif' => $request->boolean('actif', $banque->actif),
        ]);

        return redirect()
            ->route('caisse.banques.index')
            ->with('success', 'La banque « '.$banque->nom_banque.' » a été mise à jour.');
    }

    public function destroy(Banque $banque): RedirectResponse
    {
        $nom = $banque->nom_banque;
        $banque->delete();

        return redirect()
            ->route('caisse.banques.index')
            ->with('success', "La banque « {$nom} » a été supprimée.");
    }

    private function normalizedSoldeInput(Request $request, string $key = 'solde'): string
    {
        $value = preg_replace('/\s+/', '', (string) $request->input($key, ''));

        return $value === '' ? '0' : $value;
    }
}
