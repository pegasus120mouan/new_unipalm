@extends('layout.main')

@section('title', 'Modifications de tickets')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Modifications de tickets</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifications de tickets</li>
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @include('tickets.partials.search-filters', [
        'searchAction' => route('tickets.modifications'),
        'resetAction' => route('tickets.modifications'),
        'showNumeroTicketFilter' => true,
    ])

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span>Liste des tickets</span>
                        <span class="text-muted ms-2">(modification impossible si le ticket est payé)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">{{ $tickets->total() }} ticket(s)</span>
                        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Tous les tickets
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    @include('tickets.partials.table', [
                        'emptyMessage' => 'Aucun ticket trouvé.',
                        'showEditAction' => true,
                    ])
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="editTicketModal" tabindex="-1" aria-labelledby="editTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTicketModalLabel">Modifier le ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="" id="editTicketForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_date_ticket" class="form-label">Date ticket</label>
                                <input type="date" name="date_ticket" id="edit_date_ticket" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_numero_ticket" class="form-label">N° Ticket</label>
                                <input type="text" name="numero_ticket" id="edit_numero_ticket" class="form-control" required>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label for="edit_usine_search" class="form-label">Usine</label>
                                <input type="text" id="edit_usine_search" class="form-control"
                                    placeholder="Rechercher une usine..." autocomplete="off">
                                <input type="hidden" name="id_usine" id="edit_id_usine" required>
                                <div id="edit_usine_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                    style="z-index: 1060; display: none; max-height: 200px; overflow-y: auto;"></div>
                                <div id="edit_usine_found" class="form-text mt-1" style="display: none;">
                                    Usine trouvée :
                                    <button type="button" id="edit_usine_found_select"
                                        class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                        <span id="edit_usine_found_name"></span>
                                    </button>
                                    <span class="text-muted">— cliquer pour sélectionner</span>
                                </div>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label for="edit_agent_search" class="form-label">Chargé de mission</label>
                                <input type="text" id="edit_agent_search" class="form-control"
                                    placeholder="Rechercher par N° agent..." autocomplete="off">
                                <input type="hidden" name="id_agent" id="edit_id_agent" required>
                                <div id="edit_agent_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                    style="z-index: 1060; display: none; max-height: 200px; overflow-y: auto;"></div>
                                <div id="edit_agent_found" class="form-text mt-1" style="display: none;">
                                    Agent trouvé :
                                    <button type="button" id="edit_agent_found_select"
                                        class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                        <span id="edit_agent_found_name"></span>
                                    </button>
                                    <span class="text-muted">— cliquer pour sélectionner</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_vehicule_id" class="form-label">Véhicule</label>
                                <select name="vehicule_id" id="edit_vehicule_id" class="form-select" required>
                                    <option value="">Sélectionner un véhicule</option>
                                    @foreach ($vehicules as $vehicule)
                                        <option value="{{ $vehicule->vehicules_id }}">{{ $vehicule->matricule_vehicule }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_poids" class="form-label">Poids</label>
                                <input type="number" name="poids" id="edit_poids" class="form-control"
                                    step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('editTicketForm');
            const modal = document.getElementById('editTicketModal');
            const updateBaseUrl = @json(url('/tickets'));

            function setupAutocomplete(config) {
                const searchInput = document.getElementById(config.searchId);
                const hiddenInput = document.getElementById(config.hiddenId);
                const suggestions = document.getElementById(config.suggestionsId);
                const foundBox = document.getElementById(config.foundId);
                const foundName = document.getElementById(config.foundNameId);
                const foundSelect = document.getElementById(config.foundSelectId);
                let pendingItem = null;

                function clearSelection() {
                    hiddenInput.value = '';
                    pendingItem = null;
                    foundBox.style.display = 'none';
                    foundName.textContent = '';
                }

                function selectItem(item) {
                    searchInput.value = item[config.labelKey];
                    hiddenInput.value = item.id;
                    pendingItem = null;
                    foundBox.style.display = 'none';
                    foundName.textContent = '';
                    suggestions.style.display = 'none';
                }

                function setSelection(id, label) {
                    hiddenInput.value = id;
                    searchInput.value = label;
                    pendingItem = null;
                    foundBox.style.display = 'none';
                    suggestions.style.display = 'none';
                }

                function showPendingItem(item) {
                    pendingItem = item;
                    foundName.textContent = item[config.labelKey];
                    foundBox.style.display = 'block';
                }

                function showSuggestions(query) {
                    const term = query.trim().toLowerCase();
                    suggestions.innerHTML = '';

                    if (term.length < 1) {
                        suggestions.style.display = 'none';
                        clearSelection();
                        return;
                    }

                    const matches = config.items.filter(item =>
                        config.filter(item, term)
                    ).slice(0, 10);

                    if (matches.length === 0) {
                        suggestions.innerHTML = `<div class="list-group-item text-muted">${config.emptyText}</div>`;
                        suggestions.style.display = 'block';
                        clearSelection();
                        return;
                    }

                    matches.forEach(item => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action';
                        button.innerHTML = config.renderItem(item);
                        button.addEventListener('mousedown', (event) => {
                            event.preventDefault();
                            selectItem(item);
                        });
                        suggestions.appendChild(button);
                    });

                    suggestions.style.display = 'block';

                    const exact = config.items.find(item => config.isExact(item, term));
                    if (exact) {
                        showPendingItem(exact);
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

                searchInput.addEventListener('focus', () => {
                    const term = searchInput.value.trim();
                    if (term && !hiddenInput.value) {
                        showSuggestions(term);
                    }
                });

                foundSelect.addEventListener('click', () => {
                    if (pendingItem) {
                        selectItem(pendingItem);
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!searchInput.contains(event.target)
                        && !suggestions.contains(event.target)
                        && !foundBox.contains(event.target)) {
                        suggestions.style.display = 'none';
                    }
                });

                return { clearSelection, setSelection };
            }

            const usineAutocomplete = setupAutocomplete({
                items: @json($usinesForAutocomplete),
                searchId: 'edit_usine_search',
                hiddenId: 'edit_id_usine',
                suggestionsId: 'edit_usine_suggestions',
                foundId: 'edit_usine_found',
                foundNameId: 'edit_usine_found_name',
                foundSelectId: 'edit_usine_found_select',
                labelKey: 'label',
                emptyText: 'Aucune usine trouvée',
                filter: (item, term) => item.label.toLowerCase().includes(term),
                isExact: (item, term) => item.label.toLowerCase() === term,
                renderItem: (item) => `<strong>${item.label}</strong>`,
            });

            const agentAutocomplete = setupAutocomplete({
                items: @json($agentsForAutocomplete),
                searchId: 'edit_agent_search',
                hiddenId: 'edit_id_agent',
                suggestionsId: 'edit_agent_suggestions',
                foundId: 'edit_agent_found',
                foundNameId: 'edit_agent_found_name',
                foundSelectId: 'edit_agent_found_select',
                labelKey: 'name',
                emptyText: 'Aucun agent trouvé',
                filter: (item, term) => item.numero.toLowerCase().includes(term),
                isExact: (item, term) => item.numero.toLowerCase() === term,
                renderItem: (item) => `<span class="text-muted">${item.numero}</span> — <strong>${item.name}</strong>`,
            });

            document.querySelectorAll('.edit-ticket-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = `${updateBaseUrl}/${button.dataset.ticketId}`;
                    document.getElementById('edit_date_ticket').value = button.dataset.dateTicket || '';
                    document.getElementById('edit_numero_ticket').value = button.dataset.numeroTicket || '';
                    document.getElementById('edit_vehicule_id').value = button.dataset.vehiculeId || '';
                    document.getElementById('edit_poids').value = button.dataset.poids || '';

                    usineAutocomplete.setSelection(
                        button.dataset.idUsine || '',
                        button.dataset.usineName || ''
                    );
                    agentAutocomplete.setSelection(
                        button.dataset.idAgent || '',
                        button.dataset.agentName || ''
                    );
                });
            });

            modal.addEventListener('hidden.bs.modal', () => {
                usineAutocomplete.clearSelection();
                agentAutocomplete.clearSelection();
                document.getElementById('edit_usine_search').value = '';
                document.getElementById('edit_agent_search').value = '';
            });
        });
    </script>
@endsection
