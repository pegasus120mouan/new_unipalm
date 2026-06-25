<?php

namespace App\Http\Controllers;

use App\Models\Groupe;
use App\Services\CaisseService;
use App\Services\CompteGroupeService;
use App\Services\GroupeTicketPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CompteGroupeController extends Controller
{
    public function __construct(
        private readonly CompteGroupeService $compteGroupeService,
        private readonly GroupeTicketPaymentService $groupeTicketPaymentService,
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
            'statut' => trim((string) $request->query('statut', '')),
        ];

        $stats = $this->compteGroupeService->statsForGroupe($groupe->id_chef, $filters);
        $counts = $this->compteGroupeService->countsForGroupe($groupe->id_chef, $filters);
        $tickets = $this->compteGroupeService->paginatedTicketsForGroupe($groupe->id_chef, $filters);
        $soldeCaisse = $this->caisseService->getSolde();

        return view('comptes-groupes.show', compact(
            'groupe',
            'filters',
            'stats',
            'counts',
            'tickets',
            'soldeCaisse',
        ));
    }

    public function pay(Request $request, Groupe $groupe): RedirectResponse
    {
        $validated = $request->validate([
            'montant_paiement' => ['required', 'numeric', 'min:1'],
            'motif_paiement' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $details = $this->groupeTicketPaymentService->pay(
                $groupe,
                $request->user(),
                (float) $validated['montant_paiement'],
                $validated['motif_paiement'] ?? null,
            );

            return redirect()
                ->route('comptes-groupes.show', $groupe)
                ->with('success', 'Paiement de '.number_format($details['montant'], 0, '', ' ').' FCFA effectué avec succès.')
                ->with('paiement_details', $details);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['paiement' => $e->getMessage()]);
        }
    }
}
