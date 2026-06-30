<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('demande_sortie')) {
            return;
        }

        if (Schema::hasColumn('demande_sortie', 'motif_refus')) {
            return;
        }

        Schema::table('demande_sortie', function (Blueprint $table) {
            $table->text('motif_refus')->nullable()->after('motif');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('demande_sortie') || ! Schema::hasColumn('demande_sortie', 'motif_refus')) {
            return;
        }

        Schema::table('demande_sortie', function (Blueprint $table) {
            $table->dropColumn('motif_refus');
        });
    }
};
