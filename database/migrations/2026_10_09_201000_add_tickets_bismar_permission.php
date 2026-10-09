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
        $roles = DB::table('role_permissions')
            ->where('module', 'tickets.paid')
            ->pluck('role')
            ->push('admin')
            ->unique();

        foreach ($roles as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('module', 'tickets.bismar')
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'module' => 'tickets.bismar',
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

        $roles = DB::table('role_permissions')
            ->where('module', 'tickets.bismar')
            ->pluck('role')
            ->unique();

        DB::table('role_permissions')
            ->where('module', 'tickets.bismar')
            ->delete();

        foreach ($roles as $role) {
            Cache::forget('role_permissions:'.$role);
        }
    }
};
