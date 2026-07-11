<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('role_permissions')) {
            return;
        }

        $now = now();
        $allModules = collect(config('modules.groups', []))
            ->flatMap(fn (array $group) => array_keys($group['modules']))
            ->values()
            ->all();

        foreach ($allModules as $module) {
            $this->ensurePermission('admin', $module, $now);
        }

        $rolesWithFinancements = DB::table('role_permissions')
            ->where('module', 'financements')
            ->pluck('role')
            ->unique();

        foreach ($rolesWithFinancements as $role) {
            $this->ensurePermission((string) $role, 'financements.valider', $now);
        }

        foreach (['directeur', 'caissiere'] as $role) {
            $defaults = config('modules.defaults.'.$role, []);
            if (! is_array($defaults)) {
                continue;
            }
            foreach ($defaults as $module) {
                $this->ensurePermission($role, $module, $now);
            }
        }

        foreach (['admin', 'directeur', 'validateur', 'operateur', 'caissiere'] as $role) {
            Cache::forget('role_permissions:'.$role);
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('role_permissions')) {
            return;
        }

        DB::table('role_permissions')
            ->where('module', 'financements.valider')
            ->delete();

        foreach (['admin', 'directeur', 'validateur', 'operateur', 'caissiere'] as $role) {
            Cache::forget('role_permissions:'.$role);
        }
    }

    private function ensurePermission(string $role, string $module, $now): void
    {
        $exists = DB::table('role_permissions')
            ->where('role', $role)
            ->where('module', $module)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('role_permissions')->insert([
            'role' => $role,
            'module' => $module,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
