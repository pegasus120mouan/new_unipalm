<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financement') || Schema::hasColumn('financement', 'origine')) {
            return;
        }

        Schema::table('financement', function (Blueprint $table) {
            $table->string('origine', 20)->nullable()->after('statut');
            $table->index('origine');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('financement') || ! Schema::hasColumn('financement', 'origine')) {
            return;
        }

        Schema::table('financement', function (Blueprint $table) {
            $table->dropIndex(['origine']);
            $table->dropColumn('origine');
        });
    }
};
