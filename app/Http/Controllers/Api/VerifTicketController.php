<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VerifTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $numero = trim((string) $request->query('numero', ''));
        $idUsine = $request->filled('id_usine') ? (int) $request->query('id_usine') : null;

        if ($numero !== '') {
            $ticket = $this->findTicketByNumero($numero, $idUsine);

            return response()->json([
                'tickets' => $ticket ? [$this->formatTicket($ticket)] : [],
                'pagination' => [
                    'total' => $ticket ? 1 : 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 1,
                ],
            ]);
        }

        $perPage = max(1, min(100, (int) config('verif.tickets_per_page', 50)));

        $query = Ticket::query()
            ->with(['usine', 'agent', 'vehicule', 'utilisateur'])
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket');

        if ($idUsine !== null && $idUsine > 0) {
            $query->where('id_usine', $idUsine);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->query('page', 1)));

        $tickets = $paginator->getCollection()
            ->map(fn (Ticket $ticket) => $this->formatTicket($ticket))
            ->values()
            ->all();

        return response()->json([
            'tickets' => $tickets,
            'pagination' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function markVerified(Request $request, int $id_ticket): JsonResponse
    {
        $ticket = Ticket::query()
            ->with(['usine', 'agent', 'vehicule', 'utilisateur'])
            ->find($id_ticket);

        if ($ticket === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Ticket introuvable.',
            ], 404);
        }

        $verifiePar = $this->resolveVerifiePar($request);
        $wasAlreadyVerified = (bool) $ticket->verification;

        if (! $ticket->verification) {
            $ticket->verification = true;
            $ticket->date_verification = now();
        }

        if ($verifiePar !== null) {
            $ticket->verifie_par = $verifiePar;
        }

        if ($ticket->isDirty()) {
            $ticket->save();
        }

        return response()->json([
            'ok' => true,
            'message' => $wasAlreadyVerified
                ? 'Ticket déjà vérifié.'
                : 'Ticket vérifié.',
            'ticket' => $this->formatTicket($ticket->fresh(['usine', 'agent', 'vehicule', 'utilisateur'])),
        ]);
    }

    private function findTicketByNumero(string $numero, ?int $idUsine): ?Ticket
    {
        $needle = $this->normalizeNumero($numero);
        $compactNeedle = str_replace(' ', '', $needle);

        if ($compactNeedle === '') {
            return null;
        }

        $query = Ticket::query()
            ->with(['usine', 'agent', 'vehicule', 'utilisateur']);

        if ($idUsine !== null && $idUsine > 0) {
            $query->where('id_usine', $idUsine);
        }

        $candidates = $query
            ->where(function (Builder $builder) use ($numero, $needle, $compactNeedle) {
                $builder->where('numero_ticket', $numero)
                    ->orWhereRaw('LOWER(REPLACE(numero_ticket, " ", "")) = ?', [$compactNeedle])
                    ->orWhereRaw('LOWER(numero_ticket) = ?', [$needle]);
            })
            ->limit(5)
            ->get();

        foreach ($candidates as $ticket) {
            if ($this->numeroMatches($ticket->numero_ticket, $needle)) {
                return $ticket;
            }
        }

        return null;
    }

    private function numeroMatches(?string $raw, string $needle): bool
    {
        $norm = $this->normalizeNumero((string) $raw);

        if ($norm === $needle) {
            return true;
        }

        return str_replace(' ', '', $norm) === str_replace(' ', '', $needle);
    }

    private function normalizeNumero(string $value): string
    {
        $value = trim($value);

        return Str::lower(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function formatTicket(Ticket $ticket): array
    {
        $agent = $ticket->agent;
        $utilisateur = $ticket->utilisateur;

        return [
            'id_ticket' => $ticket->id_ticket,
            'numero_ticket' => $ticket->numero_ticket,
            'id_usine' => $ticket->id_usine,
            'nom_usine' => $ticket->usine?->nom_usine,
            'date_ticket' => $ticket->date_ticket?->format('Y-m-d'),
            'id_agent' => $ticket->id_agent,
            'agent_nom' => $agent?->nom,
            'agent_prenom' => $agent?->prenom,
            'vehicule_id' => $ticket->vehicule_id,
            'matricule_vehicule' => $ticket->vehicule?->matricule_vehicule,
            'type_vehicule' => $ticket->vehicule?->type_vehicule,
            'poids' => $ticket->poids,
            'prix_unitaire' => $ticket->prix_unitaire,
            'montant_paie' => $ticket->montant_paie,
            'montant_payer' => $ticket->montant_payer,
            'montant_reste' => $ticket->montant_reste,
            'date_validation_boss' => $ticket->date_validation_boss?->toIso8601String(),
            'date_paie' => $ticket->date_paie?->toIso8601String(),
            'statut_ticket' => $ticket->statut_ticket,
            'numero_bordereau' => $ticket->numero_bordereau,
            'verification' => (bool) $ticket->verification,
            'date_verification' => $ticket->date_verification?->toIso8601String(),
            'verifie_par' => $ticket->verifie_par,
            'id_utilisateur' => $ticket->id_utilisateur,
            'utilisateur_nom' => $utilisateur?->nom,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    private function resolveVerifiePar(Request $request): ?string
    {
        $explicit = trim((string) $request->input('verifie_par', ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $nom = trim((string) $request->input('verifie_par_nom', ''));
        $prenoms = trim((string) $request->input('verifie_par_prenoms', ''));
        $built = trim($nom.' '.$prenoms);

        return $built !== '' ? $built : null;
    }
}
