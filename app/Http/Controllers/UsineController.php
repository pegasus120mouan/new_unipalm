<?php

namespace App\Http\Controllers;

use App\Models\Banque;
use App\Models\Ticket;
use App\Models\Usine;
use App\Services\BanqueService;
use App\Services\UsineLocationService;
use App\Services\UsinePaymentPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsineController extends Controller
{
    public function __construct(
        private readonly BanqueService $banqueService,
        private readonly UsinePaymentPdfService $paymentPdfService,
        private readonly UsineLocationService $locationService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $usinesQuery = Usine::query()
            ->withCount('tickets')
            ->orderBy('nom_usine');

        if ($search !== '') {
            $usinesQuery->where('nom_usine', 'like', '%'.$search.'%');
        }

        $usines = $usinesQuery
            ->paginate(15)
            ->withQueryString();

        return view('usines.index', compact('usines', 'search'));
    }

    public function amounts(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $usinesQuery = Usine::query()
            ->select([
                'usines.id_usine',
                'usines.nom_usine',
                DB::raw('COALESCE(SUM(t.montant_paie), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(t.montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(t.montant_reste), 0) AS reste_a_payer'),
            ])
            ->leftJoin('tickets as t', function ($join) {
                $join->on('usines.id_usine', '=', 't.id_usine')
                    ->whereNotNull('t.date_validation_boss');
            })
            ->groupBy('usines.id_usine', 'usines.nom_usine')
            ->orderBy('usines.nom_usine');

        if ($search !== '') {
            $usinesQuery->where('usines.nom_usine', 'like', '%'.$search.'%');
        }

        $usines = $usinesQuery
            ->paginate(15)
            ->withQueryString();

        $totals = Ticket::query()
            ->validated()
            ->select([
                DB::raw('COALESCE(SUM(montant_paie), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(montant_reste), 0) AS reste_a_payer'),
            ])
            ->first();

        $banques = Banque::query()
            ->where('actif', true)
            ->orderBy('nom_banque')
            ->get();

        return view('usines.amounts', compact('usines', 'totals', 'search', 'banques'));
    }

    public function amountsShow(Usine $usine): View
    {
        $monthlyAmounts = Ticket::query()
            ->validated()
            ->where('id_usine', $usine->id_usine)
            ->select([
                DB::raw("DATE_FORMAT(date_ticket, '%Y-%m') AS mois"),
                DB::raw('COALESCE(SUM(montant_paie), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(montant_reste), 0) AS reste_a_payer'),
                DB::raw('COUNT(id_ticket) AS nombre_tickets'),
            ])
            ->groupBy('mois')
            ->orderByDesc('mois')
            ->get();

        $totals = Ticket::query()
            ->validated()
            ->where('id_usine', $usine->id_usine)
            ->select([
                DB::raw('COALESCE(SUM(montant_paie), 0) AS total_montant'),
                DB::raw('COALESCE(SUM(montant_payer), 0) AS montant_paye'),
                DB::raw('COALESCE(SUM(montant_reste), 0) AS reste_a_payer'),
                DB::raw('COUNT(id_ticket) AS nombre_tickets'),
            ])
            ->first();

        return view('usines.amounts-show', compact('usine', 'monthlyAmounts', 'totals'));
    }

    public function paymentsHistoryPdf(Usine $usine): Response
    {
        return $this->paymentPdfService->download($usine);
    }

    public function storePayment(Request $request, Usine $usine): RedirectResponse
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
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', 'string', 'max:100'],
            'reference_paiement' => ['nullable', 'string', 'max:255'],
            'redirect_to' => ['nullable', 'string'],
            'payment_usine_id' => ['nullable', 'integer'],
        ]);

        $banque = Banque::query()->findOrFail($validated['id_banque']);

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
            return back()
                ->withErrors(['paiement' => $e->getMessage()])
                ->withInput()
                ->with('payment_usine_id', $usine->id_usine);
        }

        $redirectTo = $validated['redirect_to'] ?? route('usines.amounts.show', $usine);
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, url('/montants-usines'))) {
            $redirectTo = route('usines.amounts.show', $usine);
        }

        return redirect($redirectTo)
            ->with('success', 'Paiement de '
                .number_format((float) $validated['montant'], 0, ',', ' ')
                .' FCFA enregistré pour '.$usine->nom_usine.' et crédité sur '.$banque->nom_banque.'.');
    }

    public function location(Usine $usine): JsonResponse
    {
        $payload = $this->locationService->buildLocationPayload($usine);

        if ($payload === null) {
            return response()->json([
                'success' => false,
                'message' => 'Cette usine n\'a pas de coordonnées GPS.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->usineRules());

        Usine::query()->create([
            'nom_usine' => trim($validated['nom_usine']),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('usines.index')
            ->with('success', 'Usine enregistrée avec succès.');
    }

    public function update(Request $request, Usine $usine): RedirectResponse
    {
        $validated = $request->validate($this->usineRules($usine));

        $usine->update([
            'nom_usine' => trim($validated['nom_usine']),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return redirect()
            ->route('usines.index')
            ->with('success', 'Usine modifiée avec succès.');
    }

    /**
     * @return array<string, mixed>
     */
    private function usineRules(?Usine $usine = null): array
    {
        return [
            'nom_usine' => [
                'required',
                'string',
                'max:255',
                Rule::unique('usines', 'nom_usine')->ignore($usine?->id_usine, 'id_usine'),
            ],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
