<table class="table table-bordered table-striped table-hover">
    <thead class="table-secondary">
        <tr>
            <th>Date réception</th>
            <th>Date ticket</th>
            <th>N° Ticket</th>
            <th>Usine</th>
            <th>Poids</th>
            <th>Prix unitaire</th>
            <th>Nom agent</th>
            <th>Pont</th>
            <th>Véhicule</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($tickets as $ticket)
            <tr>
                <td>{{ $ticket->created_at?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $ticket->numero_ticket ?? '-' }}</td>
                <td>{{ $ticket->usine?->nom_usine ?? '-' }}</td>
                <td>{{ $ticket->poids ? number_format($ticket->poids, 0, '', ' ') : '-' }}</td>
                <td>
                    @if (blank($ticket->prix_unitaire) || (float) $ticket->prix_unitaire == 0)
                        <span class="badge bg-danger w-100 py-2">En attente de validation</span>
                    @else
                        <span class="badge bg-secondary w-100 py-2">
                            {{ number_format((float) $ticket->prix_unitaire, 2, '.', '') }}
                        </span>
                    @endif
                </td>
                <td>{{ $ticket->agent?->full_name ?? '-' }}</td>
                <td>{{ $ticket->pont?->nom_pont ?? '—' }}</td>
                <td>{{ $ticket->vehicule?->matricule_vehicule ?? '-' }}</td>
                <td>
                    <button type="button"
                        class="btn btn-sm btn-info view-ticket-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#viewTicketModal"
                        data-numero="{{ $ticket->numero_ticket }}"
                        data-date-reception="{{ $ticket->created_at?->format('d/m/Y') }}"
                        data-date-ticket="{{ $ticket->date_ticket?->format('d/m/Y') }}"
                        data-usine="{{ $ticket->usine?->nom_usine }}"
                        data-poids="{{ $ticket->poids ? number_format($ticket->poids, 0, '', ' ') : '-' }}"
                        data-prix="{{ blank($ticket->prix_unitaire) || (float) $ticket->prix_unitaire == 0 ? 'En attente de validation' : number_format((float) $ticket->prix_unitaire, 2, '.', '') }}"
                        data-agent="{{ $ticket->agent?->full_name }}"
                        data-pont="{{ $ticket->pont?->nom_pont }}"
                        data-vehicule="{{ $ticket->vehicule?->matricule_vehicule }}"
                        data-createur="{{ $ticket->utilisateur?->full_name }}"
                        title="Voir les détails">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    {{ $emptyMessage ?? 'Aucun ticket trouvé.' }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center">
    {{ $tickets->links() }}
</div>
