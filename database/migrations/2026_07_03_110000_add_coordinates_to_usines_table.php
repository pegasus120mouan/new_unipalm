<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usines', function (Blueprint $table) {
            if (! Schema::hasColumn('usines', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('nom_usine');
            }
            if (! Schema::hasColumn('usines', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usines', function (Blueprint $table) {
            if (Schema::hasColumn('usines', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('usines', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};
