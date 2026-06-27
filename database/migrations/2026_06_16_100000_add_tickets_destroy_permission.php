<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $now = now();
        $rolesWithDelete = ['directeur'];

        foreach ($rolesWithDelete as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('module', 'tickets.destroy')
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'module' => 'tickets.destroy',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Cache::forget('role_permissions:'.$role);
        }
    }

    public function down(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        DB::table('role_permissions')
            ->where('module', 'tickets.destroy')
            ->delete();

        foreach (['admin', 'directeur', 'validateur', 'operateur', 'caissiere'] as $role) {
            Cache::forget('role_permissions:'.$role);
        }
    }

    private function tableExists(): bool
    {
        return DB::getSchemaBuilder()->hasTable('role_permissions');
    }
};
