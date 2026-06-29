<?php

namespace App\Http\Controllers;

use App\Models\Vehicule;
use App\Services\VehiculeDuplicateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    public function __construct(
        private readonly VehiculeDuplicateService $duplicateService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $duplicatesOnly = $request->boolean('duplicates');

        $vehiculesQuery = Vehicule::query()
            ->withCount('tickets')
            ->orderByDesc('created_at')
            ->orderByDesc('vehicules_id');

        if ($search !== '') {
            $vehiculesQuery->where('matricule_vehicule', 'like', '%'.$search.'%');
        }

        if (in_array($type, ['voiture', 'moto', 'tricycle'], true)) {
            $vehiculesQuery->where('type_vehicule', $type);
        }

        $duplicateMatricules = $this->duplicateNormalizedMatricules();

        if ($duplicatesOnly && $duplicateMatricules !== []) {
            $placeholders = implode(',', array_fill(0, count($duplicateMatricules), '?'));
            $vehiculesQuery->whereRaw(
                'UPPER(REPLACE(matricule_vehicule, " ", "")) IN ('.$placeholders.')',
                $duplicateMatricules
            );
        }

        $vehicules = $vehiculesQuery
            ->paginate(15)
            ->withQueryString();

        $deletableDuplicateIds = $this->duplicateService->deletableDuplicateIds($duplicateMatricules);

        $stats = [
            'duplicates' => count($duplicateMatricules),
            'deletable_duplicates' => count($deletableDuplicateIds),
        ];

        return view('vehicules.index', compact(
            'vehicules',
            'search',
            'type',
            'duplicatesOnly',
            'duplicateMatricules',
            'deletableDuplicateIds',
            'stats',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matricule_vehicule' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $normalized = Vehicule::normalizeMatricule((string) $value);

                    if ($normalized === '') {
                        $fail('Le matricule est invalide.');

                        return;
                    }

                    $exists = Vehicule::query()
                        ->whereRaw('UPPER(REPLACE(matricule_vehicule, " ", "")) = ?', [$normalized])
                        ->exists();

                    if ($exists) {
                        $fail('Ce matricule existe déjà (les espaces et la casse sont ignorés).');
                    }
                },
            ],
            'type_vehicule' => ['required', Rule::in(['voiture', 'moto', 'tricycle'])],
        ]);

        Vehicule::query()->create([
            'matricule_vehicule' => Vehicule::normalizeMatricule($validated['matricule_vehicule']),
            'type_vehicule' => $validated['type_vehicule'],
        ]);

        return redirect()
            ->route('vehicules.index')
            ->with('success', 'Véhicule enregistré avec succès.');
    }

    public function destroy(Vehicule $vehicule): RedirectResponse
    {
        $duplicateMatricules = $this->duplicateNormalizedMatricules();

        if (! $this->duplicateService->isDuplicateEligibleForDeletion($vehicule, $duplicateMatricules)) {
            return back()->withErrors([
                'vehicule' => 'Seuls les véhicules en doublon (hors exemplaire conservé) peuvent être supprimés depuis cette page.',
            ]);
        }

        $keeper = $this->duplicateService->keeperForNormalizedMatricule($vehicule->normalizedMatricule());

        if ($keeper === null) {
            return back()->withErrors(['vehicule' => 'Exemplaire conservé introuvable pour ce matricule.']);
        }

        $result = $this->duplicateService->deleteDuplicate($vehicule, $keeper);

        return redirect()
            ->route('vehicules.index', ['duplicates' => 1])
            ->with('success', $this->buildDeletionMessage($vehicule->matricule_vehicule, $result['tickets_deleted'], $result['tickets_reassigned']));
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicules_ids' => ['required', 'array', 'min:1'],
            'vehicules_ids.*' => ['integer', Rule::exists('vehicules', 'vehicules_id')],
        ]);

        $duplicateMatricules = $this->duplicateNormalizedMatricules();
        $allowedIds = $this->duplicateService->deletableDuplicateIds($duplicateMatricules);
        $requestedIds = array_values(array_unique(array_map('intval', $validated['vehicules_ids'])));

        $deleted = 0;
        $skipped = 0;
        $ticketsDeleted = 0;
        $ticketsReassigned = 0;

        foreach ($requestedIds as $id) {
            if (! in_array($id, $allowedIds, true)) {
                $skipped++;

                continue;
            }

            $vehicule = Vehicule::query()->find($id);

            if ($vehicule === null || ! $this->duplicateService->isDuplicateEligibleForDeletion($vehicule, $duplicateMatricules)) {
                $skipped++;

                continue;
            }

            $keeper = $this->duplicateService->keeperForNormalizedMatricule($vehicule->normalizedMatricule());

            if ($keeper === null) {
                $skipped++;

                continue;
            }

            $result = $this->duplicateService->deleteDuplicate($vehicule, $keeper);
            $deleted++;
            $ticketsDeleted += $result['tickets_deleted'];
            $ticketsReassigned += $result['tickets_reassigned'];
        }

        if ($deleted === 0) {
            return back()->withErrors([
                'vehicule' => 'Aucun doublon n\'a pu être supprimé.'
                    .($skipped > 0 ? " {$skipped} entrée(s) ignorée(s)." : ''),
            ]);
        }

        $message = "{$deleted} doublon(s) supprimé(s).";
        if ($ticketsDeleted > 0) {
            $message .= " {$ticketsDeleted} ticket(s) supprimé(s).";
        }
        if ($ticketsReassigned > 0) {
            $message .= " {$ticketsReassigned} ticket(s) soldé(s) réaffecté(s) à l'exemplaire conservé.";
        }
        if ($skipped > 0) {
            $message .= " {$skipped} entrée(s) ignorée(s).";
        }

        return redirect()
            ->route('vehicules.index', ['duplicates' => 1])
            ->with('success', $message);
    }

    /**
     * @return list<string>
     */
    private function duplicateNormalizedMatricules(): array
    {
        return Vehicule::query()
            ->selectRaw('UPPER(REPLACE(matricule_vehicule, " ", "")) as normalized_matricule')
            ->groupBy('normalized_matricule')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_matricule')
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();
    }

    private function buildDeletionMessage(string $label, int $ticketsDeleted, int $ticketsReassigned): string
    {
        $message = "Véhicule « {$label} » supprimé avec succès.";

        if ($ticketsDeleted > 0) {
            $message .= " {$ticketsDeleted} ticket(s) supprimé(s).";
        }

        if ($ticketsReassigned > 0) {
            $message .= " {$ticketsReassigned} ticket(s) soldé(s) réaffecté(s) à l'exemplaire conservé.";
        }

        return $message;
    }
}
