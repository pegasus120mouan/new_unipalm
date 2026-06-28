<?php

namespace App\Http\Controllers;

use App\Models\TypePont;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TypePontController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = TypePont::query()
            ->withCount('ponts')
            ->orderBy('libelle');

        if ($search !== '') {
            $query->where('libelle', 'like', '%'.$search.'%');
        }

        $types = $query->paginate(15)->withQueryString();

        return view('ponts.types.index', compact('types', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'libelle' => TypePont::normalizeLibelle((string) $request->input('libelle', '')),
        ]);

        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:100', 'unique:types_pont,libelle'],
        ]);

        TypePont::query()->create([
            'libelle' => $validated['libelle'],
        ]);

        return redirect()
            ->route('ponts.types.index')
            ->with('success', 'Type de pont enregistré avec succès.');
    }

    public function update(Request $request, TypePont $typePont): RedirectResponse
    {
        $request->merge([
            'libelle' => TypePont::normalizeLibelle((string) $request->input('libelle', '')),
        ]);

        $validated = $request->validate([
            'libelle' => [
                'required',
                'string',
                'max:100',
                Rule::unique('types_pont', 'libelle')->ignore($typePont->id_type_pont, 'id_type_pont'),
            ],
        ]);

        $typePont->update([
            'libelle' => $validated['libelle'],
        ]);

        return redirect()
            ->route('ponts.types.index')
            ->with('success', 'Type de pont modifié avec succès.');
    }

    public function destroy(TypePont $typePont): RedirectResponse
    {
        if ($typePont->ponts()->exists()) {
            return back()->withErrors([
                'type_pont' => 'Impossible de supprimer ce type : '.$typePont->ponts()->count().' pont(s) y sont associés.',
            ]);
        }

        $label = $typePont->libelle;
        $typePont->delete();

        return redirect()
            ->route('ponts.types.index')
            ->with('success', "Type de pont « {$label} » supprimé avec succès.");
    }
}
