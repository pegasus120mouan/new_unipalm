<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prets', function (Blueprint $table) {
            if (! Schema::hasColumn('prets', 'origine')) {
                $table->string('origine', 20)->nullable()->after('motif');
            }
            if (! Schema::hasColumn('prets', 'id_cca')) {
                $table->unsignedBigInteger('id_cca')->nullable()->after('origine')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('prets', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['id_cca', 'origine'],
                fn (string $column) => Schema::hasColumn('prets', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
