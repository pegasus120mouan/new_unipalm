<?php

namespace App\Http\Controllers;

use App\Models\PrixUnitaire;
use App\Models\Usine;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrixUnitaireController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'usine_id' => $request->input('usine_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'prix_min' => $request->input('prix_min'),
            'prix_max' => $request->input('prix_max'),
        ];

        $query = PrixUnitaire::query()
            ->with('usine')
            ->orderByDesc('date_debut')
            ->orderByDesc('id');

        if ($filters['usine_id']) {
            $query->where('id_usine', (int) $filters['usine_id']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('date_debut', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('date_fin')
                    ->orWhereDate('date_fin', '<=', $filters['date_fin']);
            });
        }

        if ($filters['prix_min'] !== null && $filters['prix_min'] !== '') {
            $query->where('prix', '>=', (float) $filters['prix_min']);
        }

        if ($filters['prix_max'] !== null && $filters['prix_max'] !== '') {
            $query->where('prix', '<=', (float) $filters['prix_max']);
        }

        $prixUnitaires = $query->paginate(15)->withQueryString();

        $usines = Usine::query()->orderBy('nom_usine')->get();

        return view('prix-unitaires.index', compact('prixUnitaires', 'usines', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_usine' => ['required', 'integer', 'exists:usines,id_usine'],
            'prix' => ['required', 'numeric', 'min:0.01'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        if ($this->hasPeriodOverlap(
            (int) $validated['id_usine'],
            $validated['date_debut'],
            $validated['date_fin'] ?? null,
        )) {
            return back()
                ->withInput()
                ->withErrors([
                    'date_debut' => 'Un prix unitaire existe déjà pour cette usine sur la période indiquée.',
                ]);
        }

        PrixUnitaire::query()->create([
            'id_usine' => $validated['id_usine'],
            'prix' => $validated['prix'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'] ?? null,
        ]);

        $updatedTickets = $this->ticketService->applyPrixUnitaireToPendingTickets(
            (int) $validated['id_usine'],
            (float) $validated['prix'],
            $validated['date_debut'],
            $validated['date_fin'] ?? null,
        );

        $message = 'Prix unitaire enregistré avec succès.';
        if ($updatedTickets > 0) {
            $message .= ' '.$updatedTickets.' ticket(s) mis à jour rétroactivement.';
        } else {
            $message .= ' Aucun ticket à mettre à jour pour cette période.';
        }

        return redirect()
            ->route('prix-unitaires.index')
            ->with('success', $message);
    }

    public function update(Request $request, PrixUnitaire $prixUnitaire): RedirectResponse
    {
        $validated = $request->validate([
            'id_usine' => ['required', 'integer', 'exists:usines,id_usine'],
            'prix' => ['required', 'numeric', 'min:0.01'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        if ($this->hasPeriodOverlap(
            (int) $validated['id_usine'],
            $validated['date_debut'],
            $validated['date_fin'] ?? null,
            (int) $prixUnitaire->id,
        )) {
            return back()
                ->withInput()
                ->with('edit_prix_id', $prixUnitaire->id)
                ->withErrors([
                    'date_debut' => 'Un prix unitaire existe déjà pour cette usine sur la période indiquée.',
                ]);
        }

        $prixUnitaire->update([
            'id_usine' => $validated['id_usine'],
            'prix' => $validated['prix'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'] ?? $validated['date_debut'],
        ]);

        $updatedTickets = $this->ticketService->applyPrixUnitaireToPendingTickets(
            (int) $validated['id_usine'],
            (float) $validated['prix'],
            $validated['date_debut'],
            $validated['date_fin'] ?? null,
        );

        $message = 'Prix unitaire modifié avec succès.';
        if ($updatedTickets > 0) {
            $message .= ' '.$updatedTickets.' ticket(s) mis à jour rétroactivement.';
        }

        return redirect()
            ->route('prix-unitaires.index')
            ->with('success', $message);
    }

    private function hasPeriodOverlap(int $idUsine, string $dateDebut, ?string $dateFin, ?int $excludeId = null): bool
    {
        $query = PrixUnitaire::query()
            ->where('id_usine', $idUsine)
            ->where('date_debut', '<=', $dateFin ?? '9999-12-31')
            ->where(function ($q) use ($dateDebut) {
                $q->whereNull('date_fin')
                    ->orWhere('date_fin', '>=', $dateDebut);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
