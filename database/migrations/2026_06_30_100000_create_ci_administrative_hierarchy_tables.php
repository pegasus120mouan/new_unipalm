<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 10)->nullable();
                $table->string('nom', 150);
                $table->unique('code');
            });
        }

        if (Schema::hasTable('regions')) {
            Schema::table('regions', function (Blueprint $table) {
                if (! Schema::hasColumn('regions', 'district_id')) {
                    $table->unsignedInteger('district_id')->nullable()->after('id');
                    $table->foreign('district_id')
                        ->references('id')
                        ->on('districts')
                        ->nullOnDelete();
                }

                $table->string('nom', 150)->nullable()->change();
            });
        }

        if (! Schema::hasTable('departements')) {
            Schema::create('departements', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('region_id');
                $table->string('code', 10)->nullable();
                $table->string('nom', 150);
                $table->foreign('region_id')
                    ->references('id')
                    ->on('regions')
                    ->cascadeOnDelete();
                $table->index('region_id');
            });
        }

        if (! Schema::hasTable('sous_prefectures')) {
            Schema::create('sous_prefectures', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('departement_id');
                $table->string('code', 10)->nullable();
                $table->string('nom', 150);
                $table->foreign('departement_id')
                    ->references('id')
                    ->on('departements')
                    ->cascadeOnDelete();
                $table->index('departement_id');
            });
        }

        if (! Schema::hasTable('villages')) {
            Schema::create('villages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sous_prefecture_id');
                $table->string('nom', 200);
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->foreign('sous_prefecture_id')
                    ->references('id')
                    ->on('sous_prefectures')
                    ->cascadeOnDelete();
                $table->index('sous_prefecture_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('sous_prefectures');
        Schema::dropIfExists('departements');

        if (Schema::hasTable('regions') && Schema::hasColumn('regions', 'district_id')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->dropForeign(['district_id']);
                $table->dropColumn('district_id');
            });
        }

        Schema::dropIfExists('districts');
    }
};
