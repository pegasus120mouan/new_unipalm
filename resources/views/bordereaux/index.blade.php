@extends('layout.main')

@section('title', 'Bordereaux')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Bordereaux</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bordereaux</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if ($errors->has('bordereau'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('bordereau') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBordereauModal">
                            <i class="bi bi-plus-circle"></i> Générer un bordereau
                        </button>
                        <button type="button" class="btn btn-danger" disabled title="Bientôt disponible">
                            <i class="bi bi-printer-fill"></i> Imprimer
                        </button>
                        <a href="#bordereau-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher
                        </a>
                        <button type="button" class="btn btn-warning" disabled title="Bientôt disponible">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i> Exporter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="bordereau-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres de recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('bordereaux.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="numero" class="form-label">N° bordereau</label>
                            <input type="text" name="numero" id="numero" class="form-control"
                                placeholder="Numéro de bordereau" value="{{ $filters['numero'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="agent_id" class="form-label">Agent</label>
                            <select name="agent_id" id="agent_id" class="form-select">
                                <option value="">Tous les agents</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id_agent }}" @selected(($filters['agent_id'] ?? '') == $agent->id_agent)>
                                        {{ $agent->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="usine_id" class="form-label">Usine</label>
                            <select name="usine_id" id="usine_id" class="form-select">
                                <option value="">Toutes les usines</option>
                                @foreach ($usines as $usine)
                                    <option value="{{ $usine->id_usine }}" @selected(($filters['usine_id'] ?? '') == $usine->id_usine)>
                                        {{ $usine->nom_usine }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="numero_ticket" class="form-label">N° ticket</label>
                            <input type="text" name="numero_ticket" id="numero_ticket" class="form-control"
                                placeholder="Numéro de ticket" value="{{ $filters['numero_ticket'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="date_debut" class="form-label">Date début (depuis)</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control"
                                value="{{ $filters['date_debut'] ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="date_fin" class="form-label">Date fin (jusqu'au)</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control"
                                value="{{ $filters['date_fin'] ?? '' }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('bordereaux.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des bordereaux</span>
                    <span class="text-muted">{{ $bordereaux->total() }} bordereau(x)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date génération</th>
                                <th>Numéro</th>
                                <th class="text-center">Tickets</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th class="text-end">Poids total</th>
                                <th class="text-end">Montant total</th>
                                <th class="text-end">Montant payé</th>
                                <th class="text-end">Reste à payer</th>
                                <th>Statut</th>
                                <th>Agent</th>
                                <th>Validation</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bordereaux as $bordereau)
                                <tr>
                                    <td>{{ $bordereau->created_at?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('bordereaux.pdf', $bordereau->numero_bordereau) }}"
                                            target="_blank" rel="noopener noreferrer"
                                            class="fw-semibold text-primary text-decoration-none"
                                            title="Ouvrir le PDF du bordereau">
                                            {{ $bordereau->numero_bordereau }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $bordereau->tickets_count }}</span>
                                    </td>
                                    <td>{{ $bordereau->date_debut?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $bordereau->date_fin?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-end">
                                        {{ number_format((float) $bordereau->poids_total, 2, '.', ' ') }} kg
                                    </td>
                                    <td class="text-end">
                                        {{ number_format((float) $bordereau->montant_total, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end">
                                        {{ number_format((float) ($bordereau->montant_payer ?? 0), 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end">
                                        {{ number_format((float) ($bordereau->montant_reste ?? $bordereau->montant_total), 0, '', ' ') }} FCFA
                                    </td>
                                    <td>
                                        @if (strtolower((string) $bordereau->statut_bordereau) === 'soldé')
                                            <span class="badge bg-success">Soldé</span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                {{ $bordereau->statut_bordereau ?? 'Non soldé' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $bordereau->agent?->full_name ?? '-' }}</td>
                                    <td>
                                        @if ($bordereau->isValidated())
                                            <button type="button" class="btn btn-sm btn-success" disabled>
                                                <i class="bi bi-check-lg"></i> Validé
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('bordereaux.validate', $bordereau) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Cliquer pour valider">
                                                    En attente
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (! $bordereau->isValidated() && (float) ($bordereau->montant_payer ?? 0) <= 0)
                                            <button type="button"
                                                class="btn btn-sm btn-danger btn-delete-bordereau"
                                                title="Supprimer"
                                                data-action="{{ route('bordereaux.destroy', $bordereau) }}"
                                                data-numero="{{ $bordereau->numero_bordereau }}"
                                                data-agent="{{ $bordereau->agent?->full_name ?? '-' }}"
                                                data-tickets="{{ $bordereau->tickets_count }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-danger disabled"
                                                title="Suppression impossible (bordereau validé ou payé)"
                                                disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-muted py-4">
                                        Aucun bordereau trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $bordereaux->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addBordereauModal" tabindex="-1" aria-labelledby="addBordereauModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('bordereaux.preview') }}" id="bordereauForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBordereauModalLabel">Nouveau bordereau</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Sélectionnez l'agent et la période (<strong>date ticket</strong>), puis cliquez sur
                            <strong>Voir</strong> pour afficher les tickets validés sans bordereau.
                        </p>

                        <div class="mb-3">
                            <label for="agent_search" class="form-label">Chargé de mission</label>
                            <div class="position-relative">
                                <input type="text" id="agent_search" class="form-control @error('id_agent') is-invalid @enderror"
                                    placeholder="Tapez le nom du chargé de mission..." autocomplete="off">
                                <input type="hidden" name="id_agent" id="id_agent" value="{{ old('id_agent') }}" required>
                                <div id="agent_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                    style="z-index: 1050; display: none; max-height: 220px; overflow-y: auto;"></div>
                            </div>
                            <div id="agent_found" class="alert alert-info py-2 px-3 mt-2 mb-0" style="display: none;">
                                <span id="agent_found_name"></span>
                                <button type="button" class="btn btn-sm btn-primary ms-2" id="agent_found_select">Sélectionner</button>
                            </div>
                            @error('id_agent')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="bordereau_date_debut" class="form-label">Date de début</label>
                            <input type="date" name="date_debut" id="bordereau_date_debut"
                                class="form-control @error('date_debut') is-invalid @enderror"
                                value="{{ old('date_debut') }}" required>
                            @error('date_debut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="bordereau_date_fin" class="form-label">Date de fin</label>
                            <input type="date" name="date_fin" id="bordereau_date_fin"
                                class="form-control @error('date_fin') is-invalid @enderror"
                                value="{{ old('date_fin') }}" required>
                            @error('date_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Voir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($previewTickets->isNotEmpty() && is_array($previewCriteria))
        <div class="modal fade" id="selectTicketsBordereauModal" tabindex="-1"
            aria-labelledby="selectTicketsBordereauModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('bordereaux.store') }}" id="validateBordereauForm">
                        @csrf
                        <input type="hidden" name="id_agent" value="{{ $previewCriteria['id_agent'] }}">
                        <input type="hidden" name="date_debut" value="{{ $previewCriteria['date_debut'] }}">
                        <input type="hidden" name="date_fin" value="{{ $previewCriteria['date_fin'] }}">

                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="selectTicketsBordereauModalLabel">
                                <i class="bi bi-list-check"></i> Sélection des tickets
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <span class="fw-semibold">{{ $previewCriteria['agent_name'] ?? '-' }}</span>
                                    <span class="text-muted">
                                        — du {{ \Illuminate\Support\Carbon::parse($previewCriteria['date_debut'])->format('d/m/Y') }}
                                        au {{ \Illuminate\Support\Carbon::parse($previewCriteria['date_fin'])->format('d/m/Y') }}
                                    </span>
                                </div>
                                <span class="badge bg-secondary">{{ $previewTickets->count() }} ticket(s) disponible(s)</span>
                            </div>

                            @php
                                $selectedTicketIds = old('ticket_ids', $previewCriteria['ticket_ids'] ?? []);
                            @endphp

                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" class="form-check-input" id="selectAllPreviewTickets" checked>
                                            </th>
                                            <th>Date ticket</th>
                                            <th>N° Ticket</th>
                                            <th>Usine</th>
                                            <th>Véhicule</th>
                                            <th class="text-end">Poids</th>
                                            <th class="text-end">Prix unit.</th>
                                            <th class="text-end">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($previewTickets as $ticket)
                                            @php
                                                $montant = (float) $ticket->poids * (float) $ticket->prix_unitaire;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="form-check-input preview-ticket-checkbox"
                                                        name="ticket_ids[]" value="{{ $ticket->id_ticket }}"
                                                        data-poids="{{ $ticket->poids }}"
                                                        data-montant="{{ $montant }}"
                                                        @checked(in_array($ticket->id_ticket, $selectedTicketIds))>
                                                </td>
                                                <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '-' }}</td>
                                                <td class="fw-semibold">{{ $ticket->numero_ticket }}</td>
                                                <td>{{ $ticket->usine?->nom_usine ?? '-' }}</td>
                                                <td>{{ $ticket->vehicule?->matricule_vehicule ?? '-' }}</td>
                                                <td class="text-end">{{ number_format((float) $ticket->poids, 0, '', ' ') }} kg</td>
                                                <td class="text-end">{{ number_format((float) $ticket->prix_unitaire, 0, '', ' ') }}</td>
                                                <td class="text-end">{{ number_format($montant, 0, '', ' ') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-2 mt-3">
                                <div class="col-md-4">
                                    <div class="border rounded p-2 text-center bg-light">
                                        <div class="text-muted small">Tickets sélectionnés</div>
                                        <div class="fw-bold" id="previewSelectedCount">0</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 text-center bg-light">
                                        <div class="text-muted small">Poids total</div>
                                        <div class="fw-bold" id="previewSelectedPoids">0 kg</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 text-center bg-light">
                                        <div class="text-muted small">Montant total</div>
                                        <div class="fw-bold text-danger" id="previewSelectedMontant">0 FCFA</div>
                                    </div>
                                </div>
                            </div>

                            @error('ticket_ids')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Valider le bordereau
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="deleteBordereauModal" tabindex="-1" aria-labelledby="deleteBordereauModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteBordereauModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Vous êtes sur le point de supprimer définitivement le bordereau suivant&nbsp;:
                    </p>
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="row g-2 small">
                            <div class="col-sm-4 text-muted">Numéro</div>
                            <div class="col-sm-8 fw-semibold" id="deleteBordereauNumero">-</div>
                            <div class="col-sm-4 text-muted">Agent</div>
                            <div class="col-sm-8" id="deleteBordereauAgent">-</div>
                            <div class="col-sm-4 text-muted">Tickets liés</div>
                            <div class="col-sm-8" id="deleteBordereauTickets">-</div>
                        </div>
                    </div>
                    <div class="alert alert-warning mb-0 d-flex align-items-start gap-2">
                        <i class="bi bi-info-circle-fill mt-1"></i>
                        <span>Les tickets associés seront libérés et pourront être regroupés dans un nouveau bordereau.</span>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Annuler
                    </button>
                    <form method="POST" id="deleteBordereauForm" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Supprimer le bordereau
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (session('open_tickets_preview_modal') && $previewTickets->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('selectTicketsBordereauModal');
                if (el && typeof bootstrap !== 'undefined') {
                    (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
                }
            });
        </script>
    @elseif ($errors->has('id_agent') || $errors->has('date_debut') || $errors->has('date_fin') || ($errors->has('bordereau') && ! session('open_tickets_preview_modal')))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('addBordereauModal');
                if (el && typeof bootstrap !== 'undefined') {
                    (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
                }
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('addBordereauModal');
            const agents = @json($agentsForAutocomplete);

            const searchInput = document.getElementById('agent_search');
            const hiddenInput = document.getElementById('id_agent');
            const suggestions = document.getElementById('agent_suggestions');
            const foundBox = document.getElementById('agent_found');
            const foundName = document.getElementById('agent_found_name');
            const foundSelect = document.getElementById('agent_found_select');
            let pendingItem = null;

            function clearSelection() {
                hiddenInput.value = '';
                pendingItem = null;
                foundBox.style.display = 'none';
                foundName.textContent = '';
            }

            function selectItem(item) {
                searchInput.value = item.name;
                hiddenInput.value = item.id;
                pendingItem = null;
                foundBox.style.display = 'none';
                suggestions.style.display = 'none';
            }

            function showSuggestions(query) {
                const term = query.trim().toLowerCase();
                suggestions.innerHTML = '';

                if (term.length < 1) {
                    suggestions.style.display = 'none';
                    clearSelection();
                    return;
                }

                const matches = agents.filter(item =>
                    item.name.toLowerCase().includes(term) || item.numero.toLowerCase().includes(term)
                ).slice(0, 10);

                if (matches.length === 0) {
                    suggestions.innerHTML = '<div class="list-group-item text-muted">Aucun agent trouvé</div>';
                    suggestions.style.display = 'block';
                    clearSelection();
                    return;
                }

                matches.forEach(item => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.innerHTML = item.numero
                        ? `<span class="text-muted">${item.numero}</span> — <strong>${item.name}</strong>`
                        : `<strong>${item.name}</strong>`;
                    button.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        selectItem(item);
                    });
                    suggestions.appendChild(button);
                });

                suggestions.style.display = 'block';

                const exact = agents.find(item =>
                    item.name.toLowerCase() === term || item.numero.toLowerCase() === term
                );
                if (exact) {
                    pendingItem = exact;
                    foundName.textContent = exact.name;
                    foundBox.style.display = 'block';
                } else {
                    foundBox.style.display = 'none';
                    pendingItem = null;
                    hiddenInput.value = '';
                }
            }

            searchInput.addEventListener('input', () => {
                if (hiddenInput.value) {
                    hiddenInput.value = '';
                    pendingItem = null;
                    foundBox.style.display = 'none';
                }
                showSuggestions(searchInput.value);
            });

            foundSelect.addEventListener('click', () => {
                if (pendingItem) {
                    selectItem(pendingItem);
                }
            });

            modal.addEventListener('hidden.bs.modal', () => {
                searchInput.value = '';
                clearSelection();
                suggestions.style.display = 'none';
            });

            document.getElementById('bordereauForm').addEventListener('submit', (event) => {
                if (!hiddenInput.value) {
                    event.preventDefault();
                    searchInput.classList.add('is-invalid');
                    searchInput.focus();
                }
            });

            searchInput.addEventListener('focus', () => searchInput.classList.remove('is-invalid'));

            @if (old('id_agent'))
                const selected = agents.find(item => String(item.id) === String(@json(old('id_agent'))));
                if (selected) {
                    selectItem(selected);
                }
            @endif
        });
    </script>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('selectAllPreviewTickets');
            const checkboxes = document.querySelectorAll('.preview-ticket-checkbox');
            const countEl = document.getElementById('previewSelectedCount');
            const poidsEl = document.getElementById('previewSelectedPoids');
            const montantEl = document.getElementById('previewSelectedMontant');
            const validateForm = document.getElementById('validateBordereauForm');

            function formatNumber(value) {
                return new Intl.NumberFormat('fr-FR').format(Math.round(value));
            }

            function updatePreviewTotals() {
                if (! countEl || ! poidsEl || ! montantEl) {
                    return;
                }

                let count = 0;
                let poids = 0;
                let montant = 0;

                checkboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        count++;
                        poids += parseFloat(checkbox.dataset.poids || '0');
                        montant += parseFloat(checkbox.dataset.montant || '0');
                    }
                });

                countEl.textContent = count;
                poidsEl.textContent = formatNumber(poids) + ' kg';
                montantEl.textContent = formatNumber(montant) + ' FCFA';

                if (selectAll) {
                    selectAll.checked = count > 0 && count === checkboxes.length;
                    selectAll.indeterminate = count > 0 && count < checkboxes.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                    updatePreviewTotals();
                });
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updatePreviewTotals);
            });

            if (validateForm) {
                validateForm.addEventListener('submit', function (event) {
                    const selected = Array.from(checkboxes).some(function (checkbox) {
                        return checkbox.checked;
                    });

                    if (! selected) {
                        event.preventDefault();
                        alert('Sélectionnez au moins un ticket pour créer le bordereau.');
                    }
                });
            }

            updatePreviewTotals();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteModalEl = document.getElementById('deleteBordereauModal');
            const deleteForm = document.getElementById('deleteBordereauForm');

            if (! deleteModalEl || ! deleteForm || typeof bootstrap === 'undefined') {
                return;
            }

            const deleteModal = bootstrap.Modal.getInstance(deleteModalEl) || new bootstrap.Modal(deleteModalEl);

            document.querySelectorAll('.btn-delete-bordereau').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteForm.action = button.getAttribute('data-action') || '';
                    document.getElementById('deleteBordereauNumero').textContent =
                        button.getAttribute('data-numero') || '-';
                    document.getElementById('deleteBordereauAgent').textContent =
                        button.getAttribute('data-agent') || '-';

                    const tickets = button.getAttribute('data-tickets') || '0';
                    document.getElementById('deleteBordereauTickets').textContent =
                        tickets + ' ticket(s)';

                    deleteModal.show();
                });
            });
        });
    </script>
    @endpush
@endsection
