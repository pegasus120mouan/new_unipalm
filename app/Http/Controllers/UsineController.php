<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Banque;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Usine;
use App\Models\UsineEntree;
use App\Models\UsineFinancement;
use App\Services\BanqueService;
use App\Services\TicketUsinePdfService;
use App\Services\UsineFinancementService;
use App\Services\UsineLocationService;
use App\Services\UsinePaymentPdfService;
use App\Services\UsinePaymentService;
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
        private readonly TicketUsinePdfService $ticketUsinePdfService,
        private readonly UsinePaymentService $usinePaymentService,
        private readonly UsineFinancementService $usineFinancementService,
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

    public function montants(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $usinesQuery = Usine::query()
            ->orderBy('nom_usine');

        if ($search !== '') {
            $usinesQuery->where('nom_usine', 'like', '%'.$search.'%');
        }

        $usines = $usinesQuery
            ->paginate(15)
            ->withQueryString();

        $usineIds = $usines->getCollection()->pluck('id_usine')->all();

        $montantsDus = collect();
        $montantsPayes = collect();
        $montantsFinancements = collect();

        if ($usineIds !== []) {
            $montantsDus = UsineEntree::query()
                ->whereIn('id_usine', $usineIds)
                ->select([
                    'id_usine',
                    DB::raw('COALESCE(SUM(ROUND(poids_usine * prix_unitaire, 2)), 0) AS montant_du'),
                ])
                ->groupBy('id_usine')
                ->pluck('montant_du', 'id_usine');

            $montantsPayes = Payment::query()
                ->whereIn('id_usine', $usineIds)
                ->select([
                    'id_usine',
                    DB::raw('COALESCE(SUM(montant), 0) AS montant_paye'),
                ])
                ->groupBy('id_usine')
                ->pluck('montant_paye', 'id_usine');

            $montantsFinancements = UsineFinancement::query()
                ->whereIn('id_usine', $usineIds)
                ->select([
                    'id_usine',
                    DB::raw('COALESCE(SUM(montant), 0) AS montant_financement'),
                ])
                ->groupBy('id_usine')
                ->pluck('montant_financement', 'id_usine');
        }

        $usines->setCollection(
            $usines->getCollection()->map(function (Usine $usine) use ($montantsDus, $montantsPayes, $montantsFinancements) {
                $montantDu = (float) ($montantsDus[$usine->id_usine] ?? 0);
                $montantPaye = (float) ($montantsPayes[$usine->id_usine] ?? 0);
                $montantFinancement = (float) ($montantsFinancements[$usine->id_usine] ?? 0);

                $usine->montant_du = $montantDu;
                $usine->montant_paye = $montantPaye;
                $usine->montant_financement = $montantFinancement;
                $usine->reste_a_payer = max(0, $montantDu - $montantPaye);

                return $usine;
            })
        );

        return view('usines.montants', compact('usines', 'search'));
    }

    public function montantsShow(Request $request, Usine $usine): View
    {
        $filters = [
            'id_agent' => trim((string) $request->query('id_agent', '')),
            'date_debut' => trim((string) $request->query('date_debut', '')),
            'date_fin' => trim((string) $request->query('date_fin', '')),
        ];

        $ticketsQuery = Ticket::query()
            ->validated()
            ->where('id_usine', $usine->id_usine);

        if ($filters['id_agent'] !== '' && ctype_digit($filters['id_agent'])) {
            $ticketsQuery->where('id_agent', (int) $filters['id_agent']);
        }

        if ($filters['date_debut'] !== '') {
            $ticketsQuery->whereDate('date_ticket', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin'] !== '') {
            $ticketsQuery->whereDate('date_ticket', '<=', $filters['date_fin']);
        }

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->whereIn('id_agent', function ($query) use ($usine) {
                $query->select('id_agent')
                    ->from('tickets')
                    ->where('id_usine', $usine->id_usine)
                    ->whereNotNull('id_agent')
                    ->distinct();
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id_agent', 'nom', 'prenom', 'numero_agent']);

        $savedEntrees = UsineEntree::query()
            ->where('id_usine', $usine->id_usine)
            ->get()
            ->keyBy(fn (UsineEntree $entree) => $entree->date_entree->format('Y-m-d'));

        $entrees = (clone $ticketsQuery)
            ->select([
                DB::raw('DATE(date_ticket) AS date_entree'),
                DB::raw('COALESCE(SUM(poids), 0) AS poids_unipalm'),
                DB::raw('COUNT(id_ticket) AS nombre_tickets'),
            ])
            ->groupBy(DB::raw('DATE(date_ticket)'))
            ->orderByDesc(DB::raw('DATE(date_ticket)'))
            ->get()
            ->map(function ($row) use ($savedEntrees) {
                $dateKey = (string) $row->date_entree;
                $saved = $savedEntrees->get($dateKey);
                $poidsUnipalm = (float) $row->poids_unipalm;
                $poidsUsine = $saved ? (float) $saved->poids_usine : 0.0;
                $prixUnitaire = $saved ? (float) $saved->prix_unitaire : 0.0;
                $montant = round($poidsUsine * $prixUnitaire, 2);

                return (object) [
                    'date_entree' => $dateKey,
                    'poids_unipalm' => $poidsUnipalm,
                    'poids_usine' => $poidsUsine,
                    'ecart' => round($poidsUnipalm - $poidsUsine, 2),
                    'prix_unitaire' => $prixUnitaire,
                    'montant' => $montant,
                    'nombre_tickets' => (int) $row->nombre_tickets,
                ];
            });

        $montantDu = (float) $entrees->sum('montant');
        $montantPaye = (float) Payment::query()
            ->where('id_usine', $usine->id_usine)
            ->sum('montant');
        $montantFinancement = (float) UsineFinancement::query()
            ->where('id_usine', $usine->id_usine)
            ->sum('montant');
        $resteAPayer = max(0, $montantDu - $montantPaye);

        $paiements = Payment::query()
            ->where('id_usine', $usine->id_usine)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get();

        $banques = Banque::query()
            ->where('actif', true)
            ->orderBy('nom_banque')
            ->get();

        return view('usines.montants-show', compact(
            'usine',
            'filters',
            'agents',
            'entrees',
            'montantDu',
            'montantPaye',
            'montantFinancement',
            'resteAPayer',
            'paiements',
            'banques',
        ));
    }

    public function storeEntree(Request $request, Usine $usine): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'date_entree' => ['required', 'date'],
            'poids_usine' => ['nullable', 'numeric', 'min:0'],
            'prix_unitaire' => ['nullable', 'numeric', 'min:0'],
            'field' => ['nullable', 'in:poids_usine,prix_unitaire'],
        ]);

        $existing = UsineEntree::query()
            ->where('id_usine', $usine->id_usine)
            ->whereDate('date_entree', $validated['date_entree'])
            ->first();

        $poidsUsine = array_key_exists('poids_usine', $validated) && $validated['poids_usine'] !== null
            ? (float) $validated['poids_usine']
            : (float) ($existing->poids_usine ?? 0);
        $prixUnitaire = array_key_exists('prix_unitaire', $validated) && $validated['prix_unitaire'] !== null
            ? (float) $validated['prix_unitaire']
            : (float) ($existing->prix_unitaire ?? 0);

        $entree = UsineEntree::query()->updateOrCreate(
            [
                'id_usine' => $usine->id_usine,
                'date_entree' => $validated['date_entree'],
            ],
            [
                'poids_usine' => $poidsUsine,
                'prix_unitaire' => $prixUnitaire,
            ],
        );

        $field = $validated['field'] ?? null;
        $message = match ($field) {
            'poids_usine' => 'Poids modifié avec succès.',
            'prix_unitaire' => 'Prix unitaire modifié avec succès.',
            default => 'Entrée enregistrée avec succès.',
        };

        $poidsUnipalm = (float) Ticket::query()
            ->validated()
            ->where('id_usine', $usine->id_usine)
            ->whereDate('date_ticket', $validated['date_entree'])
            ->sum('poids');

        $montant = round($poidsUsine * $prixUnitaire, 2);
        $ecart = round($poidsUnipalm - $poidsUsine, 2);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'field' => $field,
                'poids_usine' => $poidsUsine,
                'prix_unitaire' => $prixUnitaire,
                'ecart' => $ecart,
                'montant' => $montant,
            ]);
        }

        return redirect()
            ->route('usines.montants.show', array_merge(
                ['usine' => $usine],
                $request->only(['id_agent', 'date_debut', 'date_fin']),
            ))
            ->with('success', $message);
    }

    public function montantsDayPdf(Usine $usine, string $date): Response
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(404);
        }

        return $this->ticketUsinePdfService->streamByTicketDate((int) $usine->id_usine, $date);
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

        $fromMontants = $request->input('source') === 'montants';
        $soldeFinancement = $fromMontants
            ? $this->usineFinancementService->solde((int) $usine->id_usine)
            : 0.0;
        $useFinancement = $fromMontants && $soldeFinancement > 0;

        $rules = [
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_paiement' => ['required', 'date'],
            'reference_paiement' => ['nullable', 'string', 'max:255'],
            'redirect_to' => ['nullable', 'string'],
            'payment_usine_id' => ['nullable', 'integer'],
            'source' => ['nullable', 'in:montants,amounts'],
        ];

        if ($useFinancement) {
            $rules['mode_paiement'] = ['nullable', 'string', 'max:100'];
            $rules['id_banque'] = ['nullable', 'integer'];
        } else {
            $rules['mode_paiement'] = ['required', 'string', 'max:100'];
            $rules['id_banque'] = [
                'required',
                'integer',
                Rule::exists('banques', 'id_banque')->where('actif', true),
            ];
        }

        $validated = $request->validate($rules);
        $montant = (float) $validated['montant'];

        try {
            if ($useFinancement) {
                $restePlafond = $this->resteAPayerBilan($usine);
                $this->usinePaymentService->createFromFinancement(
                    $usine,
                    $montant,
                    $validated['date_paiement'],
                    $validated['reference_paiement'] ?? null,
                    $request->user(),
                    $restePlafond,
                );

                $successMessage = 'Paiement de '
                    .number_format($montant, 0, ',', ' ')
                    .' FCFA enregistré pour '.$usine->nom_usine
                    .' et déduit du financement.';
            } else {
                $banque = Banque::query()->findOrFail($validated['id_banque']);
                $restePlafond = $fromMontants ? $this->resteAPayerBilan($usine) : null;

                $this->banqueService->paiementUsine(
                    $banque,
                    $usine,
                    $montant,
                    $validated['date_paiement'],
                    $validated['mode_paiement'],
                    $validated['reference_paiement'] ?? null,
                    $request->user(),
                    restePlafond: $restePlafond,
                    distributeToTickets: true,
                );

                $successMessage = 'Paiement de '
                    .number_format($montant, 0, ',', ' ')
                    .' FCFA enregistré pour '.$usine->nom_usine
                    .' et crédité sur '.$banque->nom_banque.'.';
            }
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['paiement' => $e->getMessage()])
                ->withInput()
                ->with('payment_usine_id', $usine->id_usine);
        }

        $defaultRedirect = $fromMontants
            ? route('usines.montants.show', $usine)
            : route('usines.amounts.show', $usine);

        $redirectTo = $validated['redirect_to'] ?? $defaultRedirect;
        if (! is_string($redirectTo) || ! $this->isAllowedUsinePaymentRedirect($redirectTo)) {
            $redirectTo = $defaultRedirect;
        }

        return redirect($redirectTo)->with('success', $successMessage);
    }

    private function isAllowedUsinePaymentRedirect(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return str_starts_with($path, '/montants-usines')
            || str_starts_with($path, '/usines/montants');
    }

    /**
     * Reste à payer : bilan des entrées − paiements (le financement usine est distinct).
     */
    private function resteAPayerBilan(Usine $usine): float
    {
        $savedEntrees = UsineEntree::query()
            ->where('id_usine', $usine->id_usine)
            ->get();

        $montantDu = (float) $savedEntrees->sum(
            fn (UsineEntree $entree) => round((float) $entree->poids_usine * (float) $entree->prix_unitaire, 2)
        );

        $montantPaye = (float) Payment::query()
            ->where('id_usine', $usine->id_usine)
            ->sum('montant');

        return max(0, $montantDu - $montantPaye);
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
