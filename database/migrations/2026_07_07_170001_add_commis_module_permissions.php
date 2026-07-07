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
        $rolesWithAgents = DB::table('role_permissions')
            ->where('module', 'agents')
            ->pluck('role')
            ->unique();

        foreach ($rolesWithAgents as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('module', 'commis')
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'module' => 'commis',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Cache::forget('role_permissions:'.$role);
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('role_permissions')) {
            return;
        }

        DB::table('role_permissions')->where('module', 'commis')->delete();

        foreach (['admin', 'directeur', 'validateur', 'operateur', 'caissiere'] as $role) {
            Cache::forget('role_permissions:'.$role);
        }
    }
};
