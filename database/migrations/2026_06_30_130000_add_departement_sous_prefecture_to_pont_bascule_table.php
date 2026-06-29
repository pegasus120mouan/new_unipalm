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
            if (! Schema::hasColumn('pont_bascule', 'id_departement')) {
                $table->unsignedInteger('id_departement')->nullable()->after('id_region');
                $table->foreign('id_departement')
                    ->references('id')
                    ->on('departements')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('pont_bascule', 'id_sous_prefecture')) {
                $table->unsignedInteger('id_sous_prefecture')->nullable()->after('id_departement');
                $table->foreign('id_sous_prefecture')
                    ->references('id')
                    ->on('sous_prefectures')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pont_bascule')) {
            return;
        }

        Schema::table('pont_bascule', function (Blueprint $table) {
            if (Schema::hasColumn('pont_bascule', 'id_sous_prefecture')) {
                $table->dropForeign(['id_sous_prefecture']);
                $table->dropColumn('id_sous_prefecture');
            }

            if (Schema::hasColumn('pont_bascule', 'id_departement')) {
                $table->dropForeign(['id_departement']);
                $table->dropColumn('id_departement');
            }
        });
    }
};
