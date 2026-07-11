<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financement')) {
            return;
        }

        if (! Schema::hasColumn('financement', 'statut')) {
            Schema::table('financement', function (Blueprint $table) {
                $table->string('statut', 30)->default('valide')->after('motif');
                $table->index('statut');
            });
        }

        DB::table('financement')
            ->whereNull('statut')
            ->orWhere('statut', '')
            ->update(['statut' => 'valide']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('financement') || ! Schema::hasColumn('financement', 'statut')) {
            return;
        }

        Schema::table('financement', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropColumn('statut');
        });
    }
};
