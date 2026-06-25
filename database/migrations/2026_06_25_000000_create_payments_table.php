<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('id_usine');
            $table->decimal('montant', 15, 2);
            $table->date('date_paiement');
            $table->string('mode_paiement', 100);
            $table->string('reference_paiement', 255)->nullable();
            $table->timestamps();

            $table->index('id_usine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
