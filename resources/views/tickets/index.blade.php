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
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#printUsineModal">
                                <i class="bi bi-file-pdf"></i> Imprimer par usine
                            </button>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#printBordereauModal">
                                <i class="bi bi-printer"></i> Bordereau
                            </button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('tickets.export-all') }}" class="btn btn-dark">
                                <i class="bi bi-download"></i> Exporter tous
                            </a>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportPeriodModal">
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
                                placeholder="Rechercher par N° agent ou nom..."
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

                        <div class="mb-3 d-none" id="pont_section">
                            <label class="form-label" for="id_pont_select">Pont-bascule</label>
                            <input type="hidden" name="id_pont" id="id_pont" value="{{ old('id_pont') }}">
                            <input type="text" id="pont_readonly" class="form-control bg-light d-none" readonly>
                            <select id="id_pont_select" class="form-select d-none @error('id_pont') is-invalid @enderror">
                                <option value="">— Sélectionner un pont —</option>
                            </select>
                            <div id="pont_empty" class="form-text text-muted d-none">
                                Aucun pont-bascule associé à cet agent.
                            </div>
                            @error('id_pont')
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

    <div class="modal fade" id="printUsineModal" tabindex="-1" aria-labelledby="printUsineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="{{ route('tickets.pdf-by-usine') }}" target="_blank" id="printUsineForm">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title" id="printUsineModalLabel">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Impression des tickets par usine
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 position-relative">
                            <label for="print_usine_search" class="form-label">Sélectionner une usine</label>
                            <input type="text" id="print_usine_search" class="form-control"
                                placeholder="Rechercher une usine..." autocomplete="off">
                            <input type="hidden" name="id_usine" id="print_id_usine" required>
                            <div id="print_usine_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1060; display: none; max-height: 200px; overflow-y: auto;"></div>
                            <div id="print_usine_found" class="form-text mt-1" style="display: none;">
                                Usine trouvée :
                                <button type="button" id="print_usine_found_select"
                                    class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                    <span id="print_usine_found_name"></span>
                                </button>
                                <span class="text-muted">— cliquer pour sélectionner</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="print_date_debut" class="form-label">Date début</label>
                            <input type="text" id="print_date_debut" class="form-control print-date-fr"
                                inputmode="numeric" autocomplete="off" placeholder="jj/mm/aaaa"
                                value="{{ now()->startOfMonth()->format('d/m/Y') }}" required>
                            <input type="hidden" name="date_debut" id="print_date_debut_value"
                                value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                        </div>
                        <div class="mb-0">
                            <label for="print_date_fin" class="form-label">Date fin</label>
                            <input type="text" id="print_date_fin" class="form-control print-date-fr"
                                inputmode="numeric" autocomplete="off" placeholder="jj/mm/aaaa"
                                value="{{ now()->format('d/m/Y') }}" required>
                            <input type="hidden" name="date_fin" id="print_date_fin_value"
                                value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-earmark-pdf"></i> Générer PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="printBordereauModal" tabindex="-1" aria-labelledby="printBordereauModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="{{ route('tickets.pdf-bordereau') }}" target="_blank" id="printBordereauForm">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="printBordereauModalLabel">Impression bordereau</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 position-relative">
                            <label for="bordereau_agent_search" class="form-label fw-semibold">Chargé de Mission</label>
                            <input type="text" id="bordereau_agent_search" class="form-control"
                                placeholder="Tapez le nom du chargé de mission..." autocomplete="off">
                            <input type="hidden" name="id_agent" id="bordereau_id_agent" required>
                            <div id="bordereau_agent_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1060; display: none; max-height: 200px; overflow-y: auto;"></div>
                            <div id="bordereau_agent_found" class="form-text mt-1" style="display: none;">
                                Agent trouvé :
                                <button type="button" id="bordereau_agent_found_select"
                                    class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                    <span id="bordereau_agent_found_name"></span>
                                </button>
                                <span class="text-muted">— cliquer pour sélectionner</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="bordereau_date_debut" class="form-label fw-semibold">Date de debut</label>
                            <div class="input-group position-relative">
                                <input type="text" id="bordereau_date_debut" class="form-control print-date-fr"
                                    inputmode="numeric" autocomplete="off" placeholder="jj/mm/aaaa" required>
                                <span class="input-group-text bg-white position-relative" style="min-width: 2.75rem;">
                                    <i class="bi bi-calendar3 text-muted"></i>
                                    <input type="date" id="bordereau_date_debut_picker"
                                        class="bordereau-native-datepicker" title="Choisir la date de début"
                                        data-display="#bordereau_date_debut"
                                        style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;border:0;padding:0;margin:0;">
                                </span>
                            </div>
                            <input type="hidden" name="date_debut" id="bordereau_date_debut_value" value="">
                        </div>
                        <div class="mb-0">
                            <label for="bordereau_date_fin" class="form-label fw-semibold">Date Fin</label>
                            <div class="input-group position-relative">
                                <input type="text" id="bordereau_date_fin" class="form-control print-date-fr"
                                    inputmode="numeric" autocomplete="off" placeholder="jj/mm/aaaa" required>
                                <span class="input-group-text bg-white position-relative" style="min-width: 2.75rem;">
                                    <i class="bi bi-calendar3 text-muted"></i>
                                    <input type="date" id="bordereau_date_fin_picker"
                                        class="bordereau-native-datepicker" title="Choisir la date de fin"
                                        data-display="#bordereau_date_fin"
                                        style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;border:0;padding:0;margin:0;">
                                </span>
                            </div>
                            <input type="hidden" name="date_fin" id="bordereau_date_fin_value" value="">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-start">
                        <button type="submit" class="btn btn-primary">Imprimer</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exportPeriodModal" tabindex="-1" aria-labelledby="exportPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="{{ route('tickets.export-period') }}" id="exportPeriodForm">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="exportPeriodModalLabel">
                            <i class="bi bi-calendar3 me-2"></i>Exporter une période
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">Export CSV des tickets selon la <strong>date de création</strong>.</p>
                        <div class="mb-3">
                            <label for="export_date_debut" class="form-label">Date de début</label>
                            <input type="text" id="export_date_debut" class="form-control print-date-fr"
                                inputmode="numeric" autocomplete="off" placeholder="jj/mm/aaaa"
                                value="{{ now()->startOfMonth()->format('d/m/Y') }}" required>
                            <input type="hidden" name="date_debut" id="export_date_debut_value"
                                value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                        </div>
                        <div class="mb-0">
                            <label for="export_date_fin" class="form-label">Date fin</label>
                            <input type="text" id="export_date_fin" class="form-control print-date-fr"
                                inputmode="numeric" autocomplete="off" placeholder="jj/mm/aaaa"
                                value="{{ now()->format('d/m/Y') }}" required>
                            <input type="hidden" name="date_fin" id="export_date_fin_value"
                                value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download"></i> Exporter CSV
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
                    config.onClear?.();
                }

                function selectItem(item) {
                    searchInput.value = config.getLabel ? config.getLabel(item) : item[config.labelKey];
                    hiddenInput.value = item.id;
                    pendingItem = null;
                    foundBox.style.display = 'none';
                    foundName.textContent = '';
                    suggestions.style.display = 'none';
                    config.onSelect?.(item);
                }

                function showPendingItem(item) {
                    pendingItem = item;
                    foundName.textContent = config.getLabel ? config.getLabel(item) : item[config.labelKey];
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
                        config.onClear?.();
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

                return { clearSelection, selectItem };
            }

            const agentsPontsMap = @json($agentsPontsMap ?? []);
            const pontSection = document.getElementById('pont_section');
            const pontHidden = document.getElementById('id_pont');
            const pontReadonly = document.getElementById('pont_readonly');
            const pontSelect = document.getElementById('id_pont_select');
            const pontEmpty = document.getElementById('pont_empty');

            function resetPontField() {
                pontHidden.value = '';
                pontReadonly.value = '';
                pontReadonly.classList.add('d-none');
                pontSelect.classList.add('d-none');
                pontSelect.innerHTML = '<option value="">— Sélectionner un pont —</option>';
                pontEmpty.classList.add('d-none');
                pontSection.classList.add('d-none');
            }

            function updatePontField(agentId, selectedPontId = null) {
                resetPontField();

                if (!agentId) {
                    return;
                }

                const ponts = agentsPontsMap[String(agentId)] || agentsPontsMap[agentId] || [];
                pontSection.classList.remove('d-none');

                if (ponts.length === 0) {
                    pontEmpty.classList.remove('d-none');
                    return;
                }

                if (ponts.length === 1) {
                    pontReadonly.value = ponts[0].label;
                    pontReadonly.classList.remove('d-none');
                    pontHidden.value = String(ponts[0].id);
                    return;
                }

                ponts.forEach((pont) => {
                    const option = document.createElement('option');
                    option.value = pont.id;
                    option.textContent = pont.label;
                    if (selectedPontId && String(selectedPontId) === String(pont.id)) {
                        option.selected = true;
                    }
                    pontSelect.appendChild(option);
                });

                pontSelect.classList.remove('d-none');

                if (selectedPontId) {
                    pontHidden.value = String(selectedPontId);
                }
            }

            pontSelect.addEventListener('change', () => {
                pontHidden.value = pontSelect.value;
            });

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
                getLabel: (item) => item.numero ? `${item.numero} — ${item.name}` : item.name,
                filter: (item, term) => item.numero.toLowerCase().includes(term)
                    || item.name.toLowerCase().includes(term),
                isExact: (item, term) => item.numero.toLowerCase() === term
                    || item.name.toLowerCase() === term,
                renderItem: (item) => item.numero
                    ? `<span class="text-muted">${item.numero}</span> — <strong>${item.name}</strong>`
                    : `<strong>${item.name}</strong>`,
                onSelect: (item) => updatePontField(item.id),
                onClear: () => resetPontField(),
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

            setupAutocomplete({
                items: @json($usinesForAutocomplete),
                searchId: 'print_usine_search',
                hiddenId: 'print_id_usine',
                suggestionsId: 'print_usine_suggestions',
                foundId: 'print_usine_found',
                foundNameId: 'print_usine_found_name',
                foundSelectId: 'print_usine_found_select',
                labelKey: 'label',
                emptyText: 'Aucune usine trouvée',
                filter: (item, term) => item.label.toLowerCase().includes(term),
                isExact: (item, term) => item.label.toLowerCase() === term,
                renderItem: (item) => `<strong>${item.label}</strong>`,
            });

            setupAutocomplete({
                items: @json($agentsForAutocomplete),
                searchId: 'bordereau_agent_search',
                hiddenId: 'bordereau_id_agent',
                suggestionsId: 'bordereau_agent_suggestions',
                foundId: 'bordereau_agent_found',
                foundNameId: 'bordereau_agent_found_name',
                foundSelectId: 'bordereau_agent_found_select',
                labelKey: 'name',
                emptyText: 'Aucun agent trouvé',
                getLabel: (item) => item.numero ? `${item.numero} — ${item.name}` : item.name,
                filter: (item, term) => item.numero.toLowerCase().includes(term)
                    || item.name.toLowerCase().includes(term),
                isExact: (item, term) => item.numero.toLowerCase() === term
                    || item.name.toLowerCase() === term,
                renderItem: (item) => item.numero
                    ? `<span class="text-muted">${item.numero}</span> — <strong>${item.name}</strong>`
                    : `<strong>${item.name}</strong>`,
            });

            const printUsineForm = document.getElementById('printUsineForm');
            const printUsineModal = document.getElementById('printUsineModal');
            const printBordereauForm = document.getElementById('printBordereauForm');
            const printBordereauModal = document.getElementById('printBordereauModal');

            function formatFrenchDateInput(value) {
                const digits = String(value).replace(/\D/g, '').slice(0, 8);
                if (digits.length <= 2) {
                    return digits;
                }
                if (digits.length <= 4) {
                    return digits.slice(0, 2) + '/' + digits.slice(2);
                }
                return digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
            }

            function parseFrenchDateToIso(value) {
                const match = String(value).trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                if (!match) {
                    return null;
                }

                const day = parseInt(match[1], 10);
                const month = parseInt(match[2], 10);
                const year = parseInt(match[3], 10);
                const date = new Date(year, month - 1, day);

                if (
                    date.getFullYear() !== year
                    || date.getMonth() !== month - 1
                    || date.getDate() !== day
                ) {
                    return null;
                }

                return [
                    String(year).padStart(4, '0'),
                    String(month).padStart(2, '0'),
                    String(day).padStart(2, '0'),
                ].join('-');
            }

            function isoToFrenchDate(value) {
                const match = String(value).trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (!match) {
                    return '';
                }

                return match[3] + '/' + match[2] + '/' + match[1];
            }

            document.querySelectorAll('.print-date-fr').forEach(function (input) {
                input.addEventListener('input', function () {
                    const cursorFromEnd = input.value.length - input.selectionStart;
                    input.value = formatFrenchDateInput(input.value);
                    const nextPos = Math.max(0, input.value.length - cursorFromEnd);
                    input.setSelectionRange(nextPos, nextPos);

                    const picker = document.querySelector('.bordereau-native-datepicker[data-display="#' + input.id + '"]');
                    if (picker) {
                        picker.value = parseFrenchDateToIso(input.value) || '';
                    }
                });
            });

            document.querySelectorAll('.bordereau-native-datepicker').forEach(function (picker) {
                picker.addEventListener('change', function () {
                    const display = document.querySelector(picker.dataset.display);
                    if (!display || !picker.value) {
                        return;
                    }

                    display.value = isoToFrenchDate(picker.value);
                });

                picker.addEventListener('click', function () {
                    const display = document.querySelector(picker.dataset.display);
                    if (display) {
                        picker.value = parseFrenchDateToIso(display.value) || '';
                    }
                });
            });

            function bindPdfFormValidation(form, config) {
                if (!form) {
                    return;
                }

                form.addEventListener('submit', function (event) {
                    const entityId = document.getElementById(config.entityHiddenId)?.value;
                    const dateDebutDisplay = document.getElementById(config.dateDebutInputId)?.value;
                    const dateFinDisplay = document.getElementById(config.dateFinInputId)?.value;
                    const dateDebutIso = parseFrenchDateToIso(dateDebutDisplay);
                    const dateFinIso = parseFrenchDateToIso(dateFinDisplay);

                    if (!entityId) {
                        event.preventDefault();
                        alert(config.entityMessage);
                        return;
                    }

                    if (!dateDebutIso) {
                        event.preventDefault();
                        alert('La date de début doit être au format jj/mm/aaaa (ex. 01/07/2026).');
                        return;
                    }

                    if (!dateFinIso) {
                        event.preventDefault();
                        alert('La date de fin doit être au format jj/mm/aaaa (ex. 07/07/2026).');
                        return;
                    }

                    if (dateFinIso < dateDebutIso) {
                        event.preventDefault();
                        alert('La date de fin doit être postérieure ou égale à la date de début.');
                        return;
                    }

                    document.getElementById(config.dateDebutHiddenId).value = dateDebutIso;
                    document.getElementById(config.dateFinHiddenId).value = dateFinIso;
                });
            }

            bindPdfFormValidation(printUsineForm, {
                entityHiddenId: 'print_id_usine',
                entityMessage: 'Veuillez sélectionner une usine dans la liste de suggestions.',
                dateDebutInputId: 'print_date_debut',
                dateFinInputId: 'print_date_fin',
                dateDebutHiddenId: 'print_date_debut_value',
                dateFinHiddenId: 'print_date_fin_value',
            });

            bindPdfFormValidation(printBordereauForm, {
                entityHiddenId: 'bordereau_id_agent',
                entityMessage: 'Veuillez sélectionner un agent dans la liste de suggestions.',
                dateDebutInputId: 'bordereau_date_debut',
                dateFinInputId: 'bordereau_date_fin',
                dateDebutHiddenId: 'bordereau_date_debut_value',
                dateFinHiddenId: 'bordereau_date_fin_value',
            });

            const exportPeriodForm = document.getElementById('exportPeriodForm');
            const exportPeriodModal = document.getElementById('exportPeriodModal');

            function bindDateRangeForm(form, config) {
                if (!form) {
                    return;
                }

                form.addEventListener('submit', function (event) {
                    const dateDebutDisplay = document.getElementById(config.dateDebutInputId)?.value;
                    const dateFinDisplay = document.getElementById(config.dateFinInputId)?.value;
                    const dateDebutIso = parseFrenchDateToIso(dateDebutDisplay);
                    const dateFinIso = parseFrenchDateToIso(dateFinDisplay);

                    if (!dateDebutIso) {
                        event.preventDefault();
                        alert('La date de début doit être au format jj/mm/aaaa (ex. 01/07/2026).');
                        return;
                    }

                    if (!dateFinIso) {
                        event.preventDefault();
                        alert('La date de fin doit être au format jj/mm/aaaa (ex. 07/07/2026).');
                        return;
                    }

                    if (dateFinIso < dateDebutIso) {
                        event.preventDefault();
                        alert('La date de fin doit être postérieure ou égale à la date de début.');
                        return;
                    }

                    document.getElementById(config.dateDebutHiddenId).value = dateDebutIso;
                    document.getElementById(config.dateFinHiddenId).value = dateFinIso;
                });
            }

            bindDateRangeForm(exportPeriodForm, {
                dateDebutInputId: 'export_date_debut',
                dateFinInputId: 'export_date_fin',
                dateDebutHiddenId: 'export_date_debut_value',
                dateFinHiddenId: 'export_date_fin_value',
            });

            if (exportPeriodModal) {
                exportPeriodModal.addEventListener('hidden.bs.modal', function () {
                    const dateDebutInput = document.getElementById('export_date_debut');
                    const dateFinInput = document.getElementById('export_date_fin');
                    const dateDebutValue = document.getElementById('export_date_debut_value');
                    const dateFinValue = document.getElementById('export_date_fin_value');

                    if (dateDebutInput) dateDebutInput.value = @json(now()->startOfMonth()->format('d/m/Y'));
                    if (dateFinInput) dateFinInput.value = @json(now()->format('d/m/Y'));
                    if (dateDebutValue) dateDebutValue.value = @json(now()->startOfMonth()->format('Y-m-d'));
                    if (dateFinValue) dateFinValue.value = @json(now()->format('Y-m-d'));
                });
            }

            if (printUsineModal) {
                printUsineModal.addEventListener('hidden.bs.modal', function () {
                    const searchInput = document.getElementById('print_usine_search');
                    const hiddenInput = document.getElementById('print_id_usine');
                    const suggestions = document.getElementById('print_usine_suggestions');
                    const foundBox = document.getElementById('print_usine_found');
                    const dateDebutInput = document.getElementById('print_date_debut');
                    const dateFinInput = document.getElementById('print_date_fin');
                    const dateDebutValue = document.getElementById('print_date_debut_value');
                    const dateFinValue = document.getElementById('print_date_fin_value');

                    if (searchInput) searchInput.value = '';
                    if (hiddenInput) hiddenInput.value = '';
                    if (dateDebutInput) dateDebutInput.value = @json(now()->startOfMonth()->format('d/m/Y'));
                    if (dateFinInput) dateFinInput.value = @json(now()->format('d/m/Y'));
                    if (dateDebutValue) dateDebutValue.value = @json(now()->startOfMonth()->format('Y-m-d'));
                    if (dateFinValue) dateFinValue.value = @json(now()->format('Y-m-d'));
                    if (suggestions) {
                        suggestions.innerHTML = '';
                        suggestions.style.display = 'none';
                    }
                    if (foundBox) foundBox.style.display = 'none';
                });
            }

            if (printBordereauModal) {
                printBordereauModal.addEventListener('hidden.bs.modal', function () {
                    const searchInput = document.getElementById('bordereau_agent_search');
                    const hiddenInput = document.getElementById('bordereau_id_agent');
                    const suggestions = document.getElementById('bordereau_agent_suggestions');
                    const foundBox = document.getElementById('bordereau_agent_found');
                    const dateDebutInput = document.getElementById('bordereau_date_debut');
                    const dateFinInput = document.getElementById('bordereau_date_fin');
                    const dateDebutValue = document.getElementById('bordereau_date_debut_value');
                    const dateFinValue = document.getElementById('bordereau_date_fin_value');

                    if (searchInput) searchInput.value = '';
                    if (hiddenInput) hiddenInput.value = '';
                    if (dateDebutInput) dateDebutInput.value = '';
                    if (dateFinInput) dateFinInput.value = '';
                    if (dateDebutValue) dateDebutValue.value = '';
                    if (dateFinValue) dateFinValue.value = '';
                    if (suggestions) {
                        suggestions.innerHTML = '';
                        suggestions.style.display = 'none';
                    }
                    if (foundBox) foundBox.style.display = 'none';
                });
            }

            const oldAgentId = @json(old('id_agent'));
            const oldPontId = @json(old('id_pont'));
            if (oldAgentId) {
                updatePontField(oldAgentId, oldPontId);
            }
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
