<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departements')) {
            Schema::table('departements', function (Blueprint $table) {
                if (! Schema::hasColumn('departements', 'geojson')) {
                    $table->longText('geojson')->nullable()->after('nom');
                }
            });

            return;
        }

        Schema::create('departements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('region_id');
            $table->string('code', 10)->nullable();
            $table->string('nom', 150);
            $table->longText('geojson')->nullable();
            $table->foreign('region_id')
                ->references('id')
                ->on('regions')
                ->cascadeOnDelete();
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};
