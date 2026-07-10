<?php

namespace App\Http\Controllers;

use App\Models\BordereauAgentGestCamions;
use App\Models\Groupe;
use App\Services\CaisseService;
use App\Services\CompteGroupeService;
use App\Services\FinancementService;
use App\Services\GestCamionsBordereauPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CompteGroupeController extends Controller
{
    public function __construct(
        private readonly CompteGroupeService $compteGroupeService,
        private readonly GestCamionsBordereauPaymentService $gestCamionsBordereauPaymentService,
        private readonly FinancementService $financementService,
        private readonly CaisseService $caisseService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'chef_id' => trim((string) $request->query('chef_id', '')),
            'date_debut' => trim((string) $request->query('date_debut', '')),
            'date_fin' => trim((string) $request->query('date_fin', '')),
            'statut_paiement' => trim((string) $request->query('statut_paiement', '')),
        ];

        $allSummaries = $this->compteGroupeService->filteredGroupeSummaries($filters);
        $globalStats = $this->compteGroupeService->globalStatsFromSummaries($allSummaries);
        $groupes = $this->compteGroupeService->paginatedGroupeSummaries($filters);
        $chefsListe = Groupe::query()->orderBy('nom')->orderBy('prenoms')->get();
        $hasFilters = collect($filters)->contains(fn ($value) => $value !== '');

        return view('comptes-groupes.index', compact(
            'filters',
            'globalStats',
            'groupes',
            'chefsListe',
            'hasFilters',
        ));
    }

    public function show(Request $request, Groupe $groupe): View
    {
        $filters = [
            'date_debut' => trim((string) $request->query('date_debut', '')),
            'date_fin' => trim((string) $request->query('date_fin', '')),
            'statut_bordereau' => trim((string) $request->query('statut_bordereau', '')),
        ];

        $stats = $this->compteGroupeService->statsForGroupe($groupe->id_chef, $filters);
        $counts = $this->compteGroupeService->countsForGroupe($groupe->id_chef, $filters);
        $soldeChef = $this->compteGroupeService->soldeForGroupe($groupe->id_chef);
        $bordereaux = $this->compteGroupeService->paginatedBordereauxForGroupe($groupe->id_chef, $filters);
        $soldeCaisse = $this->caisseService->getSolde();
        $montantUtilisable = $this->caisseService->getMontantUtilisable();
        $gestCamionsUrl = (string) config('services.gest_camions.url', '');

        $financementByAgent = [];
        foreach ($bordereaux->pluck('id_agent')->unique()->filter() as $agentId) {
            $financementByAgent[(int) $agentId] = $this->financementService->statsForAgent((int) $agentId);
        }

        return view('comptes-groupes.show', compact(
            'groupe',
            'filters',
            'stats',
            'counts',
            'soldeChef',
            'bordereaux',
            'soldeCaisse',
            'montantUtilisable',
            'gestCamionsUrl',
            'financementByAgent',
        ));
    }

    public function payBordereau(Request $request, Groupe $groupe, int $bordereau): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/u', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'source_paiement' => ['required', 'in:transactions,financement,cheque'],
            'numero_cheque' => ['nullable', 'string', 'max:50'],
            'payment_bordereau_id' => ['nullable', 'integer'],
        ]);

        if ($validated['source_paiement'] === 'cheque' && blank($validated['numero_cheque'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['paiement' => 'Le numéro de chèque est obligatoire.']);
        }

        $bordereauModel = BordereauAgentGestCamions::query()->findOrFail($bordereau);

        $agentIds = $this->compteGroupeService->agentIdsForGroupe($groupe->id_chef);
        if (! $agentIds->contains((int) $bordereauModel->id_agent)) {
            abort(403, 'Ce bordereau n\'appartient pas à un agent de ce chef.');
        }

        try {
            $recuId = $this->gestCamionsBordereauPaymentService->pay(
                $bordereauModel,
                $request->user(),
                $validated,
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('payment_bordereau_id', $bordereauModel->id)
                ->withErrors(['paiement' => $e->getMessage()]);
        }

        return redirect()
            ->route('comptes-groupes.show', $groupe)
            ->with('success', 'Paiement de '.number_format((float) $validated['montant'], 0, '', ' ')
                .' FCFA enregistré pour le bordereau '.$bordereauModel->numero.'.')
            ->with('last_recu_id', $recuId);
    }
}
