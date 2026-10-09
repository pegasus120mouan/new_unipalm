<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planteur_transferts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planteur_id');
            $table->string('numero_fiche', 100)->nullable();
            $table->string('planteur_nom')->nullable();
            $table->unsignedBigInteger('ancien_collecteur_id')->nullable();
            $table->string('ancien_collecteur_nom')->nullable();
            $table->unsignedBigInteger('nouveau_collecteur_id');
            $table->string('nouveau_collecteur_nom')->nullable();
            $table->text('motif')->nullable();
            $table->unsignedInteger('nb_champs')->default(0);
            $table->decimal('superficie_totale', 12, 2)->default(0);
            $table->json('champs');
            $table->unsignedBigInteger('id_utilisateur')->nullable();
            $table->string('utilisateur_nom')->nullable();
            $table->timestamps();

            $table->index('planteur_id');
            $table->index('ancien_collecteur_id');
            $table->index('nouveau_collecteur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planteur_transferts');
    }
};
