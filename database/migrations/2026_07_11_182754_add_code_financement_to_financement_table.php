<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financement', function (Blueprint $table) {
            $table->string('code_financement', 40)->nullable()->after('Numero_financement');
            $table->unique('code_financement');
        });

        $rows = DB::table('financement as f')
            ->leftJoin('agents as a', 'a.id_agent', '=', 'f.id_agent')
            ->orderBy('f.Numero_financement')
            ->get([
                'f.Numero_financement',
                'f.id_agent',
                'a.nom',
                'a.prenom',
            ]);

        $sequences = [];

        foreach ($rows as $row) {
            $initials = $this->initials((string) ($row->nom ?? ''), (string) ($row->prenom ?? ''));
            $prefix = 'FIN-'.$initials;
            $sequences[$prefix] = ($sequences[$prefix] ?? 0) + 1;
            $code = $prefix.'-'.sprintf('%04d', $sequences[$prefix]);

            DB::table('financement')
                ->where('Numero_financement', $row->Numero_financement)
                ->update(['code_financement' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('financement', function (Blueprint $table) {
            $table->dropUnique(['code_financement']);
            $table->dropColumn('code_financement');
        });
    }

    private function initials(string $nom, string $prenom): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/u', trim($nom.' '.$prenom)) ?: []));
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';

        $letter = function (string $word): string {
            if ($word === '') {
                return '';
            }
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
            $src = $ascii !== false && $ascii !== '' ? $ascii : $word;

            return strtoupper(substr($src, 0, 1));
        };

        $initials = $letter($first).$letter($second);

        return $initials !== '' ? $initials : 'XX';
    }
};
