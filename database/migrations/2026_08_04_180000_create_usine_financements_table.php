<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('usine_financements');

        Schema::create('usine_financements', function (Blueprint $table) {
            $table->id();
            $table->string('code_financement', 50)->nullable()->unique();
            $table->integer('id_usine');
            $table->decimal('montant', 15, 2);
            $table->text('motif')->nullable();
            $table->date('date_financement');
            $table->unsignedInteger('id_banque')->nullable();
            $table->string('mode_paiement', 100)->nullable();
            $table->string('reference_paiement')->nullable();
            $table->unsignedBigInteger('id_utilisateur')->nullable();
            $table->timestamps();

            $table->index('id_usine');
            $table->index('date_financement');

            $table->foreign('id_usine')
                ->references('id_usine')
                ->on('usines')
                ->cascadeOnDelete();

            $table->foreign('id_banque')
                ->references('id_banque')
                ->on('banques')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usine_financements');
    }
};
