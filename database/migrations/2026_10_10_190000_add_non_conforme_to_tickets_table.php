<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'non_conforme')) {
                $table->boolean('non_conforme')->default(false);
            }
            if (! Schema::hasColumn('tickets', 'motif_non_conforme')) {
                $table->string('motif_non_conforme', 500)->nullable();
            }
            if (! Schema::hasColumn('tickets', 'date_non_conforme')) {
                $table->dateTime('date_non_conforme')->nullable();
            }
            if (! Schema::hasColumn('tickets', 'rejete_par')) {
                $table->string('rejete_par', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['rejete_par', 'date_non_conforme', 'motif_non_conforme', 'non_conforme'] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
