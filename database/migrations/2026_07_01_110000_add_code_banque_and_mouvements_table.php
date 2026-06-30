<?php

use App\Models\Banque;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('banques') && ! Schema::hasColumn('banques', 'code_banque')) {
            Schema::table('banques', function (Blueprint $table) {
                $table->string('code_banque', 50)->nullable()->unique()->after('id_banque');
            });
        }

        if (! Schema::hasTable('banque_mouvements')) {
            Schema::create('banque_mouvements', function (Blueprint $table) {
                $table->increments('id_mouvement');
                $table->unsignedInteger('id_banque');
                $table->string('type_mouvement', 40);
                $table->decimal('montant', 15, 2);
                $table->text('libelle');
                $table->string('reference', 255)->nullable();
                $table->unsignedBigInteger('id_utilisateur')->nullable();
                $table->unsignedInteger('id_usine')->nullable();
                $table->decimal('solde_apres', 15, 2);
                $table->timestamp('date_mouvement')->useCurrent();

                $table->foreign('id_banque')
                    ->references('id_banque')
                    ->on('banques')
                    ->cascadeOnDelete();

                $table->index(['id_banque', 'date_mouvement']);
                $table->index('type_mouvement');
            });
        }

        if (! Schema::hasTable('banques') || ! Schema::hasColumn('banques', 'code_banque')) {
            return;
        }

        Banque::query()
            ->whereNull('code_banque')
            ->orderBy('id_banque')
            ->each(function (Banque $banque): void {
                $banque->update(['code_banque' => Banque::genererCodeBanque()]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('banque_mouvements');

        if (Schema::hasTable('banques') && Schema::hasColumn('banques', 'code_banque')) {
            Schema::table('banques', function (Blueprint $table) {
                $table->dropColumn('code_banque');
            });
        }
    }
};
