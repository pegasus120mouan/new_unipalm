<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sous_prefectures')) {
            return;
        }

        Schema::table('sous_prefectures', function (Blueprint $table) {
            if (! Schema::hasColumn('sous_prefectures', 'geojson')) {
                $table->longText('geojson')->nullable()->after('nom');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sous_prefectures') || ! Schema::hasColumn('sous_prefectures', 'geojson')) {
            return;
        }

        Schema::table('sous_prefectures', function (Blueprint $table) {
            $table->dropColumn('geojson');
        });
    }
};
