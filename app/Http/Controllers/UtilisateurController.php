<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UtilisateurController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search_login' => trim((string) $request->query('search_login', '')),
            'search_nom' => trim((string) $request->query('search_nom', '')),
            'search_role' => trim((string) $request->query('search_role', '')),
            'statut' => trim((string) $request->query('statut', '')),
        ];

        $query = Utilisateur::query();

        if ($filters['search_login'] !== '') {
            $query->where('login', 'like', '%'.$filters['search_login'].'%');
        }

        if ($filters['search_nom'] !== '') {
            $term = '%'.$filters['search_nom'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('prenoms', 'like', $term)
                    ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$term]);
            });
        }

        if ($filters['search_role'] !== '') {
            $query->where('role', $filters['search_role']);
        }

        if ($filters['statut'] === 'actif') {
            $query->where('statut_compte', 1);
        } elseif ($filters['statut'] === 'inactif') {
            $query->where('statut_compte', 0);
        }

        $utilisateurs = $query
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Utilisateur::query()->count(),
            'actifs' => Utilisateur::query()->where('statut_compte', 1)->count(),
            'inactifs' => Utilisateur::query()->where('statut_compte', 0)->count(),
        ];

        $roles = Utilisateur::roleOptions();
        $hasFilters = collect($filters)->contains(fn ($value) => $value !== '');

        return view('utilisateurs.index', compact(
            'utilisateurs',
            'filters',
            'stats',
            'roles',
            'hasFilters',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenoms' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:utilisateurs,login'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
            'role' => ['required', 'string', Rule::in(array_keys(Utilisateur::roleOptions()))],
        ]);

        Utilisateur::query()->create([
            'nom' => trim($validated['nom']),
            'prenoms' => trim($validated['prenoms']),
            'contact' => trim($validated['contact']),
            'login' => trim($validated['login']),
            'password' => hash('sha256', $validated['password']),
            'role' => $validated['role'],
            'statut_compte' => true,
            'avatar' => 'utilisateurs.png',
        ]);

        return redirect()
            ->route('utilisateurs.index')
            ->with('success', 'Utilisateur enregistré avec succès.');
    }

    public function toggleStatut(Utilisateur $utilisateur): RedirectResponse
    {
        if ($utilisateur->id === auth()->id()) {
            return back()->withErrors([
                'statut' => 'Vous ne pouvez pas modifier le statut de votre propre compte.',
            ]);
        }

        $utilisateur->update([
            'statut_compte' => ! $utilisateur->isActive(),
        ]);

        $label = $utilisateur->isActive() ? 'activé' : 'désactivé';

        return back()->with('success', "Compte de {$utilisateur->full_name} {$label}.");
    }
}
