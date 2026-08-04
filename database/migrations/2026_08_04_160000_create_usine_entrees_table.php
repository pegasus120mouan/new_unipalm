<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usine_entrees')) {
            return;
        }

        Schema::create('usine_entrees', function (Blueprint $table) {
            $table->id();
            $table->integer('id_usine');
            $table->date('date_entree');
            $table->decimal('poids_usine', 12, 2)->default(0);
            $table->decimal('prix_unitaire', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['id_usine', 'date_entree']);
            $table->foreign('id_usine')
                ->references('id_usine')
                ->on('usines')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usine_entrees');
    }
};
