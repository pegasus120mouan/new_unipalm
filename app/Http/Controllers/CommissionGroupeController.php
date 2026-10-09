<?php

namespace App\Http\Controllers;

use App\Models\CommissionGroupe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionGroupeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $query = CommissionGroupe::query()
            ->with('createur')
            ->orderByDesc('date_debut')
            ->orderByDesc('id');

        if ($filters['date_debut']) {
            $query->whereDate('date_fin', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_debut', '<=', $filters['date_fin']);
        }

        $commissions = $query->paginate(15)->withQueryString();

        return view('commissions-groupe.index', compact('commissions', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCommission($request);

        if ($conflict = $this->findOverlap($validated['date_debut'], $validated['date_fin'])) {
            return back()
                ->withInput()
                ->withErrors(['date_debut' => $this->overlapMessage($conflict)]);
        }

        CommissionGroupe::query()->create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('commissions-groupe.index')
            ->with('success', 'Commission groupe enregistrée avec succès.');
    }

    public function update(Request $request, CommissionGroupe $commissionGroupe): RedirectResponse
    {
        $validated = $this->validateCommission($request);

        if ($conflict = $this->findOverlap($validated['date_debut'], $validated['date_fin'], (int) $commissionGroupe->id)) {
            return back()
                ->withInput()
                ->with('edit_commission_id', $commissionGroupe->id)
                ->withErrors(['date_debut' => $this->overlapMessage($conflict)]);
        }

        $commissionGroupe->update($validated);

        return redirect()
            ->route('commissions-groupe.index')
            ->with('success', 'Commission groupe modifiée avec succès.');
    }

    public function destroy(CommissionGroupe $commissionGroupe): RedirectResponse
    {
        $commissionGroupe->delete();

        return redirect()
            ->route('commissions-groupe.index')
            ->with('success', 'Commission groupe supprimée.');
    }

    /**
     * @return array{date_debut: string, date_fin: string, montant: string}
     */
    private function validateCommission(Request $request): array
    {
        return $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'montant' => ['required', 'numeric', 'min:0.01'],
        ], [
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);
    }

    private function findOverlap(string $dateDebut, string $dateFin, ?int $excludeId = null): ?CommissionGroupe
    {
        return CommissionGroupe::query()
            ->overlapping($dateDebut, $dateFin)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('date_debut')
            ->first();
    }

    private function overlapMessage(CommissionGroupe $conflict): string
    {
        return sprintf(
            'Une commission de %s FCFA existe déjà du %s au %s. Les périodes ne peuvent pas se chevaucher.',
            number_format((float) $conflict->montant, 0, '', ' '),
            $conflict->date_debut->format('d/m/Y'),
            $conflict->date_fin->format('d/m/Y'),
        );
    }
}
