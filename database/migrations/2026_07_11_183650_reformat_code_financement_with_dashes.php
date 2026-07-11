<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financement') || ! Schema::hasColumn('financement', 'code_financement')) {
            return;
        }

        $rows = DB::table('financement as f')
            ->leftJoin('agents as a', 'a.id_agent', '=', 'f.id_agent')
            ->orderBy('f.Numero_financement')
            ->get([
                'f.Numero_financement',
                'a.nom',
                'a.prenom',
            ]);

        $sequences = [];

        foreach ($rows as $row) {
            $nomComplet = trim(((string) ($row->nom ?? '')).' '.((string) ($row->prenom ?? '')));
            $initials = $this->initials($nomComplet);
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
        // irreversible data reformatting
    }

    private function initials(string $nomComplet): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/u', trim($nomComplet)) ?: []));
        $nom = $parts[0] ?? '';
        $prenom = $parts[1] ?? '';

        $letter = function (string $word): string {
            if ($word === '') {
                return '';
            }
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
            $src = is_string($ascii) && $ascii !== '' ? $ascii : $word;

            return strtoupper(substr($src, 0, 1));
        };

        $initials = $letter($nom).$letter($prenom);

        return $initials !== '' ? $initials : 'XX';
    }
};
