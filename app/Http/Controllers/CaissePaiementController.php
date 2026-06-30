<?php

namespace App\Http\Controllers;

use App\Models\DemandeSortie;
use App\Services\CaissePaiementService;
use App\Services\CaisseService;
use App\Services\CompteAgentService;
use App\Services\DemandeSortiePaymentService;
use App\Services\SortieDiverseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CaissePaiementController extends Controller
{
    public function __construct(
        private readonly CaisseService $caisseService,
        private readonly CaissePaiementService $caissePaiementService,
        private readonly CompteAgentService $compteAgentService,
        private readonly DemandeSortiePaymentService $demandePaymentService,
        private readonly SortieDiverseService $sortieDiverseService,
    ) {}

    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'bordereaux');
        if (! in_array($tab, ['bordereaux', 'demandes', 'divers'], true)) {
            $tab = 'bordereaux';
        }

        $stats = $this->caisseService->resume();
        $soldeCaisse = $stats['solde_caisse'];
        $montantUtilisable = $stats['montant_utilisable'];

        $bordereaux = null;
        $demandes = null;
        $sorties = null;
        $agents = null;
        $financementByAgent = [];
        $filters = [];

        if ($tab === 'bordereaux') {
            $filters = [
                'search_numero' => trim((string) $request->query('search_numero', '')),
                'search_agent' => trim((string) $request->query('search_agent', '')),
                'date_debut' => $request->query('date_debut'),
                'date_fin' => $request->query('date_fin'),
                'statut' => trim((string) $request->query('statut', '')),
            ];

            $bordereaux = $this->compteAgentService->paginatedAllValidatedBordereaux($filters);
            $agents = $this->caissePaiementService->agentsForFilter();
            $financementByAgent = $this->caissePaiementService->financementStatsForAgents(
                $bordereaux->pluck('id_agent')->all(),
            );
        } elseif ($tab === 'demandes') {
            $filters = [
                'search' => trim((string) $request->query('search', '')),
                'date_debut' => $request->query('date_debut'),
                'date_fin' => $request->query('date_fin'),
                'statut' => trim((string) $request->query('statut', '')),
            ];

            $demandes = $this->caissePaiementService->paginatedDemandesAPayer($filters);
        } else {
            $filters = [
                'search' => trim((string) $request->query('search', '')),
                'date_debut' => $request->query('date_debut'),
                'date_fin' => $request->query('date_fin'),
            ];

            $sorties = $this->caissePaiementService->paginatedSortiesDiverses($filters);
        }

        return view('caisse.paiements.index', compact(
            'tab',
            'stats',
            'soldeCaisse',
            'montantUtilisable',
            'filters',
            'bordereaux',
            'demandes',
            'sorties',
            'agents',
            'financementByAgent',
        ));
    }

    public function storeDemandePayment(Request $request, DemandeSortie $demande): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        try {
            $this->demandePaymentService->pay($demande, $request->user(), (float) $validated['montant']);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput($request->only('payment_demande_id', 'montant'))
                ->withErrors(['paiement' => $e->getMessage()]);
        }

        $redirect = $validated['redirect_to'] ?? route('caisse.paiements.index', ['tab' => 'demandes']);

        return redirect()->to($redirect)
            ->with('success', 'Paiement de la demande enregistré avec succès.');
    }

    public function storeSortieDiverse(Request $request): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'motifs' => ['required', 'string', 'max:2000'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        try {
            $sortie = $this->sortieDiverseService->create(
                (float) $validated['montant'],
                $validated['motifs'],
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['paiement' => $e->getMessage()]);
        }

        $redirect = $validated['redirect_to'] ?? route('caisse.paiements.index', ['tab' => 'divers']);

        return redirect()->to($redirect)
            ->with('success', 'Sortie diverse '.$sortie->numero_sorties.' de '
                .number_format((float) $sortie->montant, 0, '', ' ').' FCFA enregistrée avec succès.');
    }
}
