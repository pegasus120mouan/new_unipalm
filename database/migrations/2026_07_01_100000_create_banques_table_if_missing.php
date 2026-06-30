<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('banques')) {
            return;
        }

        Schema::create('banques', function (Blueprint $table) {
            $table->increments('id_banque');
            $table->string('nom_banque', 150);
            $table->string('numero_compte', 100)->nullable();
            $table->decimal('solde', 15, 2)->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique('nom_banque');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banques');
    }
};
