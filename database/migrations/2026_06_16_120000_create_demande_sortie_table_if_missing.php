<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demande_sortie')) {
            return;
        }

        Schema::create('demande_sortie', function (Blueprint $table) {
            $table->increments('id_demande');
            $table->string('numero_demande', 50)->unique();
            $table->dateTime('date_demande');
            $table->decimal('montant', 15, 2);
            $table->text('motif');
            $table->string('statut', 20)->default('en_attente');
            $table->dateTime('date_approbation')->nullable();
            $table->unsignedBigInteger('approuve_par')->nullable();
            $table->dateTime('date_paiement')->nullable();
            $table->unsignedBigInteger('paye_par')->nullable();
            $table->decimal('montant_payer', 15, 2)->default(0);
            $table->decimal('montant_reste', 15, 2)->nullable();
            $table->timestamps();

            $table->index('statut');
            $table->index('date_demande');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_sortie');
    }
};
