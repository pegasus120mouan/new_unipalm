<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pont_bascule') || Schema::hasColumn('pont_bascule', 'id_region')) {
            return;
        }

        Schema::table('pont_bascule', function (Blueprint $table) {
            $table->unsignedInteger('id_region')->nullable()->after('id_type_pont');
            $table->foreign('id_region')
                ->references('id')
                ->on('regions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pont_bascule') || ! Schema::hasColumn('pont_bascule', 'id_region')) {
            return;
        }

        Schema::table('pont_bascule', function (Blueprint $table) {
            $table->dropForeign(['id_region']);
            $table->dropColumn('id_region');
        });
    }
};
