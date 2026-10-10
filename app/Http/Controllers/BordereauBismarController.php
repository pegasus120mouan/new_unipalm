<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\BordereauBismar;
use App\Models\TicketParametre;
use App\Models\Usine;
use App\Services\BordereauBismarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BordereauBismarController extends Controller
{
    public function __construct(
        private readonly BordereauBismarService $service,
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

        $query = BordereauBismar::query()
            ->with('agent')
            ->withCount('tickets')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

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
            $query->whereHas('tickets', fn ($q) => $q->where('id_usine', (int) $filters['usine_id']));
        }

        if ($filters['numero_ticket'] !== '') {
            $query->whereHas('tickets', fn ($q) => $q->where('numero_ticket', 'like', '%'.$filters['numero_ticket'].'%'));
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

        $previewCriteria = session('bordereau_bismar_preview');
        $previewTickets = collect();

        if (is_array($previewCriteria)) {
            $previewTickets = $this->service->candidateTickets(
                (int) $previewCriteria['id_agent'],
                $previewCriteria['date_debut'],
                $previewCriteria['date_fin'],
            );

            if ($previewTickets->isEmpty()) {
                session()->forget('bordereau_bismar_preview');
                $previewCriteria = null;
            }
        }

        return view('bordereaux-bismar.index', compact(
            'bordereaux',
            'agents',
            'usines',
            'filters',
            'agentsForAutocomplete',
            'previewCriteria',
            'previewTickets',
        ));
    }

    public function preview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_agent' => ['required', 'integer', 'exists:agents,id_agent'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        $tickets = $this->service->candidateTickets(
            (int) $validated['id_agent'],
            $validated['date_debut'],
            $validated['date_fin'],
        );

        if ($tickets->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'bordereau' => TicketParametre::requiresVerification()
                        ? 'Aucun ticket BISMAR vérifié (avec code transfert) disponible pour cet agent sur la période (date de création, sans bordereau BISMAR).'
                        : 'Aucun ticket BISMAR (avec code transfert) disponible pour cet agent sur la période (date de création, sans bordereau BISMAR).',
                ]);
        }

        $agent = Agent::query()->findOrFail((int) $validated['id_agent']);

        session([
            'bordereau_bismar_preview' => [
                'id_agent' => (int) $validated['id_agent'],
                'agent_name' => $agent->full_name,
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
            ],
        ]);

        return redirect()
            ->route('bordereaux-bismar.index')
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
            $bordereau = $this->service->createFromTickets(
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

        session()->forget('bordereau_bismar_preview');

        return redirect()
            ->route('bordereaux-bismar.index')
            ->with('bordereau_generated', [
                'numero' => $bordereau->numero_bordereau,
                'tickets' => $bordereau->tickets()->count(),
                'agent' => $bordereau->agent?->full_name ?? '-',
                'montant' => (float) $bordereau->montant_total,
            ]);
    }

    public function valider(BordereauBismar $bordereauBismar): RedirectResponse
    {
        try {
            $this->service->validate($bordereauBismar);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['bordereau' => $e->getMessage()]);
        }

        return redirect()
            ->route('bordereaux-bismar.index')
            ->with('success', "Bordereau BISMAR {$bordereauBismar->numero_bordereau} validé avec succès.");
    }

    public function destroy(BordereauBismar $bordereauBismar): RedirectResponse
    {
        if ($bordereauBismar->isValidated()) {
            return back()->withErrors(['bordereau' => 'Impossible de supprimer un bordereau BISMAR déjà validé.']);
        }

        if ((float) ($bordereauBismar->montant_payer ?? 0) > 0) {
            return back()->withErrors(['bordereau' => 'Impossible de supprimer un bordereau BISMAR avec des paiements enregistrés.']);
        }

        $numero = $bordereauBismar->numero_bordereau;
        $this->service->delete($bordereauBismar);

        return redirect()
            ->route('bordereaux-bismar.index')
            ->with('success', "Bordereau BISMAR {$numero} supprimé avec succès.");
    }

    public function pdf(string $numero): Response
    {
        return Pdf::loadView('bordereaux-bismar.pdf', $this->service->pdfData($numero))
            ->setPaper('a4', 'portrait')
            ->stream('Bordereau_BISMAR_'.$numero.'.pdf');
    }
}
