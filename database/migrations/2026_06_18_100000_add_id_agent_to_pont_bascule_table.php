<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pont_bascule') || Schema::hasColumn('pont_bascule', 'id_agent')) {
            return;
        }

        Schema::table('pont_bascule', function (Blueprint $table) {
            $table->integer('id_agent')->nullable()->after('nom_pont');
            $table->foreign('id_agent')
                ->references('id_agent')
                ->on('agents')
                ->nullOnDelete();
        });

        $this->migrateGerantToAgent();
    }

    public function down(): void
    {
        if (! Schema::hasTable('pont_bascule') || ! Schema::hasColumn('pont_bascule', 'id_agent')) {
            return;
        }

        Schema::table('pont_bascule', function (Blueprint $table) {
            $table->dropForeign(['id_agent']);
            $table->dropColumn('id_agent');
        });
    }

    private function migrateGerantToAgent(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        $agents = DB::table('agents')
            ->whereNull('date_suppression')
            ->select('id_agent', 'nom', 'prenom')
            ->get()
            ->map(function ($agent) {
                $agent->full_name = trim("{$agent->nom} {$agent->prenom}");

                return $agent;
            });

        $ponts = DB::table('pont_bascule')->select('id_pont', 'gerant')->get();

        foreach ($ponts as $pont) {
            $gerant = trim((string) $pont->gerant);

            if ($gerant === '') {
                continue;
            }

            $match = $agents->first(function ($agent) use ($gerant) {
                return strcasecmp($agent->full_name, $gerant) === 0;
            });

            if ($match) {
                DB::table('pont_bascule')
                    ->where('id_pont', $pont->id_pont)
                    ->update(['id_agent' => $match->id_agent]);
            }
        }
    }
};
