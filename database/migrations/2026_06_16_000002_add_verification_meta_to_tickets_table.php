<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'date_verification')) {
                $table->dateTime('date_verification')->nullable()->after('verification');
            }
            if (! Schema::hasColumn('tickets', 'verifie_par')) {
                $table->string('verifie_par', 255)->nullable()->after('date_verification');
            }
        });

        DB::table('tickets')
            ->where('verification', true)
            ->whereNull('date_verification')
            ->update(['date_verification' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'verifie_par')) {
                $table->dropColumn('verifie_par');
            }
            if (Schema::hasColumn('tickets', 'date_verification')) {
                $table->dropColumn('date_verification');
            }
        });
    }
};
