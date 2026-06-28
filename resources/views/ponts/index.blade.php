@extends('layout.main')

@section('title', 'Liste des ponts')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Gestion des ponts-bascules</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ponts-bascules</li>
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

    <section class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #435ebe;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['total'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Total ponts</div>
                        </div>
                        <i class="bi bi-signpost-split fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #198754;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['actifs'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Ponts actifs</div>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #dc3545;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['inactifs'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Ponts inactifs</div>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #fd7e14;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['avec_cooperative'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Avec coopérative</div>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPontModal">
                            <i class="bi bi-plus-circle"></i> Enregistrer un pont
                        </button>
                        @if (auth()->user()->canAccessModule('ponts.location'))
                        <a href="{{ route('ponts.location') }}" class="btn btn-info">
                            <i class="bi bi-geo-alt"></i> Localisation des ponts
                        </a>
                        @endif
                        @if (auth()->user()->canAccessModule('ponts.types'))
                        <a href="{{ route('ponts.types.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-type"></i> Types de ponts
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4" id="pont-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-funnel"></i> Filtres</div>
                <div class="card-body">
                    <form method="GET" action="{{ route('ponts.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Code, nom, gérant, coopérative..." value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select name="statut" id="statut" class="form-select">
                                <option value="">Tous</option>
                                <option value="Actif" @selected($filters['statut'] === 'Actif')>Actif</option>
                                <option value="Inactif" @selected($filters['statut'] === 'Inactif')>Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="cooperatif" class="form-label">Coopérative</label>
                            <select name="cooperatif" id="cooperatif" class="form-select">
                                <option value="">Toutes</option>
                                @foreach ($cooperatives as $coop)
                                    <option value="{{ $coop }}" @selected($filters['cooperatif'] === $coop)>{{ $coop }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                        </div>
                        @if ($hasFilters)
                        <div class="col-12">
                            <a href="{{ route('ponts.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des ponts-bascules</span>
                    <span class="text-muted">{{ $ponts->total() }} pont(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="ponts-table-header">
                            <tr>
                                <th>Code</th>
                                <th>Nom du pont</th>
                                <th>Type</th>
                                <th>Gérant</th>
                                <th>Coopérative</th>
                                <th>Coordonnées</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ponts as $pont)
                                <tr>
                                    <td><code>{{ $pont->code_pont }}</code></td>
                                    <td class="fw-semibold">{{ $pont->nom_pont }}</td>
                                    <td>{{ $pont->typePont?->libelle ?? '—' }}</td>
                                    <td>{{ $pont->gerantLabel() }}</td>
                                    <td>{{ $pont->cooperatif ?: '—' }}</td>
                                    <td>
                                        @if ($pont->hasCoordinates())
                                            <span class="text-success small">
                                                <i class="bi bi-geo-alt"></i>
                                                {{ number_format((float) $pont->latitude, 5) }},
                                                {{ number_format((float) $pont->longitude, 5) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Non définie</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($pont->isActive())
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-warning edit-pont-btn"
                                                data-bs-toggle="modal" data-bs-target="#editPontModal"
                                                data-id="{{ $pont->id_pont }}"
                                                data-code="{{ $pont->code_pont }}"
                                                data-nom="{{ $pont->nom_pont }}"
                                                data-type-id="{{ $pont->id_type_pont }}"
                                                data-id-agent="{{ $pont->id_agent }}"
                                                data-cooperatif="{{ $pont->cooperatif }}"
                                                data-statut="{{ $pont->statut }}"
                                                data-latitude="{{ $pont->latitude }}"
                                                data-longitude="{{ $pont->longitude }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#deletePontModal"
                                                data-id="{{ $pont->id_pont }}"
                                                data-label="{{ $pont->code_pont }} — {{ $pont->nom_pont }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        @if ($hasFilters)
                                            Aucun pont ne correspond aux filtres.
                                        @else
                                            Aucun pont-bascule enregistré.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $ponts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addPontModal" tabindex="-1" aria-labelledby="addPontModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('ponts.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPontModalLabel">Enregistrer un pont-bascule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i> Le code pont sera généré automatiquement (ex. UNIPALM-PB-0001-CI).
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nom_pont" class="form-label">Nom du pont *</label>
                                <input type="text" name="nom_pont" id="nom_pont" class="form-control @error('nom_pont') is-invalid @enderror"
                                    value="{{ old('nom_pont') }}" required>
                                @error('nom_pont')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="id_type_pont" class="form-label">Type de pont</label>
                                <select name="id_type_pont" id="id_type_pont" class="form-select @error('id_type_pont') is-invalid @enderror">
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($typesPont as $type)
                                        <option value="{{ $type->id_type_pont }}" @selected(old('id_type_pont') == $type->id_type_pont)>
                                            {{ $type->libelle }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_type_pont')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 position-relative">
                                <label for="gerant_search" class="form-label">Gérant *</label>
                                <input type="text" id="gerant_search"
                                    class="form-control @error('id_agent') is-invalid @enderror"
                                    placeholder="Rechercher par N° agent..."
                                    autocomplete="off"
                                    value="{{ old('gerant_search') }}">
                                <input type="hidden" name="id_agent" id="id_agent"
                                    value="{{ old('id_agent') }}" required>
                                <div id="gerant_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                    style="z-index: 1060; display: none; max-height: 200px; overflow-y: auto;"></div>
                                <div id="gerant_found" class="form-text mt-1" style="display: none;">
                                    Gérant trouvé :
                                    <button type="button" id="gerant_found_select"
                                        class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                        <span id="gerant_found_name"></span>
                                    </button>
                                    <span class="text-muted">— cliquer pour sélectionner</span>
                                </div>
                                @error('id_agent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="cooperatif" class="form-label">Coopérative</label>
                                <input type="text" name="cooperatif" id="cooperatif" class="form-control"
                                    value="{{ old('cooperatif') }}" placeholder="Ex. Unicoop">
                            </div>
                            <div class="col-md-6">
                                <label for="statut_add" class="form-label">Statut *</label>
                                <select name="statut" id="statut_add" class="form-select" required>
                                    <option value="Actif" @selected(old('statut', 'Actif') === 'Actif')>Actif</option>
                                    <option value="Inactif" @selected(old('statut') === 'Inactif')>Inactif</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="latitude_add" class="form-label">Latitude</label>
                                <input type="number" name="latitude" id="latitude_add" class="form-control" step="any"
                                    value="{{ old('latitude') }}" placeholder="5.3599517">
                            </div>
                            <div class="col-md-6">
                                <label for="longitude_add" class="form-label">Longitude</label>
                                <input type="number" name="longitude" id="longitude_add" class="form-control" step="any"
                                    value="{{ old('longitude') }}" placeholder="-4.0082563">
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

    <div class="modal fade" id="editPontModal" tabindex="-1" aria-labelledby="editPontModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editPontForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="editPontModalLabel">Modifier le pont-bascule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_code_pont" class="form-label">Code pont *</label>
                                <input type="text" name="code_pont" id="edit_code_pont" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_nom_pont" class="form-label">Nom du pont *</label>
                                <input type="text" name="nom_pont" id="edit_nom_pont" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_id_type_pont" class="form-label">Type de pont</label>
                                <select name="id_type_pont" id="edit_id_type_pont" class="form-select">
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($typesPont as $type)
                                        <option value="{{ $type->id_type_pont }}">{{ $type->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label for="edit_gerant_search" class="form-label">Gérant *</label>
                                <input type="text" id="edit_gerant_search"
                                    class="form-control"
                                    placeholder="Rechercher par N° agent..."
                                    autocomplete="off">
                                <input type="hidden" name="id_agent" id="edit_id_agent" required>
                                <div id="edit_gerant_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                    style="z-index: 1060; display: none; max-height: 200px; overflow-y: auto;"></div>
                                <div id="edit_gerant_found" class="form-text mt-1" style="display: none;">
                                    Gérant trouvé :
                                    <button type="button" id="edit_gerant_found_select"
                                        class="btn btn-link btn-sm p-0 align-baseline text-success fw-bold text-decoration-none">
                                        <span id="edit_gerant_found_name"></span>
                                    </button>
                                    <span class="text-muted">— cliquer pour sélectionner</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_cooperatif" class="form-label">Coopérative</label>
                                <input type="text" name="cooperatif" id="edit_cooperatif" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="edit_statut" class="form-label">Statut *</label>
                                <select name="statut" id="edit_statut" class="form-select" required>
                                    <option value="Actif">Actif</option>
                                    <option value="Inactif">Inactif</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_latitude" class="form-label">Latitude</label>
                                <input type="number" name="latitude" id="edit_latitude" class="form-control" step="any">
                            </div>
                            <div class="col-md-4">
                                <label for="edit_longitude" class="form-label">Longitude</label>
                                <input type="number" name="longitude" id="edit_longitude" class="form-control" step="any">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deletePontModal" tabindex="-1" aria-labelledby="deletePontModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deletePontModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Supprimer le pont
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Confirmer la suppression de <strong id="deletePontLabel"></strong> ?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" id="deletePontForm" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .ponts-table-header {
            background: #111;
        }

        .ponts-table-header th {
            color: #fff !important;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            padding: 0.95rem 1rem;
            white-space: nowrap;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.getElementById('editPontForm');
            const deleteForm = document.getElementById('deletePontForm');
            const pontBaseUrl = @json(url('/ponts'));
            const agentsForAutocomplete = @json($agentsForAutocomplete);

            function setupGerantAutocomplete(config) {
                const searchInput = document.getElementById(config.searchId);
                const hiddenInput = document.getElementById(config.hiddenId);
                const suggestions = document.getElementById(config.suggestionsId);
                const foundBox = document.getElementById(config.foundId);
                const foundName = document.getElementById(config.foundNameId);
                const foundSelect = document.getElementById(config.foundSelectId);
                let pendingItem = null;

                if (!searchInput || !hiddenInput) {
                    return { setGerant: function () {} };
                }

                function formatDisplay(item) {
                    return item.numero
                        ? item.numero + ' — ' + item.name
                        : item.name;
                }

                function clearSelection() {
                    hiddenInput.value = '';
                    pendingItem = null;
                    foundBox.style.display = 'none';
                    foundName.textContent = '';
                }

                function selectItem(item) {
                    searchInput.value = formatDisplay(item);
                    hiddenInput.value = item.id;
                    pendingItem = null;
                    foundBox.style.display = 'none';
                    foundName.textContent = '';
                    suggestions.style.display = 'none';
                }

                function showPendingItem(item) {
                    pendingItem = item;
                    foundName.textContent = formatDisplay(item);
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

                    const matches = agentsForAutocomplete.filter(function (item) {
                        return item.numero.toLowerCase().includes(term)
                            || item.name.toLowerCase().includes(term);
                    }).slice(0, 10);

                    if (matches.length === 0) {
                        suggestions.innerHTML = '<div class="list-group-item text-muted">Aucun agent trouvé</div>';
                        suggestions.style.display = 'block';
                        clearSelection();
                        return;
                    }

                    matches.forEach(function (item) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action';
                        button.innerHTML = item.numero
                            ? '<span class="text-muted">' + item.numero + '</span> — <strong>' + item.name + '</strong>'
                            : '<strong>' + item.name + '</strong>';
                        button.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            selectItem(item);
                        });
                        suggestions.appendChild(button);
                    });

                    suggestions.style.display = 'block';

                    const exact = agentsForAutocomplete.find(function (item) {
                        return item.numero.toLowerCase() === term;
                    });

                    if (exact) {
                        showPendingItem(exact);
                    } else {
                        foundBox.style.display = 'none';
                        pendingItem = null;
                        hiddenInput.value = '';
                    }
                }

                searchInput.addEventListener('input', function () {
                    if (hiddenInput.value) {
                        hiddenInput.value = '';
                        pendingItem = null;
                        foundBox.style.display = 'none';
                    }
                    showSuggestions(searchInput.value);
                });

                searchInput.addEventListener('focus', function () {
                    const term = searchInput.value.trim();
                    if (term && !hiddenInput.value) {
                        showSuggestions(term);
                    }
                });

                foundSelect?.addEventListener('click', function () {
                    if (pendingItem) {
                        selectItem(pendingItem);
                    }
                });

                document.addEventListener('click', function (event) {
                    if (!searchInput.contains(event.target)
                        && !suggestions.contains(event.target)
                        && !foundBox.contains(event.target)) {
                        suggestions.style.display = 'none';
                    }
                });

                return {
                    setAgent: function (agentId) {
                        const agent = agentsForAutocomplete.find(function (item) {
                            return String(item.id) === String(agentId);
                        });

                        if (agent) {
                            selectItem(agent);
                        } else {
                            searchInput.value = '';
                            hiddenInput.value = agentId || '';
                        }
                    },
                    clearSelection: function () {
                        searchInput.value = '';
                        clearSelection();
                        suggestions.style.display = 'none';
                    },
                };
            }

            const addGerantAutocomplete = setupGerantAutocomplete({
                searchId: 'gerant_search',
                hiddenId: 'id_agent',
                suggestionsId: 'gerant_suggestions',
                foundId: 'gerant_found',
                foundNameId: 'gerant_found_name',
                foundSelectId: 'gerant_found_select',
            });

            const editGerantAutocomplete = setupGerantAutocomplete({
                searchId: 'edit_gerant_search',
                hiddenId: 'edit_id_agent',
                suggestionsId: 'edit_gerant_suggestions',
                foundId: 'edit_gerant_found',
                foundNameId: 'edit_gerant_found_name',
                foundSelectId: 'edit_gerant_found_select',
            });

            document.getElementById('addPontModal')?.addEventListener('hidden.bs.modal', function () {
                addGerantAutocomplete.clearSelection();
            });

            document.getElementById('editPontModal')?.addEventListener('hidden.bs.modal', function () {
                editGerantAutocomplete.clearSelection();
            });

            document.querySelectorAll('.edit-pont-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = button.dataset.id;
                    editForm.action = `${pontBaseUrl}/${id}`;
                    document.getElementById('edit_code_pont').value = button.dataset.code || '';
                    document.getElementById('edit_nom_pont').value = button.dataset.nom || '';
                    document.getElementById('edit_id_type_pont').value = button.dataset.typeId || '';
                    editGerantAutocomplete.setAgent(button.dataset.idAgent || '');
                    document.getElementById('edit_cooperatif').value = button.dataset.cooperatif || '';
                    document.getElementById('edit_statut').value = button.dataset.statut || 'Actif';
                    document.getElementById('edit_latitude').value = button.dataset.latitude || '';
                    document.getElementById('edit_longitude').value = button.dataset.longitude || '';
                });
            });

            document.querySelectorAll('[data-bs-target="#deletePontModal"]').forEach(function (button) {
                if (!button.dataset.id) {
                    return;
                }

                button.addEventListener('click', function () {
                    deleteForm.action = `${pontBaseUrl}/${button.dataset.id}`;
                    document.getElementById('deletePontLabel').textContent = button.dataset.label || '';
                });
            });

            @if (old('id_agent') && ! old('_method'))
                addGerantAutocomplete.setAgent(@json(old('id_agent')));
            @endif

            @if ($errors->any() && ! old('_method'))
                new bootstrap.Modal(document.getElementById('addPontModal')).show();
            @endif
        });
    </script>
@endpush
