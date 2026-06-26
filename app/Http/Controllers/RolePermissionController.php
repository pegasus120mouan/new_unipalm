<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function __construct(
        private readonly RolePermissionService $permissions,
    ) {}

    public function index(): View
    {
        $roles = Utilisateur::roleOptions();
        $roleModules = [];

        foreach (array_keys($roles) as $role) {
            $roleModules[$role] = $this->permissions->modulesForRole($role);
        }

        $groups = config('modules.groups', []);

        return view('role-permissions.index', compact('roles', 'roleModules', 'groups'));
    }

    public function edit(string $role): View
    {
        $roles = Utilisateur::roleOptions();

        if (! array_key_exists($role, $roles)) {
            abort(404);
        }

        $assigned = $this->permissions->modulesForRole($role);
        $groups = config('modules.groups', []);

        return view('role-permissions.edit', compact('role', 'roles', 'assigned', 'groups'));
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $roles = Utilisateur::roleOptions();

        if (! array_key_exists($role, $roles)) {
            abort(404);
        }

        $validated = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ]);

        $this->permissions->sync($role, $validated['modules'] ?? []);

        return redirect()
            ->route('role-permissions.index')
            ->with('success', 'Accès du profil « '.$roles[$role].' » mis à jour.');
    }
}
