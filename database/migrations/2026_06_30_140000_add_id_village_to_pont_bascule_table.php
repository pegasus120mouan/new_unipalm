<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pont_bascule')) {
            return;
        }

        Schema::table('pont_bascule', function (Blueprint $table) {
            if (! Schema::hasColumn('pont_bascule', 'id_village')) {
                $table->unsignedInteger('id_village')->nullable()->after('id_sous_prefecture');
                $table->foreign('id_village')
                    ->references('id')
                    ->on('villages')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pont_bascule') || ! Schema::hasColumn('pont_bascule', 'id_village')) {
            return;
        }

        Schema::table('pont_bascule', function (Blueprint $table) {
            $table->dropForeign(['id_village']);
            $table->dropColumn('id_village');
        });
    }
};
