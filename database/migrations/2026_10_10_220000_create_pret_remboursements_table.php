<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pret_remboursements')) {
            Schema::create('pret_remboursements', function (Blueprint $table) {
                $table->id('id_remboursement');
                $table->unsignedBigInteger('id_pret')->index();
                $table->unsignedBigInteger('id_mouvement')->nullable()->index();
                $table->decimal('montant', 15, 2);
                $table->dateTime('date_remboursement');
                $table->string('libelle')->nullable();
                $table->unsignedInteger('id_utilisateur')->nullable();
                $table->timestamps();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('pret_remboursements');
    }

    private function backfill(): void
    {
        if (! Schema::hasTable('cca_mouvements') || ! Schema::hasTable('prets')) {
            return;
        }

        $mouvements = DB::table('cca_mouvements')
            ->where('type_mouvement', 'remboursement')
            ->where('source', 'pret_agent')
            ->orderBy('date_mouvement')
            ->orderBy('id_mouvement')
            ->get();

        foreach ($mouvements as $mouvement) {
            $nom = trim((string) preg_replace('/^Remboursement via prêt agent\s*:\s*/u', '', (string) $mouvement->libelle));
            if ($nom === '') {
                continue;
            }

            $agent = DB::table('agents')
                ->whereRaw("TRIM(CONCAT(nom, ' ', COALESCE(prenom, ''))) = ?", [$nom])
                ->first();

            if ($agent === null) {
                continue;
            }

            $prets = DB::table('prets')
                ->where('id_agent', $agent->id_agent)
                ->where('id_cca', $mouvement->id_cca)
                ->where('origine', 'cca')
                ->orderBy('date_octroi')
                ->orderBy('id_pret')
                ->get();

            $reste = (float) $mouvement->montant;

            foreach ($prets as $pret) {
                if ($reste <= 0.009) {
                    break;
                }

                $deja = (float) DB::table('pret_remboursements')->where('id_pret', $pret->id_pret)->sum('montant');
                $gap = (float) $pret->montant_initial - (float) $pret->montant_restant - $deja;
                $applique = min($reste, max(0, $gap));

                if ($applique <= 0.009) {
                    continue;
                }

                DB::table('pret_remboursements')->insert([
                    'id_pret' => $pret->id_pret,
                    'id_mouvement' => $mouvement->id_mouvement,
                    'montant' => $applique,
                    'date_remboursement' => $mouvement->date_mouvement,
                    'libelle' => $mouvement->libelle,
                    'id_utilisateur' => $mouvement->id_utilisateur,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $reste -= $applique;
            }
        }
    }
};
