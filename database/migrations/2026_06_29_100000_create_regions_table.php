<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regions')) {
            return;
        }

        Schema::create('regions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 10)->nullable();
            $table->string('nom', 100)->nullable();
            $table->longText('geojson')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
