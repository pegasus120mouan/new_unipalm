<table class="table table-striped table-hover" id="table1">
    <thead>
        <tr>
            @if (! empty($showBulkSelection))
                <th style="width: 40px;">
                    <input type="checkbox" class="form-check-input" id="selectAllTickets" title="Tout sélectionner">
                </th>
            @endif
            <th>Date ticket</th>
            <th>N° Ticket</th>
            <th>Usine</th>
            <th>Chargé de mission</th>
            <th>Véhicule</th>
            <th>Poids</th>
            <th>Créé par</th>
            <th>Date ajout</th>
            <th>Prix unitaire</th>
            @if (empty($compactView))
                <th>Validation</th>
            @endif
            <th class="text-center">Vérification</th>
            <th>Montant</th>
            @if (empty($compactView))
                <th>Date paie</th>
                <th>Statut</th>
            @endif
            @if (! empty($showValidateAction))
                <th>Actions</th>
            @endif
            @if (! empty($showEditAction))
                <th>Actions</th>
            @endif
            @if (! empty($showDeleteAction))
                <th class="text-center" style="width: 3rem;">Suppr.</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse ($tickets as $ticket)
            <tr>
                @if (! empty($showBulkSelection))
                    <td>
                        @if ($ticket->hasPrixUnitaire())
                            <span class="text-muted">—</span>
                        @else
                            <input type="checkbox" class="form-check-input ticket-checkbox"
                                name="ticket_ids[]" value="{{ $ticket->id_ticket }}">
                        @endif
                    </td>
                @endif
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
                @if (empty($compactView))
                    <td>
                        @if ($ticket->date_validation_boss)
                            <span class="badge bg-success">{{ $ticket->date_validation_boss->format('d/m/Y') }}</span>
                        @else
                            <span class="badge bg-warning">En cours</span>
                        @endif
                    </td>
                @endif
                <td class="text-center">
                    @if ($ticket->isVerified())
                        <img src="{{ asset('assets/images/icones/verified.png') }}"
                            alt="Vérifié"
                            title="Ticket vérifié{{ $ticket->verifie_par ? ' par '.$ticket->verifie_par : '' }}"
                            width="28"
                            height="28"
                            class="d-inline-block">
                    @else
                        <img src="{{ asset('assets/images/icones/false.png') }}"
                            alt="Non vérifié"
                            title="Ticket non vérifié"
                            width="28"
                            height="28"
                            class="d-inline-block">
                    @endif
                </td>
                <td>
                    @if (blank($ticket->montant_paie))
                        <span class="badge bg-warning">En attente</span>
                    @else
                        {{ number_format((float) $ticket->montant_paie, 0, '', ' ') }}
                    @endif
                </td>
                @if (empty($compactView))
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
                @endif
                @if (! empty($showValidateAction))
                    <td>
                        <button type="button"
                            class="btn btn-sm btn-info validate-ticket-btn"
                            data-ticket-id="{{ $ticket->id_ticket }}"
                            data-ticket-numero="{{ $ticket->numero_ticket }}"
                            data-prix-unitaire="{{ (float) $ticket->prix_unitaire > 0 ? $ticket->prix_unitaire : '' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#validateTicketModal">
                            Valider
                        </button>
                    </td>
                @endif
                @if (! empty($showEditAction))
                    <td>
                        @if ($ticket->isPaid())
                            <span class="badge bg-secondary">Payé</span>
                        @else
                            <button type="button"
                                class="btn btn-sm btn-primary edit-ticket-btn"
                                data-ticket-id="{{ $ticket->id_ticket }}"
                                data-date-ticket="{{ $ticket->date_ticket?->format('Y-m-d') }}"
                                data-numero-ticket="{{ $ticket->numero_ticket }}"
                                data-id-usine="{{ $ticket->id_usine }}"
                                data-usine-name="{{ $ticket->usine?->nom_usine }}"
                                data-id-agent="{{ $ticket->id_agent }}"
                                data-agent-name="{{ $ticket->agent?->full_name }}"
                                data-vehicule-id="{{ $ticket->vehicule_id }}"
                                data-poids="{{ $ticket->poids }}"
                                data-bs-toggle="modal"
                                data-bs-target="#editTicketModal">
                                <i class="bi bi-pencil"></i> Modifier
                            </button>
                        @endif
                    </td>
                @endif
                @if (! empty($showDeleteAction))
                    <td class="text-center">
                        @if ($ticket->isSold())
                            <span class="text-muted" title="Ticket soldé — suppression impossible">
                                <i class="bi bi-trash fs-5 opacity-25"></i>
                            </span>
                        @else
                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0"
                                    title="Supprimer le ticket"
                                    onclick="return confirm(@json('Supprimer le ticket « '.($ticket->numero_ticket ?? '').' » ?'));">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                @endif
            </tr>
        @empty
            @php
                $columnCount = 11
                    + (empty($compactView) ? 3 : 0)
                    + (! empty($showValidateAction) ? 1 : 0)
                    + (! empty($showEditAction) ? 1 : 0)
                    + (! empty($showDeleteAction) ? 1 : 0)
                    + (! empty($showBulkSelection) ? 1 : 0);
            @endphp
            <tr>
                <td colspan="{{ $columnCount }}" class="text-center text-muted py-4">
                    {{ $emptyMessage ?? 'Aucun ticket trouvé.' }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center">
    {{ $tickets->links() }}
</div>
