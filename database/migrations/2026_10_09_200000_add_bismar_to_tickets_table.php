<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'bismar')) {
                $table->boolean('bismar')->default(false)->after('poids');
            }

            if (! Schema::hasColumn('tickets', 'code_transfert')) {
                $table->string('code_transfert', 255)->nullable()->after('bismar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'code_transfert')) {
                $table->dropColumn('code_transfert');
            }

            if (Schema::hasColumn('tickets', 'bismar')) {
                $table->dropColumn('bismar');
            }
        });
    }
};
