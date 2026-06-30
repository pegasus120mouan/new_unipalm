<?php

namespace App\Services;

use App\Models\Usine;
use App\Models\Utilisateur;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApprovisionnementService
{
    public const SOURCE_USINE_PREFIX = 'Usine: ';

    public const SOURCE_BANQUE_PREFIX = 'Banque: ';

    public function __construct(
        private readonly CaisseService $caisseService,
    ) {}

    public function createManual(float $montant, string $source, Utilisateur $utilisateur): int
    {
        $soldeActuel = $this->caisseService->getSolde();
        $nouveauSolde = $soldeActuel + $montant;

        return (int) DB::table('transactions')->insertGetId([
            'type_transaction' => 'approvisionnement',
            'montant' => $montant,
            'date_transaction' => now(),
            'motifs' => 'Approvisionnement de la caisse',
            'source' => $source,
            'id_utilisateur' => $utilisateur->id,
            'solde' => $nouveauSolde,
            'numero_cheque' => null,
        ]);
    }

    public function recordUsinePayment(
        Usine $usine,
        float $montant,
        string $modePaiement,
        ?string $referencePaiement,
        Utilisateur $utilisateur,
        ?\DateTimeInterface $datePaiement = null,
    ): int {
        $soldeActuel = $this->caisseService->getSolde();
        $nouveauSolde = $soldeActuel + $montant;

        $motifs = 'Paiement usine ('.$modePaiement.')';
        if ($referencePaiement) {
            $motifs .= ' - Réf. '.$referencePaiement;
        }

        return (int) DB::table('transactions')->insertGetId([
            'type_transaction' => 'approvisionnement',
            'montant' => $montant,
            'date_transaction' => $datePaiement ?? now(),
            'motifs' => $motifs,
            'source' => self::SOURCE_USINE_PREFIX.$usine->nom_usine,
            'id_utilisateur' => $utilisateur->id,
            'solde' => $nouveauSolde,
            'numero_cheque' => null,
        ]);
    }

    public function paginatedApprovisionnements(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = DB::table('transactions as t')
            ->leftJoin('utilisateurs as u', 't.id_utilisateur', '=', 'u.id')
            ->where('t.type_transaction', 'approvisionnement')
            ->select([
                't.id_transaction',
                't.montant',
                't.date_transaction',
                't.motifs',
                't.source',
                't.solde',
                DB::raw("TRIM(CONCAT(COALESCE(u.nom, ''), ' ', COALESCE(u.prenoms, ''))) AS nom_utilisateur"),
            ])
            ->orderByDesc('t.date_transaction')
            ->orderByDesc('t.id_transaction');

        $origine = $filters['origine'] ?? 'all';
        if ($origine === 'usine') {
            $query->where('t.source', 'like', self::SOURCE_USINE_PREFIX.'%');
        } elseif ($origine === 'banque') {
            $query->where('t.source', 'like', self::SOURCE_BANQUE_PREFIX.'%');
        } elseif ($origine === 'manuel') {
            $query->where(function ($builder): void {
                $builder
                    ->whereNull('t.source')
                    ->orWhere(function ($inner): void {
                        $inner
                            ->where('t.source', 'not like', self::SOURCE_USINE_PREFIX.'%')
                            ->where('t.source', 'not like', self::SOURCE_BANQUE_PREFIX.'%');
                    });
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('t.source', 'like', '%'.$search.'%')
                    ->orWhere('t.motifs', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('t.date_transaction', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('t.date_transaction', '<=', $filters['date_fin']);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function soldeCaisse(): float
    {
        return $this->caisseService->getSolde();
    }

    public function resumeCaisse(): array
    {
        return $this->caisseService->resume();
    }

    public function stats(): array
    {
        $resume = $this->caisseService->resume();

        $rows = DB::table('transactions')
            ->where('type_transaction', 'approvisionnement')
            ->selectRaw('
                COUNT(*) as total,
                COALESCE(SUM(montant), 0) as total_montant,
                COALESCE(SUM(CASE WHEN source LIKE ? THEN montant ELSE 0 END), 0) as montant_usines,
                COALESCE(SUM(CASE WHEN source LIKE ? THEN montant ELSE 0 END), 0) as montant_banques,
                COALESCE(SUM(CASE WHEN source IS NULL OR (source NOT LIKE ? AND source NOT LIKE ?) THEN montant ELSE 0 END), 0) as montant_manuel
            ', [
                self::SOURCE_USINE_PREFIX.'%',
                self::SOURCE_BANQUE_PREFIX.'%',
                self::SOURCE_USINE_PREFIX.'%',
                self::SOURCE_BANQUE_PREFIX.'%',
            ])
            ->first();

        return [
            'solde_caisse' => $resume['solde_caisse'],
            'montant_utilisable' => $resume['montant_utilisable'],
            'montant_reserve' => $resume['montant_reserve'],
            'total' => (int) ($rows->total ?? 0),
            'total_montant' => (float) ($rows->total_montant ?? 0),
            'montant_usines' => (float) ($rows->montant_usines ?? 0),
            'montant_banques' => (float) ($rows->montant_banques ?? 0),
            'montant_manuel' => (float) ($rows->montant_manuel ?? 0),
        ];
    }

    public function isUsineSource(?string $source): bool
    {
        return $source !== null && str_starts_with($source, self::SOURCE_USINE_PREFIX);
    }

    public function usineNameFromSource(?string $source): ?string
    {
        if (! $this->isUsineSource($source)) {
            return null;
        }

        return trim(substr($source, strlen(self::SOURCE_USINE_PREFIX)));
    }

    public function isBanqueSource(?string $source): bool
    {
        return $source !== null && str_starts_with($source, self::SOURCE_BANQUE_PREFIX);
    }

    public function banqueNameFromSource(?string $source): ?string
    {
        if (! $this->isBanqueSource($source)) {
            return null;
        }

        return trim(substr($source, strlen(self::SOURCE_BANQUE_PREFIX)));
    }
}
