<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commissions_groupe')) {
            Schema::create('commissions_groupe', function (Blueprint $table) {
                $table->id();
                $table->date('date_debut');
                $table->date('date_fin');
                $table->decimal('montant', 10, 2);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['date_debut', 'date_fin']);
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();
        $roles = DB::table('role_permissions')
            ->where('module', 'prix-unitaires')
            ->pluck('role')
            ->push('admin')
            ->unique();

        foreach ($roles as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('module', 'commissions-groupe')
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'module' => 'commissions-groupe',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Cache::forget('role_permissions:'.$role);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            $roles = DB::table('role_permissions')
                ->where('module', 'commissions-groupe')
                ->pluck('role')
                ->unique();

            DB::table('role_permissions')->where('module', 'commissions-groupe')->delete();

            foreach ($roles as $role) {
                Cache::forget('role_permissions:'.$role);
            }
        }

        Schema::dropIfExists('commissions_groupe');
    }
};
