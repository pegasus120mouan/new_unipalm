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
        $rolesWithPonts = DB::table('role_permissions')
            ->where('module', 'ponts.index')
            ->pluck('role')
            ->unique();

        foreach ($rolesWithPonts as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('module', 'ponts.types')
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'module' => 'ponts.types',
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

        DB::table('role_permissions')->where('module', 'ponts.types')->delete();

        foreach (['admin', 'directeur', 'validateur', 'operateur', 'caissiere'] as $role) {
            Cache::forget('role_permissions:'.$role);
        }
    }
};
