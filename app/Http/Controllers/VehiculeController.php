<?php

namespace App\Http\Controllers;

use App\Models\Vehicule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));

        $vehiculesQuery = Vehicule::query()
            ->withCount('tickets')
            ->orderByDesc('created_at')
            ->orderByDesc('vehicules_id');

        if ($search !== '') {
            $vehiculesQuery->where('matricule_vehicule', 'like', '%'.$search.'%');
        }

        if (in_array($type, ['voiture', 'moto', 'tricycle'], true)) {
            $vehiculesQuery->where('type_vehicule', $type);
        }

        $vehicules = $vehiculesQuery
            ->paginate(15)
            ->withQueryString();

        return view('vehicules.index', compact('vehicules', 'search', 'type'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matricule_vehicule' => ['required', 'string', 'max:255', 'unique:vehicules,matricule_vehicule'],
            'type_vehicule' => ['required', Rule::in(['voiture', 'moto', 'tricycle'])],
        ]);

        Vehicule::query()->create([
            'matricule_vehicule' => strtoupper(trim($validated['matricule_vehicule'])),
            'type_vehicule' => $validated['type_vehicule'],
        ]);

        return redirect()
            ->route('vehicules.index')
            ->with('success', 'Véhicule enregistré avec succès.');
    }
}
