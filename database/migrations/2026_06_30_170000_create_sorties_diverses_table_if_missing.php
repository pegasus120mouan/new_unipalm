<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sorties_diverses')) {
            return;
        }

        Schema::create('sorties_diverses', function (Blueprint $table) {
            $table->increments('id_sorties');
            $table->string('numero_sorties', 50)->unique();
            $table->decimal('montant', 15, 2);
            $table->dateTime('date_sortie');
            $table->text('motifs');

            $table->index('date_sortie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties_diverses');
    }
};
