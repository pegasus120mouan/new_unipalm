<?php

namespace App\Http\Controllers;

use App\Models\Banque;
use App\Models\Cca;
use App\Models\CcaMouvement;
use App\Services\CaisseService;
use App\Services\CcaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CcaController extends Controller
{
    public function __construct(
        private readonly CcaService $ccaService,
        private readonly CaisseService $caisseService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $ccas = Cca::query()
            ->withCount('mouvements')
            ->withTotaux()
            ->when($search !== '', fn ($query) => $query->where('nom', 'like', '%'.$search.'%'))
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        $totalPrete = (float) CcaMouvement::query()->whereIn('type_mouvement', CcaMouvement::typesAvance())->sum('montant');
        $totalRembourse = (float) CcaMouvement::query()->where('type_mouvement', CcaMouvement::TYPE_REMBOURSEMENT)->sum('montant');

        $stats = [
            'total' => Cca::query()->count(),
            'total_prete' => $totalPrete,
            'total_rembourse' => $totalRembourse,
            'reste' => max(0, $totalPrete - $totalRembourse),
        ];

        $ccasRemboursables = Cca::query()
            ->withTotaux()
            ->orderBy('nom')
            ->get()
            ->filter(fn (Cca $cca) => (float) $cca->total_prete - (float) $cca->total_rembourse > 0.009);

        $banques = Banque::query()->where('actif', true)->orderBy('nom_banque')->get();
        $montantUtilisableCaisse = $this->caisseService->getMontantUtilisable();

        return view('caisse.cca.index', compact(
            'ccas',
            'stats',
            'search',
            'ccasRemboursables',
            'banques',
            'montantUtilisableCaisse',
        ));
    }

    public function show(Request $request, Cca $cca): View
    {
        $filters = [
            'type' => (string) $request->query('type', 'all'),
            'search' => trim((string) $request->query('search', '')),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
        ];

        $cca = Cca::query()->withTotaux()->findOrFail($cca->id_cca);

        $mouvements = CcaMouvement::query()
            ->with(['banque', 'utilisateur'])
            ->where('id_cca', $cca->id_cca)
            ->when($filters['type'] === CcaMouvement::TYPE_PRET_CAISSE, fn ($query) => $query->where('type_mouvement', CcaMouvement::TYPE_PRET_CAISSE))
            ->when($filters['type'] === CcaMouvement::TYPE_PRET_AGENT, fn ($query) => $query->where('type_mouvement', CcaMouvement::TYPE_PRET_AGENT))
            ->when($filters['type'] === CcaMouvement::TYPE_REMBOURSEMENT, fn ($query) => $query->where('type_mouvement', CcaMouvement::TYPE_REMBOURSEMENT))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($inner) use ($search): void {
                    $inner->where('libelle', 'like', '%'.$search.'%')
                        ->orWhere('numero_cheque', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['date_debut'], fn ($query) => $query->whereDate('date_mouvement', '>=', $filters['date_debut']))
            ->when($filters['date_fin'], fn ($query) => $query->whereDate('date_mouvement', '<=', $filters['date_fin']))
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id_mouvement')
            ->paginate(15)
            ->withQueryString();

        $banques = Banque::query()->where('actif', true)->orderBy('nom_banque')->get();
        $montantUtilisableCaisse = $this->caisseService->getMontantUtilisable();
        $reste = max(0, (float) $cca->total_prete - (float) $cca->total_rembourse);

        return view('caisse.cca.show', compact('cca', 'mouvements', 'filters', 'banques', 'montantUtilisableCaisse', 'reste'));
    }

    public function rembourser(Request $request): RedirectResponse
    {
        $request->merge([
            'montant' => preg_replace('/\s+/', '', (string) $request->input('montant', '')),
            'numero_cheque' => trim((string) $request->input('numero_cheque', '')) ?: null,
        ]);

        $validated = $request->validate([
            'id_cca' => ['required', 'integer', Rule::exists('ccas', 'id_cca')],
            'source' => ['required', Rule::in([
                CcaMouvement::SOURCE_BANQUE,
                CcaMouvement::SOURCE_CAISSE,
            ])],
            'id_banque' => [
                'required_if:source,'.CcaMouvement::SOURCE_BANQUE,
                'nullable',
                'integer',
                Rule::exists('banques', 'id_banque')->where('actif', true),
            ],
            'mode_paiement' => [
                'required_if:source,'.CcaMouvement::SOURCE_BANQUE,
                'nullable',
                Rule::in([CcaMouvement::MODE_VIREMENT, CcaMouvement::MODE_CHEQUE]),
            ],
            'numero_cheque' => ['nullable', 'string', 'max:100'],
            'montant' => ['required', 'numeric', 'min:1'],
        ], [
            'id_banque.required_if' => 'Sélectionnez une banque.',
            'mode_paiement.required_if' => 'Choisissez Virement ou Chèque.',
        ]);

        $estBanque = $validated['source'] === CcaMouvement::SOURCE_BANQUE;
        if ($estBanque && $validated['mode_paiement'] === CcaMouvement::MODE_CHEQUE && empty($validated['numero_cheque'])) {
            return back()
                ->withErrors(['numero_cheque' => 'Saisissez le numéro du chèque.'])
                ->withInput()
                ->with('open_rembourser_modal', true);
        }

        $cca = Cca::query()->findOrFail($validated['id_cca']);
        $banque = $estBanque ? Banque::query()->findOrFail($validated['id_banque']) : null;
        $montant = (float) $validated['montant'];

        try {
            $this->ccaService->rembourser(
                $cca,
                $montant,
                $validated['source'],
                $banque,
                $validated['mode_paiement'] ?? null,
                $validated['numero_cheque'] ?? null,
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['rembourser' => $e->getMessage()])
                ->withInput()
                ->with('open_rembourser_modal', true);
        }

        $origine = $validated['source'] === CcaMouvement::SOURCE_BANQUE
            ? $banque->nom_banque.' ('.($validated['mode_paiement'] === CcaMouvement::MODE_CHEQUE ? 'chèque n° '.$validated['numero_cheque'] : 'virement').')'
            : 'la caisse';

        return redirect()
            ->back()
            ->with('success', 'Remboursement de '
                .number_format($montant, 0, ',', ' ')
                .' FCFA à '.$cca->nom.' effectué depuis '.$origine.'.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCca($request);

        Cca::create([
            'nom' => $validated['nom'],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('caisse.cca.index')
            ->with('success', 'CCA « '.$validated['nom'].' » ajouté.');
    }

    public function update(Request $request, Cca $cca): RedirectResponse
    {
        $validated = $this->validateCca($request, $cca);

        $cca->update(['nom' => $validated['nom']]);

        return redirect()
            ->route('caisse.cca.index')
            ->with('success', 'CCA « '.$cca->nom.' » modifié.');
    }

    public function destroy(Cca $cca): RedirectResponse
    {
        if ($cca->mouvements()->exists()) {
            return redirect()
                ->route('caisse.cca.index')
                ->withErrors(['cca' => 'Impossible de supprimer « '.$cca->nom.' » : il a déjà prêté à la caisse.']);
        }

        $nom = $cca->nom;
        $cca->delete();

        return redirect()
            ->route('caisse.cca.index')
            ->with('success', 'CCA « '.$nom.' » supprimé.');
    }

    private function validateCca(Request $request, ?Cca $cca = null): array
    {
        $request->merge([
            'nom' => trim((string) $request->input('nom', '')),
        ]);

        return $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ccas', 'nom')->ignore($cca?->id_cca, 'id_cca'),
            ],
        ], [
            'nom.unique' => 'Un CCA porte déjà ce nom.',
        ]);
    }
}
