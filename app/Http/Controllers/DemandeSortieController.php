<?php

namespace App\Http\Controllers;

use App\Models\DemandeSortie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemandeSortieController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statut = (string) $request->query('statut', 'all');
        $dateDebut = $request->query('date_debut');
        $dateFin = $request->query('date_fin');

        $query = DemandeSortie::query()
            ->with(['approbateur', 'payeur'])
            ->orderByDesc('date_demande')
            ->orderByDesc('id_demande');

        if ($statut !== 'all' && $statut !== '') {
            $query->where('statut', $statut);
        }

        if ($dateDebut) {
            $query->whereDate('date_demande', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->whereDate('date_demande', '<=', $dateFin);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('numero_demande', 'like', '%'.$search.'%')
                    ->orWhere('motif', 'like', '%'.$search.'%');
            });
        }

        $demandes = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => DemandeSortie::query()->count(),
            'en_attente' => DemandeSortie::query()->where('statut', DemandeSortie::STATUT_EN_ATTENTE)->count(),
            'approuve' => DemandeSortie::query()->where('statut', DemandeSortie::STATUT_APPROUVE)->count(),
            'paye' => DemandeSortie::query()->where('statut', DemandeSortie::STATUT_PAYE)->count(),
        ];

        return view('sorties.demandes.index', compact(
            'demandes',
            'search',
            'statut',
            'dateDebut',
            'dateFin',
            'stats',
        ));
    }

    public function pending(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $dateDebut = $request->query('date_debut');
        $dateFin = $request->query('date_fin');

        $query = DemandeSortie::query()
            ->where('statut', DemandeSortie::STATUT_EN_ATTENTE)
            ->orderByDesc('date_demande')
            ->orderByDesc('id_demande');

        if ($dateDebut) {
            $query->whereDate('date_demande', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->whereDate('date_demande', '<=', $dateFin);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('numero_demande', 'like', '%'.$search.'%')
                    ->orWhere('motif', 'like', '%'.$search.'%');
            });
        }

        $demandes = $query->paginate(15)->withQueryString();

        return view('sorties.demandes.pending', compact(
            'demandes',
            'search',
            'dateDebut',
            'dateFin',
        ));
    }

    public function approve(Request $request, DemandeSortie $demandeSortie): RedirectResponse
    {
        if (! $demandeSortie->isApprovable()) {
            return redirect()
                ->route('sorties.pending.index')
                ->withErrors(['demande' => 'Cette demande ne peut plus être approuvée.']);
        }

        $demandeSortie->update([
            'statut' => DemandeSortie::STATUT_APPROUVE,
            'date_approbation' => now(),
            'approuve_par' => $request->user()->id,
        ]);

        return redirect()
            ->route('sorties.pending.index')
            ->with('success', "La demande {$demandeSortie->numero_demande} a été approuvée avec succès.");
    }

    public function reject(Request $request, DemandeSortie $demandeSortie): RedirectResponse
    {
        if (! $demandeSortie->isApprovable()) {
            return redirect()
                ->route('sorties.pending.index')
                ->withErrors(['demande' => 'Cette demande ne peut plus être refusée.']);
        }

        $validated = $request->validate([
            'motif_refus' => ['required', 'string', 'max:2000'],
        ]);

        $demandeSortie->update([
            'statut' => DemandeSortie::STATUT_REJETE,
            'date_approbation' => now(),
            'approuve_par' => $request->user()->id,
            'motif_refus' => $validated['motif_refus'],
        ]);

        return redirect()
            ->route('sorties.pending.index')
            ->with('success', "La demande {$demandeSortie->numero_demande} a été refusée.");
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'motif' => ['required', 'string', 'max:2000'],
        ]);

        DemandeSortie::query()->create([
            'numero_demande' => DemandeSortie::genererNumeroDemande(),
            'date_demande' => now(),
            'montant' => $validated['montant'],
            'motif' => $validated['motif'],
            'statut' => DemandeSortie::STATUT_EN_ATTENTE,
            'montant_payer' => 0,
            'montant_reste' => $validated['montant'],
        ]);

        return redirect()
            ->route('sorties.demandes.index')
            ->with('success', 'La demande de sortie a été enregistrée avec succès.');
    }

    public function update(Request $request, DemandeSortie $demandeSortie): RedirectResponse
    {
        if (! $demandeSortie->isEditable()) {
            return redirect()
                ->route('sorties.demandes.index')
                ->withErrors(['demande' => 'Impossible de modifier une demande déjà approuvée ou payée.']);
        }

        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'motif' => ['required', 'string', 'max:2000'],
        ]);

        $demandeSortie->update([
            'montant' => $validated['montant'],
            'motif' => $validated['motif'],
            'montant_reste' => $validated['montant'],
        ]);

        return redirect()
            ->route('sorties.demandes.index')
            ->with('success', 'La demande a été mise à jour avec succès.');
    }

    public function destroy(DemandeSortie $demandeSortie): RedirectResponse
    {
        if (! $demandeSortie->isEditable()) {
            return redirect()
                ->route('sorties.demandes.index')
                ->withErrors(['demande' => 'Impossible de supprimer une demande déjà approuvée ou payée.']);
        }

        $numero = $demandeSortie->numero_demande;
        $demandeSortie->delete();

        return redirect()
            ->route('sorties.demandes.index')
            ->with('success', "La demande {$numero} a été supprimée avec succès.");
    }
}
