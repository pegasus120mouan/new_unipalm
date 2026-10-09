<?php

namespace App\Http\Controllers;

use App\Models\PrimeBismar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrimeBismarController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $query = PrimeBismar::query()
            ->with('createur')
            ->orderByDesc('date_debut')
            ->orderByDesc('id');

        if ($filters['date_debut']) {
            $query->whereDate('date_fin', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_debut', '<=', $filters['date_fin']);
        }

        $primes = $query->paginate(15)->withQueryString();

        return view('primes-bismar.index', compact('primes', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePrime($request);

        if ($conflict = $this->findOverlap($validated['date_debut'], $validated['date_fin'])) {
            return back()
                ->withInput()
                ->withErrors(['date_debut' => $this->overlapMessage($conflict)]);
        }

        PrimeBismar::query()->create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('primes-bismar.index')
            ->with('success', 'Prime BISMAR enregistrée avec succès.');
    }

    public function update(Request $request, PrimeBismar $primeBismar): RedirectResponse
    {
        $validated = $this->validatePrime($request);

        if ($conflict = $this->findOverlap($validated['date_debut'], $validated['date_fin'], (int) $primeBismar->id)) {
            return back()
                ->withInput()
                ->with('edit_prime_id', $primeBismar->id)
                ->withErrors(['date_debut' => $this->overlapMessage($conflict)]);
        }

        $primeBismar->update($validated);

        return redirect()
            ->route('primes-bismar.index')
            ->with('success', 'Prime BISMAR modifiée avec succès.');
    }

    public function destroy(PrimeBismar $primeBismar): RedirectResponse
    {
        $primeBismar->delete();

        return redirect()
            ->route('primes-bismar.index')
            ->with('success', 'Prime BISMAR supprimée.');
    }

    /**
     * @return array{date_debut: string, date_fin: string, prix: string}
     */
    private function validatePrime(Request $request): array
    {
        return $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'prix' => ['required', 'numeric', 'min:0.01'],
        ], [
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);
    }

    private function findOverlap(string $dateDebut, string $dateFin, ?int $excludeId = null): ?PrimeBismar
    {
        return PrimeBismar::query()
            ->overlapping($dateDebut, $dateFin)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('date_debut')
            ->first();
    }

    private function overlapMessage(PrimeBismar $conflict): string
    {
        return sprintf(
            'Une prime BISMAR de %s FCFA existe déjà du %s au %s. Les périodes ne peuvent pas se chevaucher.',
            number_format((float) $conflict->prix, 0, '', ' '),
            $conflict->date_debut->format('d/m/Y'),
            $conflict->date_fin->format('d/m/Y'),
        );
    }
}
