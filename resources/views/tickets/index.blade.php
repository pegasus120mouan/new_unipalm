@extends('layout.main')

@section('title', 'Tableau de bord')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Tableau de bord</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ auth()->user()->role }}</li>
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

    @if ($errors->has('ticket'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('ticket') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2 justify-content-between">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTicketModal">
                                <i class="bi bi-plus-lg"></i> Enregistrer un ticket
                            </button>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#searchTicketModal">
                                <i class="bi bi-search"></i> Rechercher un ticket
                            </button>
                            <button type="button" class="btn btn-info" disabled title="Bientôt disponible">
                                <i class="bi bi-file-pdf"></i> Imprimer par usine
                            </button>
                            <button type="button" class="btn btn-warning" disabled title="Bientôt disponible">
                                <i class="bi bi-printer"></i> Bordereau
                            </button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-dark" disabled title="Bientôt disponible">
                                <i class="bi bi-download"></i> Exporter tous
                            </button>
                            <button type="button" class="btn btn-outline-primary" disabled title="Bientôt disponible">
                                <i class="bi bi-calendar3"></i> Exporter période
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            @if (! empty($isSearchRequested))
                <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <span>
                        <i class="bi bi-funnel"></i>
                        Résultats de recherche — {{ $tickets->total() }} ticket(s) trouvé(s)
                    </span>
                    <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser la recherche
                    </a>
                </div>
            @endif
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ ! empty($isSearchRequested) ? 'Résultats' : 'Liste des tickets' }}</span>
                    <span class="text-muted">{{ $tickets->total() }} ticket(s)</span>
                </div>
                <div class="card-body table-responsive">
                    @include('tickets.partials.table', [
                        'showDeleteAction' => auth()->user()->canAccessModule('tickets.destroy'),
                    ])
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addTicketModal" tabindex="-1" aria-labelledby="addTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTicketModalLabel">Enregistrer un ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="date_ticket" class="form-label">Date ticket</label>
                            <input type="date" name="date_ticket" id="date_ticket"
                                class="form-control @error('date_ticket') is-invalid @enderror"
                                value="{{ old('date_ticket') }}" required>
                            @error('date_ticket')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="numero_ticket" class="form-label">Numéro du ticket</label>
                            <input type="text" name="numero_ticket" id="numero_ticket"
                                class="form-control @error('numero_ticket') is-invalid @enderror"
                                value="{{ old('numero_ticket') }}" placeholder="Numéro du ticket" required>
                            @error('numero_ticket')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 position-relative">
                            <label for="usine_search" class="form-label">Sélection usine</label>
                            <input type="text" id="usine_search"
                                class="form-control @error('id_usine') is-invalid @enderror"
                                placeholder="Rechercher une usine..."
                                autocomplete="off"
                                value="{{ $selectedUsine?->nom_usine }}">
                            <input type="hidden" name="id_usine" id="id_usine"
                                value="{{ old('id_usine') }}" required>
                            <div id="usine_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1056; display: none; max-height: 200px; overflow-y: auto;"></div>
                            <div id="usine_found" class="form-text mt-1" style="display: none;">
                                Usine trouvée :
                                <button type="button" id="usine_found_select"
                                    class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                    <span id="usine_found_name"></span>
                                </button>
                                <span class="text-muted">— cliquer pour sélectionner</span>
                            </div>
                            @error('id_usine')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 position-relative">
                            <label for="agent_search" class="form-label">Sélectionner un chargé de mission</label>
                            <input type="text" id="agent_search"
                                class="form-control @error('id_agent') is-invalid @enderror"
                                placeholder="Rechercher par N° agent..."
                                autocomplete="off"
                                value="{{ $selectedAgent?->full_name }}">
                            <input type="hidden" name="id_agent" id="id_agent"
                                value="{{ old('id_agent') }}" required>
                            <div id="agent_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1056; display: none; max-height: 200px; overflow-y: auto;"></div>
                            <div id="agent_found" class="form-text mt-1" style="display: none;">
                                Agent trouvé :
                                <button type="button" id="agent_found_select"
                                    class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                    <span id="agent_found_name"></span>
                                </button>
                                <span class="text-muted">— cliquer pour sélectionner</span>
                            </div>
                            @error('id_agent')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 position-relative">
                            <label for="vehicule_search" class="form-label">Sélection véhicule</label>
                            <input type="text" id="vehicule_search"
                                class="form-control @error('vehicule_id') is-invalid @enderror"
                                placeholder="Rechercher un véhicule..."
                                autocomplete="off"
                                value="{{ $selectedVehicule?->matricule_vehicule }}">
                            <input type="hidden" name="vehicule_id" id="vehicule_id"
                                value="{{ old('vehicule_id') }}" required>
                            <div id="vehicule_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1055; display: none; max-height: 200px; overflow-y: auto;"></div>
                            <div id="vehicule_found" class="form-text mt-1" style="display: none;">
                                Véhicule trouvé :
                                <button type="button" id="vehicule_found_select"
                                    class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                    <span id="vehicule_found_name"></span>
                                </button>
                                <span class="text-muted">— cliquer pour sélectionner</span>
                            </div>
                            @error('vehicule_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="poids" class="form-label">Poids</label>
                            <input type="number" name="poids" id="poids" step="0.01" min="0"
                                class="form-control @error('poids') is-invalid @enderror"
                                value="{{ old('poids') }}" placeholder="Poids" required>
                            @error('poids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('tickets.partials.search-modals', ['searchAction' => route('tickets.index')])

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addTicketModal')).show();
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('addTicketModal');

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

                modal.addEventListener('hidden.bs.modal', () => {
                    searchInput.value = '';
                    clearSelection();
                    suggestions.style.display = 'none';
                });

                return { clearSelection };
            }

            setupAutocomplete({
                items: @json($usinesForAutocomplete),
                searchId: 'usine_search',
                hiddenId: 'id_usine',
                suggestionsId: 'usine_suggestions',
                foundId: 'usine_found',
                foundNameId: 'usine_found_name',
                foundSelectId: 'usine_found_select',
                labelKey: 'label',
                emptyText: 'Aucune usine trouvée',
                filter: (item, term) => item.label.toLowerCase().includes(term),
                isExact: (item, term) => item.label.toLowerCase() === term,
                renderItem: (item) => `<strong>${item.label}</strong>`,
            });

            setupAutocomplete({
                items: @json($agentsForAutocomplete),
                searchId: 'agent_search',
                hiddenId: 'id_agent',
                suggestionsId: 'agent_suggestions',
                foundId: 'agent_found',
                foundNameId: 'agent_found_name',
                foundSelectId: 'agent_found_select',
                labelKey: 'name',
                emptyText: 'Aucun agent trouvé',
                filter: (item, term) => item.numero.toLowerCase().includes(term),
                isExact: (item, term) => item.numero.toLowerCase() === term,
                renderItem: (item) => `<span class="text-muted">${item.numero}</span> — <strong>${item.name}</strong>`,
            });

            setupAutocomplete({
                items: @json($vehiculesForAutocomplete),
                searchId: 'vehicule_search',
                hiddenId: 'vehicule_id',
                suggestionsId: 'vehicule_suggestions',
                foundId: 'vehicule_found',
                foundNameId: 'vehicule_found_name',
                foundSelectId: 'vehicule_found_select',
                labelKey: 'label',
                emptyText: 'Aucun véhicule trouvé',
                filter: (item, term) => item.label.toLowerCase().includes(term),
                isExact: (item, term) => item.label.toLowerCase() === term,
                renderItem: (item) => `<strong>${item.label}</strong>`,
            });
        });
    </script>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuModalEl = document.getElementById('searchTicketModal');

            function getModal(element) {
                return bootstrap.Modal.getInstance(element) || new bootstrap.Modal(element);
            }

            document.querySelectorAll('.search-ticket-option').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();

                    const targetSelector = button.getAttribute('data-target-modal');
                    const targetEl = targetSelector ? document.querySelector(targetSelector) : null;

                    if (! targetEl || typeof bootstrap === 'undefined') {
                        return;
                    }

                    const openTarget = function () {
                        getModal(targetEl).show();
                    };

                    if (menuModalEl && menuModalEl.classList.contains('show')) {
                        const menuInstance = getModal(menuModalEl);

                        menuModalEl.addEventListener('hidden.bs.modal', openTarget, { once: true });
                        menuInstance.hide();
                    } else {
                        openTarget();
                    }
                });
            });

            const singleDateInput = document.getElementById('search_single_date');
            const singleDateMirror = document.getElementById('search_single_date_mirror');

            if (singleDateInput && singleDateMirror) {
                singleDateInput.addEventListener('change', function () {
                    singleDateMirror.value = singleDateInput.value;
                });

                singleDateInput.closest('form')?.addEventListener('submit', function () {
                    singleDateMirror.value = singleDateInput.value;
                });
            }
        });
    </script>
    @endpush
@endsection
