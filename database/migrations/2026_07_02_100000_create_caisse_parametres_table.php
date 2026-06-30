<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('caisse_parametres')) {
            return;
        }

        Schema::create('caisse_parametres', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant_utilisable', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('caisse_utilisable_logs', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant_avant', 15, 2);
            $table->decimal('montant_apres', 15, 2);
            $table->unsignedBigInteger('id_utilisateur');
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_utilisateur');
        });

        $solde = (float) (DB::table('transactions')
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type_transaction = 'approvisionnement' THEN montant
                WHEN type_transaction = 'paiement' THEN -montant
                ELSE 0
            END), 0) AS solde")
            ->value('solde') ?? 0);

        DB::table('caisse_parametres')->insert([
            'montant_utilisable' => max(0, $solde),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_utilisable_logs');
        Schema::dropIfExists('caisse_parametres');
    }
};
