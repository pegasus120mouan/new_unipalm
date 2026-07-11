<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Bordereau;
use App\Models\DemandeAvanceGestCamions;
use App\Services\AgentBordereauPaymentService;
use App\Services\AgentTransactionsHistoryPdfService;
use App\Services\CaisseService;
use App\Services\CompteAgentService;
use App\Services\DemandeAvancePaymentService;
use App\Services\FinancementService;
use App\Services\PretService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;

class CompteAgentController extends Controller
{
    public function __construct(
        private readonly CompteAgentService $compteAgentService,
        private readonly FinancementService $financementService,
        private readonly PretService $pretService,
        private readonly CaisseService $caisseService,
        private readonly AgentBordereauPaymentService $bordereauPaymentService,
        private readonly AgentTransactionsHistoryPdfService $transactionsHistoryPdfService,
        private readonly DemandeAvancePaymentService $demandeAvancePaymentService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search_nom' => trim((string) $request->query('search_nom', '')),
            'search_prenom' => trim((string) $request->query('search_prenom', '')),
            'search_contact' => trim((string) $request->query('search_contact', '')),
            'search_chef' => trim((string) $request->query('search_chef', '')),
        ];

        $totalAgents = $this->compteAgentService->totalAgentsCount();
        $globalStats = $this->compteAgentService->globalStats();
        $agents = $this->compteAgentService->paginatedAgentSummaries($filters);
        $hasFilters = collect($filters)->contains(fn ($value) => $value !== '');

        return view('comptes-agents.index', compact(
            'filters',
            'totalAgents',
            'globalStats',
            'agents',
            'hasFilters',
        ));
    }

    public function show(Request $request, Agent $agent): View
    {
        abort_if($agent->date_suppression !== null, 404);

        $agent->load('groupe');

        $filters = [
            'statut_ticket' => trim((string) $request->query('statut_ticket', '')),
            'statut_bordereau' => trim((string) $request->query('statut_bordereau', '')),
        ];

        $section = (string) $request->query('section', 'tickets');
        $activeSection = in_array($section, ['tickets', 'bordereaux', 'financement'], true)
            ? $section
            : 'tickets';

        $financementStats = $this->financementService->statsForAgent($agent->id_agent);
        $pretStats = $this->pretService->statsForAgent($agent->id_agent);
        $financialStats = $this->compteAgentService->financialStatsForAgent(
            $agent->id_agent,
            (float) $financementStats['solde_financement'],
        );
        $counts = $this->compteAgentService->countsForAgent($agent->id_agent);
        $this->bordereauPaymentService->syncTicketsForAgent($agent->id_agent);
        $tickets = $this->compteAgentService->paginatedAgentTickets($agent->id_agent, $filters);
        $bordereaux = $this->compteAgentService->paginatedAgentBordereaux($agent->id_agent, $filters);
        $soldeCaisse = $this->caisseService->getSolde();
        $montantUtilisable = $this->caisseService->getMontantUtilisable();

        $demandesAvance = null;
        $demandesAvanceError = null;
        $counts['demandes_avance'] = 0;

        try {
            $demandesAvance = DemandeAvanceGestCamions::query()
                ->where('id_agent', $agent->id_agent)
                ->orderByDesc('date_demande')
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'demandes_page')
                ->withQueryString();

            $counts['demandes_avance'] = DemandeAvanceGestCamions::query()
                ->where('id_agent', $agent->id_agent)
                ->where('statut', 'en_attente')
                ->count();
        } catch (\Throwable $e) {
            report($e);
            $demandesAvanceError = 'Impossible de lire les demandes d\'avance gest-camions. '
                .'Vérifiez GEST_CAMIONS_DB_* sur Unipalm (host, base, user, mot de passe) '
                .'et que la table demandes_avance existe sur cette base.';
            $demandesAvance = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                20,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('comptes-agents.show', compact(
            'agent',
            'filters',
            'activeSection',
            'financementStats',
            'pretStats',
            'financialStats',
            'counts',
            'tickets',
            'bordereaux',
            'soldeCaisse',
            'montantUtilisable',
            'demandesAvance',
            'demandesAvanceError',
        ));
    }

    public function storeDemandeFinancement(Request $request, Agent $agent): RedirectResponse
    {
        abort_if($agent->date_suppression !== null, 404);

        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'motif' => ['required', 'string', 'max:1000'],
        ]);

        $financement = $this->financementService->createDemande(
            (int) $agent->id_agent,
            (float) $validated['montant'],
            $validated['motif'],
        );

        return redirect()
            ->route('comptes-agents.show', [
                'agent' => $agent->id_agent,
                'section' => 'financement',
            ])
            ->with(
                'success',
                'Demande de financement '.$financement->code_affiche
                .' enregistrée. Elle est en attente de validation.'
            );
    }

    public function storeDemandeAvancePayment(Request $request, int $demande): RedirectResponse
    {
        $demandeModel = DemandeAvanceGestCamions::query()->findOrFail($demande);

        try {
            $this->demandeAvancePaymentService->payer($demandeModel, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['paiement' => $e->getMessage()]);
        }

        return redirect()
            ->route('comptes-agents.show', [
                'agent' => $demandeModel->id_agent,
                'section' => 'financement',
            ])
            ->with(
                'success',
                'Avance de '.number_format((float) $demandeModel->montant, 0, ',', ' ')
                .' FCFA payée. Solde chef de groupe et caisse débités.'
            );
    }

    public function storeBordereauPayment(Request $request, Bordereau $bordereau): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'source_paiement' => ['required', 'in:transactions,financement,cheque'],
            'numero_cheque' => ['nullable', 'string', 'max:50'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        if ($validated['source_paiement'] === 'cheque' && blank($validated['numero_cheque'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['paiement' => 'Le numéro de chèque est obligatoire.']);
        }

        try {
            $recuId = $this->bordereauPaymentService->pay($bordereau, $request->user(), $validated);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['paiement' => $e->getMessage()]);
        }

        $redirect = $validated['redirect_to'] ?? route('comptes-agents.show', [
            'agent' => $bordereau->id_agent,
            'section' => 'bordereaux',
        ]);

        return redirect()->to($redirect)
            ->with('success', 'Paiement du bordereau enregistré avec succès.')
            ->with('last_recu_id', $recuId);
    }

    public function transactionsHistoryPdf(Request $request, Agent $agent): Response
    {
        abort_if($agent->date_suppression !== null, 404);

        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        return $this->transactionsHistoryPdfService->download(
            $agent,
            $validated['date_debut'],
            $validated['date_fin'],
        );
    }
}
