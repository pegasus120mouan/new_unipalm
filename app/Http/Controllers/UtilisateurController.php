<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
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

    public function show(Utilisateur $utilisateur): View
    {
        return view('utilisateurs.show', compact('utilisateur'));
    }

    public function update(Request $request, Utilisateur $utilisateur): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenoms' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
        ]);

        $utilisateur->update([
            'nom' => trim($validated['nom']),
            'prenoms' => trim($validated['prenoms']),
            'contact' => trim($validated['contact']),
        ]);

        return redirect()
            ->route('utilisateurs.show', $utilisateur)
            ->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(Request $request, Utilisateur $utilisateur): RedirectResponse
    {
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:4', 'confirmed'],
        ], [
            'new_password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        if (! $utilisateur->checkPassword($validated['old_password'])) {
            return back()
                ->withErrors(['old_password' => 'L\'ancien mot de passe est incorrect.'])
                ->with('active_tab', 'password');
        }

        $utilisateur->update([
            'password' => hash('sha256', $validated['new_password']),
        ]);

        return redirect()
            ->route('utilisateurs.show', $utilisateur)
            ->with('success', 'Mot de passe modifié avec succès.')
            ->with('active_tab', 'password');
    }

    public function resetPassword(Utilisateur $utilisateur): RedirectResponse
    {
        $authUser = auth()->user();

        $canReset = $authUser->id === $utilisateur->id
            || $authUser->canAccessModule('utilisateurs.index');

        if (! $canReset) {
            abort(403, 'Vous n\'avez pas la permission de réinitialiser ce mot de passe.');
        }

        $utilisateur->setPasswordFromPlain(Utilisateur::DEFAULT_PASSWORD);

        $message = $authUser->id === $utilisateur->id
            ? 'Votre mot de passe a été réinitialisé. Connectez-vous avec le mot de passe par défaut : '.Utilisateur::DEFAULT_PASSWORD
            : 'Mot de passe de '.$utilisateur->full_name.' réinitialisé. Nouveau mot de passe : '.Utilisateur::DEFAULT_PASSWORD;

        return redirect()
            ->route('utilisateurs.show', $utilisateur)
            ->with('success', $message)
            ->with('active_tab', 'password');
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
            'avatar' => 'default.jpg',
        ]);

        return redirect()
            ->route('utilisateurs.index')
            ->with('success', 'Utilisateur enregistré avec succès.');
    }

    public function inlineUpdate(Request $request, Utilisateur $utilisateur): JsonResponse
    {
        $rules = [
            'field' => ['required', 'in:nom,prenoms,contact,login'],
            'value' => ['required', 'string', 'max:255'],
        ];

        if ($request->input('field') === 'login') {
            $rules['value'][] = Rule::unique('utilisateurs', 'login')->ignore($utilisateur->id);
        }

        $validated = $request->validate($rules);

        $field = $validated['field'];
        $value = trim($validated['value']);

        $utilisateur->update([$field => $value]);

        $display = in_array($field, ['nom', 'prenoms'], true)
            ? Utilisateur::formatPersonName($utilisateur->{$field})
            : $utilisateur->{$field};

        return response()->json([
            'success' => true,
            'field' => $field,
            'value' => $utilisateur->{$field},
            'display' => $display,
        ]);
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

    public function destroy(Utilisateur $utilisateur): RedirectResponse
    {
        if ($utilisateur->id === auth()->id()) {
            return back()->withErrors([
                'statut' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ]);
        }

        $name = $utilisateur->full_name;

        try {
            $utilisateur->delete();
        } catch (QueryException) {
            return back()->withErrors([
                'statut' => "Impossible de supprimer {$name} : cet utilisateur est lié à d'autres enregistrements.",
            ]);
        }

        return redirect()
            ->route('utilisateurs.index')
            ->with('success', "Utilisateur {$name} supprimé avec succès.");
    }
}
