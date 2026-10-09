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
        if (! Schema::hasTable('ccas')) {
            Schema::create('ccas', function (Blueprint $table) {
                $table->id('id_cca');
                $table->string('nom');
                $table->decimal('solde', 15, 2)->default(0);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cca_mouvements')) {
            Schema::create('cca_mouvements', function (Blueprint $table) {
                $table->id('id_mouvement');
                $table->unsignedBigInteger('id_cca')->index();
                $table->string('type_mouvement', 50);
                $table->decimal('montant', 15, 2);
                $table->decimal('solde_apres', 15, 2);
                $table->string('libelle')->nullable();
                $table->unsignedBigInteger('id_transaction')->nullable();
                $table->unsignedInteger('id_utilisateur')->nullable();
                $table->dateTime('date_mouvement');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();
        $roles = DB::table('role_permissions')
            ->where('module', 'caisse.banques')
            ->pluck('role')
            ->push('admin')
            ->unique();

        foreach ($roles as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('module', 'caisse.cca')
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'module' => 'caisse.cca',
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
                ->where('module', 'caisse.cca')
                ->pluck('role')
                ->unique();

            DB::table('role_permissions')->where('module', 'caisse.cca')->delete();

            foreach ($roles as $role) {
                Cache::forget('role_permissions:'.$role);
            }
        }

        Schema::dropIfExists('cca_mouvements');
        Schema::dropIfExists('ccas');
    }
};
