<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cca_mouvements', function (Blueprint $table) {
            if (! Schema::hasColumn('cca_mouvements', 'source')) {
                $table->string('source', 20)->nullable()->after('montant');
            }
            if (! Schema::hasColumn('cca_mouvements', 'id_banque')) {
                $table->unsignedBigInteger('id_banque')->nullable()->after('source');
            }
            if (! Schema::hasColumn('cca_mouvements', 'mode_paiement')) {
                $table->string('mode_paiement', 20)->nullable()->after('id_banque');
            }
            if (! Schema::hasColumn('cca_mouvements', 'numero_cheque')) {
                $table->string('numero_cheque', 100)->nullable()->after('mode_paiement');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cca_mouvements', function (Blueprint $table) {
            $table->dropColumn(['source', 'id_banque', 'mode_paiement', 'numero_cheque']);
        });
    }
};
