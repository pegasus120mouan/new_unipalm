<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Bordereau;
use App\Models\Ticket;
use App\Models\Usine;
use App\Services\BordereauPdfService;
use App\Services\BordereauService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BordereauController extends Controller
{
    public function __construct(
        private readonly BordereauService $bordereauService,
        private readonly BordereauPdfService $bordereauPdfService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'numero' => trim((string) $request->query('numero', '')),
            'agent_id' => $request->query('agent_id'),
            'usine_id' => $request->query('usine_id'),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
            'numero_ticket' => trim((string) $request->query('numero_ticket', '')),
        ];

        $query = Bordereau::query()
            ->with('agent')
            ->withCount('tickets')
            ->orderByDesc('created_at')
            ->orderByDesc('id_bordereau');

        if ($filters['numero'] !== '') {
            $numero = preg_replace('/[\s\-]+/', '', $filters['numero']);
            $query->whereRaw(
                "REPLACE(REPLACE(numero_bordereau, ' ', ''), '-', '') LIKE ?",
                ['%'.$numero.'%']
            );
        }

        if ($filters['agent_id']) {
            $query->where('id_agent', (int) $filters['agent_id']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('date_debut', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_fin', '<=', $filters['date_fin']);
        }

        if ($filters['usine_id']) {
            $query->whereHas('tickets', function ($ticketQuery) use ($filters) {
                $ticketQuery->where('id_usine', (int) $filters['usine_id']);
            });
        }

        if ($filters['numero_ticket'] !== '') {
            $query->whereHas('tickets', function ($ticketQuery) use ($filters) {
                $ticketQuery->where('numero_ticket', 'like', '%'.$filters['numero_ticket'].'%');
            });
        }

        $bordereaux = $query->paginate(15)->withQueryString();

        $agents = Agent::query()
            ->whereNull('date_suppression')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $usines = Usine::query()->orderBy('nom_usine')->get();

        $agentsForAutocomplete = $agents->map(fn (Agent $agent) => [
            'id' => $agent->id_agent,
            'numero' => $agent->numero_agent ?? '',
            'name' => $agent->full_name,
        ])->values();

        $previewCriteria = session('bordereau_preview');
        $previewTickets = collect();

        if (is_array($previewCriteria) && ! empty($previewCriteria['ticket_ids'])) {
            $previewTickets = Ticket::query()
                ->with(['usine', 'vehicule'])
                ->whereIn('id_ticket', $previewCriteria['ticket_ids'])
                ->orderByDesc('date_ticket')
                ->orderByDesc('id_ticket')
                ->get();

            if ($previewTickets->isEmpty()) {
                session()->forget('bordereau_preview');
                $previewCriteria = null;
            }
        }

        return view('bordereaux.index', compact(
            'bordereaux',
            'agents',
            'usines',
            'filters',
            'agentsForAutocomplete',
            'previewCriteria',
            'previewTickets',
        ));
    }

    public function pdf(string $numero): Response
    {
        return $this->bordereauPdfService->stream($numero);
    }

    public function preview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        $tickets = $this->bordereauService->eligibleTickets(
            (int) $validated['id_agent'],
            $validated['date_debut'],
            $validated['date_fin'],
        );

        if ($tickets->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'bordereau' => 'Aucun ticket validé disponible pour cet agent sur la période (sans bordereau).',
                ]);
        }

        $agent = Agent::query()->findOrFail((int) $validated['id_agent']);

        session([
            'bordereau_preview' => [
                'id_agent' => (int) $validated['id_agent'],
                'agent_name' => $agent->full_name,
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'ticket_ids' => $tickets->pluck('id_ticket')->all(),
            ],
        ]);

        return redirect()
            ->route('bordereaux.index')
            ->with('open_tickets_preview_modal', true);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id_ticket'],
        ]);

        try {
            $bordereau = $this->bordereauService->createFromTickets(
                (int) $validated['id_agent'],
                $validated['date_debut'],
                $validated['date_fin'],
                $validated['ticket_ids'],
            );
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('open_tickets_preview_modal', true)
                ->withErrors(['bordereau' => $e->getMessage()]);
        }

        session()->forget('bordereau_preview');

        $ticketCount = $bordereau->tickets()->count();

        return redirect()
            ->route('bordereaux.index')
            ->with('success', "Bordereau {$bordereau->numero_bordereau} créé avec {$ticketCount} ticket(s) sélectionné(s).");
    }

    public function validate(Bordereau $bordereau): RedirectResponse
    {
        try {
            $this->bordereauService->validate($bordereau);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['bordereau' => $e->getMessage()]);
        }

        return redirect()
            ->route('bordereaux.index')
            ->with('success', "Bordereau {$bordereau->numero_bordereau} validé avec succès.");
    }

    public function destroy(Bordereau $bordereau): RedirectResponse
    {
        if ($bordereau->isValidated()) {
            return back()->withErrors([
                'bordereau' => 'Impossible de supprimer un bordereau déjà validé.',
            ]);
        }

        if ((float) ($bordereau->montant_payer ?? 0) > 0) {
            return back()->withErrors([
                'bordereau' => 'Impossible de supprimer un bordereau avec des paiements enregistrés.',
            ]);
        }

        $numero = $bordereau->numero_bordereau;

        $this->bordereauService->delete($bordereau);

        return redirect()
            ->route('bordereaux.index')
            ->with('success', "Bordereau {$numero} supprimé avec succès.");
    }
}
