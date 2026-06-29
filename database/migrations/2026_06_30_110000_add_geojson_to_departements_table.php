<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departements')) {
            return;
        }

        Schema::table('departements', function (Blueprint $table) {
            if (! Schema::hasColumn('departements', 'geojson')) {
                $table->longText('geojson')->nullable()->after('nom');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('departements') || ! Schema::hasColumn('departements', 'geojson')) {
            return;
        }

        Schema::table('departements', function (Blueprint $table) {
            $table->dropColumn('geojson');
        });
    }
};
