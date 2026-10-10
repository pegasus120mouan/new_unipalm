<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_parametres')) {
            return;
        }

        Schema::create('ticket_parametres', function (Blueprint $table) {
            $table->id();
            $table->boolean('exiger_verification')->default(false);
            $table->timestamps();
        });

        DB::table('ticket_parametres')->insert([
            'exiger_verification' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_parametres');
    }
};
