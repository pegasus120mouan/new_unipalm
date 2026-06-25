<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Ticket;
use App\Models\Usine;
use App\Models\Vehicule;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->searchFiltersFromRequest($request);
        $isSearchRequested = $request->has('search');

        $query = Ticket::query()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur']);

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

    public function today(): View
    {
        $tickets = Ticket::query()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur'])
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        return view('tickets.today', compact('tickets'));
    }

    public function pending(): View
    {
        $tickets = Ticket::query()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur'])
            ->pending()
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        return view('tickets.pending', compact('tickets'));
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

        $query = Ticket::query()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur'])
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

        $query = Ticket::query()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur'])
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

    public function modifications(Request $request): View
    {
        $filters = [
            'agent_id' => $request->input('agent_id'),
            'usine_id' => $request->input('usine_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'numero_ticket' => $request->input('numero_ticket'),
        ];

        $query = Ticket::query()
            ->with(['agent', 'usine', 'vehicule', 'utilisateur']);

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

        $usinesForAutocomplete = $usines->map(fn (Usine $usine) => [
            'id' => $usine->id_usine,
            'label' => $usine->nom_usine,
        ])->values();

        return view('tickets.modifications', compact(
            'tickets',
            'filters',
            'agents',
            'usines',
            'vehicules',
            'agentsForAutocomplete',
            'usinesForAutocomplete',
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
            $query = Ticket::query()
                ->with(['agent', 'usine', 'vehicule', 'utilisateur']);

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

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
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
            'vehicule_id' => ['required', 'integer', 'exists:vehicules,vehicules_id'],
            'poids' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->ticketService->update($ticket, $validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['ticket' => $e->getMessage()]);
        }

        return redirect()
            ->route('tickets.modifications', $request->only(['agent_id', 'usine_id', 'date_debut', 'date_fin', 'numero_ticket', 'page']))
            ->with('success', 'Ticket modifié avec succès.');
    }

    public function validate(Request $request, Ticket $ticket): RedirectResponse
    {
        if (! $ticket->isPending()) {
            return back()->withErrors([
                'prix_unitaire' => 'Ce ticket n\'est plus en attente de validation.',
            ]);
        }

        $validated = $request->validate([
            'prix_unitaire' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->ticketService->validate($ticket, (float) $validated['prix_unitaire']);

        return redirect()
            ->route('tickets.pending')
            ->with('success', 'Ticket validé avec succès.');
    }

    public function validateBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id_ticket'],
            'prix_unitaire' => ['required', 'numeric', 'min:0.01'],
            'update_all_usine' => ['sometimes', 'boolean'],
        ]);

        $result = $this->ticketService->validateMany(
            $validated['ticket_ids'],
            (float) $validated['prix_unitaire'],
            $request->boolean('update_all_usine'),
        );

        if ($result['validated'] === 0) {
            return back()->withErrors([
                'prix_unitaire' => 'Aucun ticket n\'a pu être validé.',
            ]);
        }

        $message = $result['validated'].' ticket(s) validé(s) avec succès.';

        if ($request->boolean('update_all_usine') && $result['usines_updated'] !== []) {
            $message .= ' Prix unitaire appliqué aux autres tickets en attente des usines concernées.';
        }

        return redirect()
            ->route('tickets.pending')
            ->with('success', $message);
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
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
        $vehicules = Vehicule::query()->orderBy('matricule_vehicule')->get();

        $agentsForAutocomplete = $agents->map(fn (Agent $agent) => [
            'id' => $agent->id_agent,
            'numero' => $agent->numero_agent ?? '',
            'name' => $agent->full_name,
        ])->values();

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
            'vehicule_id' => ['required', 'integer', 'exists:vehicules,vehicules_id'],
            'poids' => ['required', 'numeric', 'min:0'],
        ]);

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
}
