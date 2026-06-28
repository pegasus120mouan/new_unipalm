<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('types_pont')) {
            Schema::create('types_pont', function (Blueprint $table) {
                $table->id('id_type_pont');
                $table->string('libelle', 100)->unique();
                $table->timestamps();
            });
        }

        $defaults = ['3 mètres', '6 mètres', '9 mètres', '12 mètres', '15 mètres'];
        $now = now();

        foreach ($defaults as $libelle) {
            $exists = DB::table('types_pont')->where('libelle', $libelle)->exists();
            if (! $exists) {
                DB::table('types_pont')->insert([
                    'libelle' => $libelle,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('pont_bascule') && ! Schema::hasColumn('pont_bascule', 'id_type_pont')) {
            Schema::table('pont_bascule', function (Blueprint $table) {
                $table->unsignedBigInteger('id_type_pont')->nullable()->after('nom_pont');
                $table->foreign('id_type_pont')
                    ->references('id_type_pont')
                    ->on('types_pont')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pont_bascule') && Schema::hasColumn('pont_bascule', 'id_type_pont')) {
            Schema::table('pont_bascule', function (Blueprint $table) {
                $table->dropForeign(['id_type_pont']);
                $table->dropColumn('id_type_pont');
            });
        }

        Schema::dropIfExists('types_pont');
    }
};
