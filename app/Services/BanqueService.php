<?php

namespace App\Services;

use App\Models\Banque;
use App\Models\BanqueMouvement;
use App\Models\Usine;
use App\Models\UsineFinancement;
use App\Models\Utilisateur;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BanqueService
{
    public function __construct(
        private readonly ApprovisionnementService $approvisionnementService,
        private readonly UsinePaymentService $usinePaymentService,
    ) {}

    public function statsForBanque(Banque $banque): array
    {
        $rows = BanqueMouvement::query()
            ->where('id_banque', $banque->id_banque)
            ->selectRaw('
                COUNT(*) as total_mouvements,
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) as total_manuel,
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) as total_usine,
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) as total_initial
            ', [
                BanqueMouvement::TYPE_MANUEL,
                BanqueMouvement::TYPE_USINE,
                BanqueMouvement::TYPE_SOLDE_INITIAL,
            ])
            ->first();

        return [
            'solde' => (float) $banque->solde,
            'total_mouvements' => (int) ($rows->total_mouvements ?? 0),
            'total_manuel' => (float) ($rows->total_manuel ?? 0),
            'total_usine' => (float) ($rows->total_usine ?? 0),
            'total_initial' => (float) ($rows->total_initial ?? 0),
            'total_entrees' => (float) BanqueMouvement::query()
                ->where('id_banque', $banque->id_banque)
                ->sum('montant'),
        ];
    }

    public function paginatedMouvements(Banque $banque, array $filters): LengthAwarePaginator
    {
        $query = BanqueMouvement::query()
            ->with(['utilisateur', 'usine'])
            ->where('id_banque', $banque->id_banque)
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id_mouvement');

        $type = $filters['type'] ?? 'all';
        if ($type !== 'all' && $type !== '') {
            $query->where('type_mouvement', $type);
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_mouvement', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_mouvement', '<=', $filters['date_fin']);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function enregistrerSoldeInitial(Banque $banque, float $montant, Utilisateur $utilisateur): void
    {
        if ($montant <= 0) {
            return;
        }

        $this->crediter(
            $banque,
            $montant,
            BanqueMouvement::TYPE_SOLDE_INITIAL,
            'Solde initial à l\'ouverture du compte',
            $utilisateur,
        );
    }

    public function approvisionnementManuel(
        Banque $banque,
        float $montant,
        string $libelle,
        Utilisateur $utilisateur,
    ): void {
        DB::transaction(function () use ($banque, $montant, $libelle, $utilisateur): void {
            $this->crediter(
                $banque,
                $montant,
                BanqueMouvement::TYPE_MANUEL,
                $libelle,
                $utilisateur,
            );

            $this->approvisionnementService->createManual(
                $montant,
                $banque->nom_banque.' — '.$libelle,
                $utilisateur,
            );
        });
    }

    public function approvisionnementCaisse(
        Banque $banque,
        float $montant,
        Utilisateur $utilisateur,
    ): void {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        $banque->refresh();

        if ((float) $banque->solde < $montant) {
            throw new \InvalidArgumentException(
                'Solde insuffisant sur '.$banque->nom_banque.' (disponible : '
                .number_format((float) $banque->solde, 0, ',', ' ')
                .' FCFA).',
            );
        }

        DB::transaction(function () use ($banque, $montant, $utilisateur): void {
            $libelle = 'Approvisionnement caisse depuis '.$banque->nom_banque;

            $this->debiter(
                $banque,
                $montant,
                BanqueMouvement::TYPE_CAISSE,
                $libelle,
                $utilisateur,
            );

            $this->approvisionnementService->createManual(
                $montant,
                ApprovisionnementService::SOURCE_BANQUE_PREFIX.$banque->nom_banque,
                $utilisateur,
            );
        });
    }

    public function statsApprovisionnementsCaisse(): array
    {
        $banques = Banque::query()->where('actif', true)->orderBy('nom_banque')->get();

        $totalVerse = (float) BanqueMouvement::query()
            ->where('type_mouvement', BanqueMouvement::TYPE_CAISSE)
            ->sum('montant');

        $resumeCaisse = $this->approvisionnementService->resumeCaisse();

        return [
            'nombre_banques' => $banques->count(),
            'solde_total' => (float) $banques->sum('solde'),
            'total_verse_caisse' => $totalVerse,
            'nombre_operations' => (int) BanqueMouvement::query()
                ->where('type_mouvement', BanqueMouvement::TYPE_CAISSE)
                ->count(),
            'solde_caisse' => $resumeCaisse['solde_caisse'],
            'montant_utilisable' => $resumeCaisse['montant_utilisable'],
            'montant_reserve' => $resumeCaisse['montant_reserve'],
        ];
    }

    public function paginatedApprovisionnementsCaisse(array $filters): LengthAwarePaginator
    {
        $query = BanqueMouvement::query()
            ->with(['banque', 'utilisateur'])
            ->where('type_mouvement', BanqueMouvement::TYPE_CAISSE)
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id_mouvement');

        if (! empty($filters['id_banque'])) {
            $query->where('id_banque', $filters['id_banque']);
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_mouvement', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_mouvement', '<=', $filters['date_fin']);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function paiementUsine(
        Banque $banque,
        Usine $usine,
        float $montant,
        string $datePaiement,
        string $modePaiement,
        ?string $referencePaiement,
        Utilisateur $utilisateur,
        ?float $restePlafond = null,
        bool $distributeToTickets = true,
    ): void {
        DB::transaction(function () use (
            $banque,
            $usine,
            $montant,
            $datePaiement,
            $modePaiement,
            $referencePaiement,
            $utilisateur,
            $restePlafond,
            $distributeToTickets,
        ): void {
            $this->usinePaymentService->create(
                $usine,
                $montant,
                $datePaiement,
                $modePaiement,
                $referencePaiement,
                $utilisateur,
                crediterCaisse: false,
                restePlafond: $restePlafond,
                distributeToTickets: $distributeToTickets,
            );

            $libelle = 'Paiement usine '.$usine->nom_usine.' ('.$modePaiement.')';

            $this->crediter(
                $banque,
                $montant,
                BanqueMouvement::TYPE_USINE,
                $libelle,
                $utilisateur,
                $usine->id_usine,
                $referencePaiement,
                Carbon::parse($datePaiement),
            );
        });
    }

    /**
     * Enregistre un financement reçu d'une usine et crédite la banque sélectionnée.
     */
    public function financementUsine(
        Banque $banque,
        Usine $usine,
        float $montant,
        string $dateFinancement,
        string $modePaiement,
        ?string $referencePaiement,
        ?string $motif,
        Utilisateur $utilisateur,
    ): UsineFinancement {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant du financement doit être supérieur à 0.');
        }

        return DB::transaction(function () use (
            $banque,
            $usine,
            $montant,
            $dateFinancement,
            $modePaiement,
            $referencePaiement,
            $motif,
            $utilisateur,
        ): UsineFinancement {
            $financement = UsineFinancement::query()->create([
                'code_financement' => UsineFinancement::genererCode(),
                'id_usine' => $usine->id_usine,
                'montant' => $montant,
                'motif' => $motif,
                'date_financement' => $dateFinancement,
                'id_banque' => $banque->id_banque,
                'mode_paiement' => $modePaiement,
                'reference_paiement' => $referencePaiement,
                'id_utilisateur' => $utilisateur->id,
            ]);

            $libelle = 'Financement usine '.$usine->nom_usine;
            if ($motif) {
                $libelle .= ' — '.$motif;
            }

            $this->crediter(
                $banque,
                $montant,
                BanqueMouvement::TYPE_FINANCEMENT_USINE,
                $libelle,
                $utilisateur,
                $usine->id_usine,
                $referencePaiement,
                Carbon::parse($dateFinancement),
            );

            return $financement;
        });
    }

    private function crediter(
        Banque $banque,
        float $montant,
        string $type,
        string $libelle,
        Utilisateur $utilisateur,
        ?int $idUsine = null,
        ?string $reference = null,
        ?Carbon $dateMouvement = null,
    ): void {
        $banque->refresh();
        $nouveauSolde = (float) $banque->solde + $montant;

        $banque->update(['solde' => $nouveauSolde]);

        BanqueMouvement::query()->create([
            'id_banque' => $banque->id_banque,
            'type_mouvement' => $type,
            'montant' => $montant,
            'libelle' => $libelle,
            'reference' => $reference,
            'id_utilisateur' => $utilisateur->id,
            'id_usine' => $idUsine,
            'solde_apres' => $nouveauSolde,
            'date_mouvement' => $dateMouvement ?? now(),
        ]);
    }

    private function debiter(
        Banque $banque,
        float $montant,
        string $type,
        string $libelle,
        Utilisateur $utilisateur,
        ?Carbon $dateMouvement = null,
    ): void {
        $banque->refresh();
        $nouveauSolde = (float) $banque->solde - $montant;

        $banque->update(['solde' => $nouveauSolde]);

        BanqueMouvement::query()->create([
            'id_banque' => $banque->id_banque,
            'type_mouvement' => $type,
            'montant' => $montant,
            'libelle' => $libelle,
            'reference' => null,
            'id_utilisateur' => $utilisateur->id,
            'id_usine' => null,
            'solde_apres' => $nouveauSolde,
            'date_mouvement' => $dateMouvement ?? now(),
        ]);
    }
}
