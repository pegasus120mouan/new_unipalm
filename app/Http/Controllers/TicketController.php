<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\PontBascule;
use App\Models\Ticket;
use App\Models\Usine;
use App\Models\Vehicule;
use App\Services\TicketBordereauPdfService;
use App\Services\TicketExportService;
use App\Services\TicketService;
use App\Services\TicketUsinePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly TicketUsinePdfService $ticketUsinePdfService,
        private readonly TicketBordereauPdfService $ticketBordereauPdfService,
        private readonly TicketExportService $ticketExportService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->searchFiltersFromRequest($request);
        $isSearchRequested = $request->has('search');

        $query = $this->ticketQuery();

        if ($isSearchRequested) {
            $this->applySearchFilters($query, $filters);
        }

        $tickets = $query
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        return view('tickets.index', $this->formData($tickets, $filters, $isSearchRequested));
    }

    public function pdfByUsine(Request $request): Response
    {
        $validated = $request->validate([
            'id_usine' => ['required', 'integer', 'exists:usines,id_usine'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        return $this->ticketUsinePdfService->stream(
            (int) $validated['id_usine'],
            $validated['date_debut'],
            $validated['date_fin'],
        );
    }

    public function pdfBordereau(Request $request): Response
    {
        $validated = $request->validate([
            'id_agent' => ['required', 'integer', Rule::exists('agents', 'id_agent')->whereNull('date_suppression')],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        return $this->ticketBordereauPdfService->stream(
            (int) $validated['id_agent'],
            $validated['date_debut'],
            $validated['date_fin'],
        );
    }

    public function exportAll(): StreamedResponse
    {
        return $this->ticketExportService->streamAll();
    }

    public function exportPeriod(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        return $this->ticketExportService->streamPeriod(
            $validated['date_debut'],
            $validated['date_fin'],
        );
    }

    public function today(): View
    {
        $tickets = $this->ticketQuery()
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        return view('tickets.today', compact('tickets'));
    }

    public function pending(Request $request): View
    {
        $filters = [
            'agent_id' => $request->input('agent_id'),
            'usine_id' => $request->input('usine_id'),
            'vehicule_id' => $request->input('vehicule_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $query = $this->ticketQuery()->pending();

        if ($filters['agent_id']) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if ($filters['usine_id']) {
            $query->where('id_usine', (int) $filters['usine_id']);
        }

        if ($filters['vehicule_id']) {
            $query->where('vehicule_id', (int) $filters['vehicule_id']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('date_ticket', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_ticket', '<=', $filters['date_fin']);
        }

        $tickets = $query
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $usines = Usine::query()->orderBy('nom_usine')->get();
        $vehicules = Vehicule::query()->orderBy('matricule_vehicule')->get();

        return view('tickets.pending', compact('tickets', 'filters', 'agents', 'usines', 'vehicules'));
    }

    public function paid(Request $request): View
    {
        $filters = [
            'agent_id' => $request->input('agent_id'),
            'usine_id' => $request->input('usine_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'statut' => $request->input('statut'),
        ];

        $query = $this->ticketQuery()
            ->paid()
            ->whereNotNull('date_ticket');

        if ($filters['agent_id']) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if ($filters['usine_id']) {
            $query->where('id_usine', (int) $filters['usine_id']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('date_ticket', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_ticket', '<=', $filters['date_fin']);
        }

        if ($filters['statut'] === 'solde') {
            $query->where('montant_reste', '<=', 0);
        } elseif ($filters['statut'] === 'en_cours') {
            $query->where('montant_reste', '>', 0);
        }

        $tickets = $query
            ->orderByDesc('date_paie')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $usines = Usine::query()->orderBy('nom_usine')->get();

        return view('tickets.paid', compact('tickets', 'filters', 'agents', 'usines'));
    }

    public function validated(Request $request): View
    {
        $filters = [
            'agent_id' => $request->input('agent_id'),
            'usine_id' => $request->input('usine_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $query = $this->ticketQuery()
            ->validated();

        if ($filters['agent_id']) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if ($filters['usine_id']) {
            $query->where('id_usine', (int) $filters['usine_id']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        $tickets = $query
            ->orderByDesc('date_validation_boss')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $usines = Usine::query()->orderBy('nom_usine')->get();

        return view('tickets.validated', compact('tickets', 'filters', 'agents', 'usines'));
    }

    public function verified(Request $request): View
    {
        $filters = [
            'agent_id' => $request->input('agent_id'),
            'usine_id' => $request->input('usine_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'numero_ticket' => trim((string) $request->input('numero_ticket', '')),
        ];

        $query = $this->ticketQuery()->verified();

        if ($filters['agent_id']) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if ($filters['usine_id']) {
            $query->where('id_usine', (int) $filters['usine_id']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('date_verification', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_verification', '<=', $filters['date_fin']);
        }

        if ($filters['numero_ticket'] !== '') {
            $query->where('numero_ticket', 'like', '%'.$filters['numero_ticket'].'%');
        }

        $tickets = $query
            ->orderByDesc('date_verification')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $usines = Usine::query()->orderBy('nom_usine')->get();

        return view('tickets.verified', compact('tickets', 'filters', 'agents', 'usines'));
    }

    public function modifications(Request $request): View
    {
        $filters = [
            'agent_id' => $request->input('agent_id'),
            'usine_id' => $request->input('usine_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'numero_ticket' => $request->input('numero_ticket'),
        ];

        $query = $this->ticketQuery();

        if ($filters['agent_id']) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if ($filters['usine_id']) {
            $query->where('id_usine', (int) $filters['usine_id']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        if ($filters['numero_ticket']) {
            $query->where('numero_ticket', 'like', '%'.$filters['numero_ticket'].'%');
        }

        $tickets = $query
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->with(['ponts' => fn ($query) => $query->orderBy('nom_pont')])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $usines = Usine::query()->orderBy('nom_usine')->get();
        $vehicules = Vehicule::query()->orderBy('matricule_vehicule')->get();

        $agentsForAutocomplete = $agents->map(fn (Agent $agent) => [
            'id' => $agent->id_agent,
            'numero' => $agent->numero_agent ?? '',
            'name' => $agent->full_name,
        ])->values();

        $agentsPontsMap = $agents->mapWithKeys(fn (Agent $agent) => [
            $agent->id_agent => $agent->ponts->map(fn ($pont) => [
                'id' => $pont->id_pont,
                'code' => $pont->code_pont,
                'nom' => $pont->nom_pont,
                'label' => $pont->code_pont
                    ? $pont->code_pont.' — '.$pont->nom_pont
                    : $pont->nom_pont,
            ])->values()->all(),
        ])->all();

        $usinesForAutocomplete = $usines->map(fn (Usine $usine) => [
            'id' => $usine->id_usine,
            'label' => $usine->nom_usine,
        ])->values();

        $vehiculesForAutocomplete = $vehicules->map(fn (Vehicule $vehicule) => [
            'id' => $vehicule->vehicules_id,
            'label' => $vehicule->matricule_vehicule,
        ])->values();

        return view('tickets.modifications', compact(
            'tickets',
            'filters',
            'agents',
            'usines',
            'vehicules',
            'agentsForAutocomplete',
            'agentsPontsMap',
            'usinesForAutocomplete',
            'vehiculesForAutocomplete',
        ));
    }

    public function search(Request $request): View
    {
        $filters = $this->searchFiltersFromRequest($request);

        $isSearchRequested = $request->has('search');

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $usines = Usine::query()->orderBy('nom_usine')->get();
        $vehicules = Vehicule::query()->orderBy('matricule_vehicule')->get();

        $tickets = null;

        if ($isSearchRequested) {
            $query = $this->ticketQuery();

            $this->applySearchFilters($query, $filters);

            $tickets = $query
                ->orderByDesc('date_ticket')
                ->orderByDesc('id_ticket')
                ->paginate(15)
                ->withQueryString();
        }

        return view('tickets.search', compact(
            'filters',
            'agents',
            'usines',
            'vehicules',
            'tickets',
            'isSearchRequested',
        ));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeTicketAccess($ticket);

        $validated = $request->validate([
            'date_ticket' => ['required', 'date'],
            'numero_ticket' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tickets', 'numero_ticket')->ignore($ticket->id_ticket, 'id_ticket'),
            ],
            'id_usine' => ['required', 'integer', 'exists:usines,id_usine'],
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'id_pont' => ['nullable', 'integer', Rule::exists('pont_bascule', 'id_pont')],
            'vehicule_id' => ['required', 'integer', 'exists:vehicules,vehicules_id'],
            'poids' => $this->poidsRules($ticket),
            'prix_unitaire' => ['nullable', 'numeric', 'min:0'],
            'created_at' => ['nullable', 'date'],
        ]);

        $agentHasPonts = PontBascule::query()
            ->where('id_agent', (int) $validated['id_agent'])
            ->exists();

        if ($agentHasPonts) {
            if (empty($validated['id_pont'])) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Veuillez sélectionner le pont-bascule associé à cet agent.',
                        'errors' => ['id_pont' => ['Veuillez sélectionner le pont-bascule associé à cet agent.']],
                    ], 422);
                }

                return back()->withErrors(['id_pont' => 'Veuillez sélectionner le pont-bascule associé à cet agent.']);
            }

            $pontBelongsToAgent = PontBascule::query()
                ->where('id_pont', (int) $validated['id_pont'])
                ->where('id_agent', (int) $validated['id_agent'])
                ->exists();

            if (! $pontBelongsToAgent) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Le pont sélectionné n\'est pas associé à cet agent.',
                        'errors' => ['id_pont' => ['Le pont sélectionné n\'est pas associé à cet agent.']],
                    ], 422);
                }

                return back()->withErrors(['id_pont' => 'Le pont sélectionné n\'est pas associé à cet agent.']);
            }

            $validated['id_pont'] = (int) $validated['id_pont'];
        } else {
            $validated['id_pont'] = null;
        }

        try {
            $updated = $this->ticketService->update($ticket, $validated);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['ticket' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            $updated->load(['usine', 'agent', 'vehicule', 'pont']);

            return response()->json([
                'ok' => true,
                'message' => 'Modification effectuée.',
                'ticket' => [
                    'date_ticket' => $updated->date_ticket?->format('Y-m-d'),
                    'date_ticket_display' => $updated->date_ticket?->format('d/m/Y'),
                    'numero_ticket' => $updated->numero_ticket,
                    'numero_ticket_display' => $updated->numero_ticket,
                    'id_usine' => $updated->id_usine,
                    'usine_name' => $updated->usine?->nom_usine,
                    'id_agent' => $updated->id_agent,
                    'agent_name' => $updated->agent?->full_name,
                    'id_pont' => $updated->id_pont,
                    'pont_name' => $updated->pont?->nom_pont,
                    'vehicule_id' => $updated->vehicule_id,
                    'vehicule_label' => $updated->vehicule?->matricule_vehicule,
                    'poids' => $updated->poids,
                    'poids_display' => $updated->poids ? number_format($updated->poids, 0, '', ' ') : '—',
                    'prix_unitaire' => $updated->prix_unitaire,
                    'prix_unitaire_display' => $updated->hasPrixUnitaire()
                        ? number_format((float) $updated->prix_unitaire, 0, '', ' ')
                        : null,
                    'created_at' => $updated->created_at?->format('Y-m-d'),
                    'created_at_display' => $updated->created_at?->format('d/m/Y'),
                    'montant_paie' => $updated->montant_paie,
                    'montant_display' => $updated->montant_paie
                        ? number_format((float) $updated->montant_paie, 0, '', ' ')
                        : null,
                ],
            ]);
        }

        return redirect()
            ->route('tickets.modifications', $request->only(['agent_id', 'usine_id', 'date_debut', 'date_fin', 'numero_ticket', 'page']))
            ->with('success', 'Ticket modifié avec succès.');
    }

    public function validate(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->authorizeTicketValidation();
        $this->authorizeTicketAccess($ticket);

        if (! $ticket->isPending()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ce ticket n\'est plus en attente de validation.',
                ], 422);
            }

            return back()->withErrors([
                'prix_unitaire' => 'Ce ticket n\'est plus en attente de validation.',
            ]);
        }

        $validated = $request->validate([
            'prix_unitaire' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->ticketService->validate($ticket, (float) $validated['prix_unitaire']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ticket validé avec succès.',
                'validated_ids' => [$ticket->id_ticket],
            ]);
        }

        return redirect()
            ->to($this->pendingRedirectUrl($request))
            ->with('success', 'Ticket validé avec succès.');
    }

    public function validateBulk(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeTicketValidation();

        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id_ticket'],
            'prix_unitaire' => ['required', 'numeric', 'min:0.01'],
            'update_all_usine' => ['sometimes', 'boolean'],
        ]);

        if (auth()->user()?->limitsTicketsToOwn()) {
            $validated['ticket_ids'] = Ticket::query()
                ->visibleToCurrentUser()
                ->whereIn('id_ticket', $validated['ticket_ids'])
                ->pluck('id_ticket')
                ->all();
        }

        $result = $this->ticketService->validateMany(
            $validated['ticket_ids'],
            (float) $validated['prix_unitaire'],
            $request->boolean('update_all_usine'),
        );

        if ($result['validated'] === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Aucun ticket n\'a pu être validé.',
                ], 422);
            }

            return back()->withErrors([
                'prix_unitaire' => 'Aucun ticket n\'a pu être validé.',
            ]);
        }

        $message = $result['validated'].' ticket(s) validé(s) avec succès.';

        if ($request->boolean('update_all_usine') && $result['usines_updated'] !== []) {
            $message .= ' Prix unitaire appliqué aux autres tickets en attente des usines concernées.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'validated_ids' => $result['validated_ids'],
                'usines_updated' => $result['usines_updated'],
            ]);
        }

        return redirect()
            ->to($this->pendingRedirectUrl($request))
            ->with('success', $message);
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        if (! auth()->user()?->canAccessModule('tickets.destroy')) {
            abort(403, 'Vous n\'avez pas la permission de supprimer un ticket.');
        }

        $this->authorizeTicketAccess($ticket);

        try {
            $this->ticketService->delete($ticket);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['ticket' => $e->getMessage()]);
        }

        return back()->with('success', 'Ticket supprimé avec succès.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Ticket>
     */
    private function ticketQuery()
    {
        return Ticket::query()
            ->visibleToCurrentUser()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur', 'pont']);
    }

    private function authorizeTicketAccess(Ticket $ticket): void
    {
        $user = auth()->user();

        if ($user?->limitsTicketsToOwn() && (int) $ticket->id_utilisateur !== (int) $user->id) {
            abort(403, 'Vous ne pouvez accéder qu\'aux tickets que vous avez enregistrés.');
        }
    }

    private function authorizeTicketValidation(): void
    {
        if (! auth()->user()?->canValidateTickets()) {
            abort(403, 'Les opérateurs ne peuvent pas valider un ticket.');
        }
    }

    /**
     * Conserve les filtres de la page « Tickets en attente » après validation.
     */
    private function pendingRedirectUrl(Request $request): string
    {
        $filters = array_filter(
            $request->only(['agent_id', 'usine_id', 'vehicule_id', 'date_debut', 'date_fin', 'page']),
            fn ($value) => $value !== null && $value !== '',
        );

        if ($filters === [] && $request->headers->get('referer')) {
            return (string) $request->headers->get('referer');
        }

        return route('tickets.pending', $filters);
    }

    /**
     * @return array<string, mixed>
     */
    private function searchFiltersFromRequest(Request $request): array
    {
        return [
            'numero_ticket' => trim((string) $request->input('numero_ticket', '')),
            'agent_id' => $request->input('agent_id'),
            'usine_id' => $request->input('usine_id'),
            'vehicule_id' => $request->input('vehicule_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Ticket>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySearchFilters($query, array $filters): void
    {
        if ($filters['numero_ticket'] !== '') {
            $query->where('numero_ticket', 'like', '%'.$filters['numero_ticket'].'%');
        }

        if ($filters['agent_id']) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if ($filters['usine_id']) {
            $query->where('id_usine', (int) $filters['usine_id']);
        }

        if ($filters['vehicule_id']) {
            $query->where('vehicule_id', (int) $filters['vehicule_id']);
        }

        if ($filters['date_debut'] && $filters['date_fin']) {
            $query->whereDate('date_ticket', '>=', $filters['date_debut'])
                ->whereDate('date_ticket', '<=', $filters['date_fin']);
        } elseif ($filters['date_debut']) {
            $query->whereDate('date_ticket', $filters['date_debut']);
        } elseif ($filters['date_fin']) {
            $query->whereDate('date_ticket', '<=', $filters['date_fin']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData($tickets, array $filters = [], bool $isSearchRequested = false): array
    {
        $usines = Usine::query()->orderBy('nom_usine')->get();
        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->with(['ponts' => fn ($query) => $query->orderBy('nom_pont')])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
        $vehicules = Vehicule::query()->orderBy('matricule_vehicule')->get();

        $agentsForAutocomplete = $agents->map(fn (Agent $agent) => [
            'id' => $agent->id_agent,
            'numero' => $agent->numero_agent ?? '',
            'name' => $agent->full_name,
        ])->values();

        $agentsPontsMap = $agents->mapWithKeys(fn (Agent $agent) => [
            $agent->id_agent => $agent->ponts->map(fn ($pont) => [
                'id' => $pont->id_pont,
                'code' => $pont->code_pont,
                'nom' => $pont->nom_pont,
                'label' => $pont->code_pont
                    ? $pont->code_pont.' — '.$pont->nom_pont
                    : $pont->nom_pont,
            ])->values()->all(),
        ])->all();

        $usinesForAutocomplete = $usines->map(fn (Usine $usine) => [
            'id' => $usine->id_usine,
            'label' => $usine->nom_usine,
        ])->values();

        $vehiculesForAutocomplete = $vehicules->map(fn (Vehicule $vehicule) => [
            'id' => $vehicule->vehicules_id,
            'label' => $vehicule->matricule_vehicule,
        ])->values();

        $selectedAgent = old('id_agent')
            ? $agents->firstWhere('id_agent', (int) old('id_agent'))
            : null;

        $selectedUsine = old('id_usine')
            ? $usines->firstWhere('id_usine', (int) old('id_usine'))
            : null;

        $selectedVehicule = old('vehicule_id')
            ? $vehicules->firstWhere('vehicules_id', (int) old('vehicule_id'))
            : null;

        return compact(
            'tickets',
            'usines',
            'agents',
            'vehicules',
            'agentsForAutocomplete',
            'agentsPontsMap',
            'usinesForAutocomplete',
            'vehiculesForAutocomplete',
            'selectedAgent',
            'selectedUsine',
            'selectedVehicule',
            'filters',
            'isSearchRequested',
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date_ticket' => ['required', 'date'],
            'numero_ticket' => ['required', 'string', 'max:255', Rule::unique('tickets', 'numero_ticket')],
            'id_usine' => ['required', 'integer', 'exists:usines,id_usine'],
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'id_pont' => ['nullable', 'integer', Rule::exists('pont_bascule', 'id_pont')],
            'vehicule_id' => ['required', 'integer', 'exists:vehicules,vehicules_id'],
            'poids' => $this->poidsRules(),
        ]);

        $agentPontsCount = PontBascule::query()
            ->where('id_agent', $validated['id_agent'])
            ->count();

        if ($agentPontsCount > 0) {
            if (empty($validated['id_pont'])) {
                return back()
                    ->withInput()
                    ->withErrors(['id_pont' => 'Veuillez sélectionner le pont-bascule associé à cet agent.']);
            }

            $pontBelongsToAgent = PontBascule::query()
                ->where('id_pont', $validated['id_pont'])
                ->where('id_agent', $validated['id_agent'])
                ->exists();

            if (! $pontBelongsToAgent) {
                return back()
                    ->withInput()
                    ->withErrors(['id_pont' => 'Le pont sélectionné n\'est pas associé à cet agent.']);
            }

            $validated['id_pont'] = (int) $validated['id_pont'];
        } else {
            $validated['id_pont'] = null;
        }

        try {
            $ticket = $this->ticketService->create([
                ...$validated,
                'id_utilisateur' => auth()->id(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['numero_ticket' => $e->getMessage()]);
        }

        $message = 'Ticket enregistré avec succès.';
        if ($ticket->hasPrixUnitaire()) {
            $message .= ' Prix unitaire appliqué : '.number_format((float) $ticket->prix_unitaire, 2, '.', ' ').' FCFA.';
        }

        return redirect()
            ->route('tickets.index')
            ->with('success', $message);
    }

    /**
     * @return array<int, mixed>
     */
    private function poidsRules(?Ticket $existing = null): array
    {
        return [
            'required',
            function (string $attribute, mixed $value, \Closure $fail) use ($existing): void {
                $raw = str_replace(' ', '', (string) $value);

                if (
                    $existing !== null
                    && is_numeric($raw)
                    && abs((float) $raw - (float) $existing->poids) < 0.00001
                ) {
                    return;
                }

                if (
                    str_contains($raw, '.')
                    || str_contains($raw, ',')
                    || filter_var($raw, FILTER_VALIDATE_INT) === false
                ) {
                    $fail('Enregistrement nombre à virgule interdit');
                }
            },
        ];
    }
}
