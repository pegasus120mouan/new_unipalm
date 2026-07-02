<table class="table table-striped table-hover" id="table-modifications">
    <thead>
        <tr>
            <th>Date ticket</th>
            <th>N° Ticket</th>
            <th>Usine</th>
            <th>Chargé de mission</th>
            <th>Pont</th>
            <th>Véhicule</th>
            <th>Poids</th>
            <th>Créé par</th>
            <th>Date ajout</th>
            <th>Prix unitaire</th>
            <th>Validation</th>
            <th class="text-center">Vérification</th>
            <th>Montant</th>
            <th>Date paie</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($tickets as $ticket)
            @php
                $canEdit = ! $ticket->isPaid();
                $prixRaw = $ticket->hasPrixUnitaire() ? (float) $ticket->prix_unitaire : '';
            @endphp
            <tr @class([
                    'ticket-mod-row',
                    'ticket-row-readonly' => ! $canEdit,
                ])
                data-ticket-id="{{ $ticket->id_ticket }}"
                data-numero-ticket="{{ $ticket->numero_ticket }}"
                data-editable="{{ $canEdit ? '1' : '0' }}"
                @if (! $canEdit)
                    title="Ticket payé — modification impossible"
                @endif>
                <td @class(['ticket-editable' => $canEdit, 'ticket-editable-cell' => $canEdit])
                    @if ($canEdit)
                        data-field="date_ticket"
                        data-value="{{ $ticket->date_ticket?->format('Y-m-d') ?? '' }}"
                        title="Cliquer pour modifier — Entrée pour enregistrer"
                    @endif>
                    <span class="ticket-cell-display">{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</span>
                </td>
                <td class="fw-semibold">{{ $ticket->numero_ticket ?? '—' }}</td>
                <td @class(['ticket-editable' => $canEdit, 'ticket-editable-cell' => $canEdit])
                    @if ($canEdit)
                        data-field="id_usine"
                        data-value="{{ $ticket->id_usine }}"
                        data-label="{{ $ticket->usine?->nom_usine ?? '' }}"
                        title="Cliquer pour modifier — Entrée pour enregistrer"
                    @endif>
                    <span class="ticket-cell-display">{{ $ticket->usine?->nom_usine ?? '—' }}</span>
                </td>
                <td @class(['ticket-editable' => $canEdit, 'ticket-editable-cell' => $canEdit])
                    @if ($canEdit)
                        data-field="id_agent"
                        data-value="{{ $ticket->id_agent }}"
                        data-label="{{ $ticket->agent?->full_name ?? '' }}"
                        title="Cliquer pour modifier — Entrée pour enregistrer"
                    @endif>
                    <span class="ticket-cell-display">{{ $ticket->agent?->full_name ?? '—' }}</span>
                </td>
                <td>{{ $ticket->pont?->nom_pont ?? '—' }}</td>
                <td @class(['ticket-editable' => $canEdit, 'ticket-editable-cell' => $canEdit])
                    @if ($canEdit)
                        data-field="vehicule_id"
                        data-value="{{ $ticket->vehicule_id }}"
                        data-label="{{ $ticket->vehicule?->matricule_vehicule ?? '' }}"
                        title="Cliquer pour modifier — Entrée pour enregistrer"
                    @endif>
                    <span class="ticket-cell-display">{{ $ticket->vehicule?->matricule_vehicule ?? '—' }}</span>
                </td>
                <td @class(['ticket-editable' => $canEdit, 'ticket-editable-cell' => $canEdit])
                    @if ($canEdit)
                        data-field="poids"
                        data-value="{{ $ticket->poids ?? '' }}"
                        title="Cliquer pour modifier — Entrée pour enregistrer"
                    @endif>
                    <span class="ticket-cell-display">{{ $ticket->poids ? number_format($ticket->poids, 0, '', ' ') : '—' }}</span>
                </td>
                <td>{{ $ticket->utilisateur?->full_name ?? '—' }}</td>
                <td @class(['ticket-editable' => $canEdit, 'ticket-editable-cell' => $canEdit])
                    @if ($canEdit)
                        data-field="created_at"
                        data-value="{{ $ticket->created_at?->format('Y-m-d') ?? '' }}"
                        title="Cliquer pour modifier — Entrée pour enregistrer"
                    @endif>
                    <span class="ticket-cell-display">{{ $ticket->created_at?->format('d/m/Y') ?? '—' }}</span>
                </td>
                <td @class(['ticket-editable' => $canEdit, 'ticket-editable-cell' => $canEdit])
                    @if ($canEdit)
                        data-field="prix_unitaire"
                        data-value="{{ $prixRaw }}"
                        title="Cliquer pour modifier — Entrée pour enregistrer"
                    @endif>
                    <span class="ticket-cell-display">
                        @if (blank($ticket->prix_unitaire) || (float) $ticket->prix_unitaire == 0)
                            <span class="badge bg-warning ticket-prix-badge">En attente</span>
                        @else
                            {{ number_format((float) $ticket->prix_unitaire, 0, '', ' ') }}
                        @endif
                    </span>
                </td>
                <td>
                    @if ($ticket->date_validation_boss)
                        <span class="badge bg-success">{{ $ticket->date_validation_boss->format('d/m/Y') }}</span>
                    @else
                        <span class="badge bg-warning">En cours</span>
                    @endif
                </td>
                <td class="text-center">
                    @if ($ticket->isVerified())
                        <img src="{{ asset('assets/images/icones/verified.png') }}" alt="Vérifié" width="28" height="28">
                    @else
                        <img src="{{ asset('assets/images/icones/false.png') }}" alt="Non vérifié" width="28" height="28">
                    @endif
                </td>
                <td class="ticket-montant-cell">
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
                    @if ($ticket->isPaid())
                        <span class="badge bg-secondary">Payé</span>
                    @elseif ($ticket->statut_ticket === 'soldé')
                        <span class="badge bg-success">Soldé</span>
                    @else
                        <span class="badge bg-secondary">Non soldé</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="15" class="text-center text-muted py-4">
                    {{ $emptyMessage ?? 'Aucun ticket trouvé.' }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center">
    {{ $tickets->links() }}
</div>
