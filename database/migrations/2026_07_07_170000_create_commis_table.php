<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commis')) {
            return;
        }

        Schema::create('commis', function (Blueprint $table) {
            $table->increments('id_commis');
            $table->string('nom');
            $table->string('prenoms');
            $table->string('contact');
            $table->integer('id_agent');
            $table->integer('id_pont');
            $table->unsignedBigInteger('cree_par')->nullable();
            $table->dateTime('date_ajout')->useCurrent();
            $table->dateTime('date_modification')->nullable();
            $table->dateTime('date_suppression')->nullable();

            $table->unique('id_pont');
            $table->index('id_agent');
            $table->index('date_suppression');

            $table->foreign('id_agent')
                ->references('id_agent')
                ->on('agents')
                ->cascadeOnDelete();

            $table->foreign('id_pont')
                ->references('id_pont')
                ->on('pont_bascule')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commis');
    }
};
