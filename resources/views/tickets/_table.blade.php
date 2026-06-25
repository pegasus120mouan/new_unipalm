<table class="table table-striped table-hover" id="table1">
    <thead>
        <tr>
            <th>Date ticket</th>
            <th>N° Ticket</th>
            <th>Usine</th>
            <th>Chargé de mission</th>
            <th>Véhicule</th>
            <th>Poids</th>
            <th>Créé par</th>
            <th>Date ajout</th>
            <th>Prix unitaire</th>
            <th>Validation</th>
            <th>Montant</th>
            <th>Date paie</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($tickets as $ticket)
            <tr>
                <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $ticket->numero_ticket ?? '-' }}</td>
                <td>{{ $ticket->usine?->nom_usine ?? '-' }}</td>
                <td>{{ $ticket->agent?->full_name ?? '-' }}</td>
                <td>{{ $ticket->vehicule?->matricule_vehicule ?? '-' }}</td>
                <td>{{ $ticket->poids ? number_format($ticket->poids, 0, '', ' ') : '-' }}</td>
                <td>{{ $ticket->utilisateur?->full_name ?? '-' }}</td>
                <td>{{ $ticket->created_at?->format('d/m/Y') ?? '-' }}</td>
                <td>
                    @if (blank($ticket->prix_unitaire) || (float) $ticket->prix_unitaire == 0)
                        <span class="badge bg-warning">En attente</span>
                    @else
                        {{ number_format((float) $ticket->prix_unitaire, 0, '', ' ') }}
                    @endif
                </td>
                <td>
                    @if ($ticket->date_validation_boss)
                        <span class="badge bg-success">{{ $ticket->date_validation_boss->format('d/m/Y') }}</span>
                    @else
                        <span class="badge bg-warning">En cours</span>
                    @endif
                </td>
                <td>
                    @if (blank($ticket->montant_paie))
                        <span class="badge bg-warning">En attente</span>
                    @else
                        {{ number_format((float) $ticket->montant_paie, 0, '', ' ') }}
                    @endif
                </td>
                <td>
                    @if ($ticket->date_paie)
                        <span class="badge bg-success">{{ $ticket->date_paie->format('d/m/Y') }}</span>
                    @else
                        <span class="badge bg-danger">Non payé</span>
                    @endif
                </td>
                <td>
                    @if ($ticket->statut_ticket === 'soldé')
                        <span class="badge bg-success">Soldé</span>
                    @else
                        <span class="badge bg-secondary">Non soldé</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="13" class="text-center text-muted py-4">
                    {{ $emptyMessage ?? 'Aucun ticket trouvé.' }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center">
    {{ $tickets->links() }}
</div>
