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
        if (! Schema::hasTable('bordereaux_bismar')) {
            Schema::create('bordereaux_bismar', function (Blueprint $table) {
                $table->id();
                $table->string('numero_bordereau', 50)->unique();
                $table->integer('id_agent');
                $table->date('date_debut');
                $table->date('date_fin');
                $table->decimal('poids_total', 20, 2)->default(0);
                $table->decimal('montant_total', 20, 2)->default(0);
                $table->decimal('montant_payer', 20, 2)->default(0);
                $table->decimal('montant_reste', 20, 2)->default(0);
                $table->string('statut_bordereau', 20)->default('non soldé');
                $table->dateTime('date_validation_boss')->nullable();
                $table->dateTime('date_paie')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('id_agent');
            });
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'numero_bordereau_bismar')) {
                $table->string('numero_bordereau_bismar', 50)->nullable()->after('numero_bordereau');
                $table->index('numero_bordereau_bismar');
            }

            if (! Schema::hasColumn('tickets', 'prime_bismar')) {
                $table->decimal('prime_bismar', 10, 2)->nullable()->after('numero_bordereau_bismar');
            }
        });

        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();
        $roles = DB::table('role_permissions')
            ->where('module', 'bordereaux')
            ->pluck('role')
            ->push('admin')
            ->unique();

        foreach ($roles as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('module', 'bordereaux-bismar')
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'module' => 'bordereaux-bismar',
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
                ->where('module', 'bordereaux-bismar')
                ->pluck('role')
                ->unique();

            DB::table('role_permissions')->where('module', 'bordereaux-bismar')->delete();

            foreach ($roles as $role) {
                Cache::forget('role_permissions:'.$role);
            }
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'prime_bismar')) {
                $table->dropColumn('prime_bismar');
            }

            if (Schema::hasColumn('tickets', 'numero_bordereau_bismar')) {
                $table->dropIndex(['numero_bordereau_bismar']);
                $table->dropColumn('numero_bordereau_bismar');
            }
        });

        Schema::dropIfExists('bordereaux_bismar');
    }
};
