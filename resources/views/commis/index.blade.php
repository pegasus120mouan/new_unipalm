@extends('layout.main')

@section('title', 'Commis')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Commis</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Commis</li>
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
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommisModal">
                            <i class="bi bi-person-plus-fill"></i> Enregistrer un commis
                        </button>
                        <a href="#commis-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="commis-filters">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-funnel-fill me-2"></i>Filtres</span>
                    @if ($hasFilters)
                        <span class="badge bg-secondary">{{ $commis->total() }} résultat(s)</span>
                    @endif
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('commis.index') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Nom ou prénoms</label>
                                <input type="text" name="search" id="search" class="form-control"
                                    value="{{ $filters['search'] }}" placeholder="Rechercher un commis...">
                            </div>
                            <div class="col-md-4">
                                <label for="filter_agent_search" class="form-label">Agent</label>
                                <div class="position-relative">
                                    <input type="text" id="filter_agent_search" class="form-control"
                                        autocomplete="off" placeholder="Nom, prénom ou N° agent..."
                                        value="{{ $filterAgentLabel }}">
                                    <input type="hidden" name="id_agent" id="filter_id_agent"
                                        value="{{ $filters['id_agent'] ?? '' }}">
                                    <div id="filter_agent_suggestions" class="commis-agent-suggestions list-group shadow-sm" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="search_contact" class="form-label">Contact</label>
                                <input type="text" name="search_contact" id="search_contact" class="form-control"
                                    value="{{ $filters['search_contact'] }}" placeholder="Téléphone...">
                            </div>
                            <div class="col-12 d-flex gap-2 justify-content-end">
                                <button type="submit" class="btn btn-dark"><i class="bi bi-search"></i> Rechercher</button>
                                <a href="{{ route('commis.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <span class="fw-semibold">Liste des commis</span>
                    <span class="text-muted small ms-2">Un commis par pont — rattaché à un agent</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Prénoms</th>
                                <th>Contact</th>
                                <th>Code PIN</th>
                                <th>Agent</th>
                                <th>Pont</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($commis as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->nom }}</td>
                                    <td>{{ $item->prenoms }}</td>
                                    <td>{{ $item->contact }}</td>
                                    <td><span class="badge bg-dark font-monospace">{{ $item->code_pin }}</span></td>
                                    <td>
                                        @if ($item->agent)
                                            <a href="{{ route('agents.show', $item->agent) }}" class="text-primary">
                                                {{ $item->agent->full_name }}
                                            </a>
                                            <br><small class="text-muted">{{ $item->agent->numero_agent }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->pont)
                                            <span class="badge bg-label-info">{{ $item->pont->code_pont }}</span>
                                            <br><small>{{ $item->pont->nom_pont }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-commis"
                                            data-bs-toggle="modal" data-bs-target="#editCommisModal"
                                            data-id="{{ $item->id_commis }}"
                                            data-nom="{{ $item->nom }}"
                                            data-prenoms="{{ $item->prenoms }}"
                                            data-contact="{{ $item->contact }}"
                                            data-code-pin="{{ $item->code_pin }}"
                                            data-id-agent="{{ $item->id_agent }}"
                                            data-agent-label="{{ $item->agent ? trim(($item->agent->numero_agent ? $item->agent->numero_agent.' — ' : '').$item->agent->full_name) : '' }}"
                                            data-id-pont="{{ $item->id_pont }}"
                                            data-update-url="{{ route('commis.update', $item) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#deleteCommisModal"
                                            data-delete-url="{{ route('commis.destroy', $item) }}"
                                            data-name="{{ $item->full_name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Aucun commis enregistré.
                                        <br><small>Chaque pont d’un agent peut avoir un commis dédié.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($commis->hasPages())
                    <div class="card-footer">{{ $commis->links() }}</div>
                @endif
            </div>
        </div>
    </section>

    {{-- Modal ajout --}}
    <div class="modal fade" id="addCommisModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('commis.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nouveau commis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="add_nom" class="form-control" required value="{{ old('nom') }}">
                        </div>
                        <div class="mb-3">
                            <label for="add_prenoms" class="form-label">Prénoms <span class="text-danger">*</span></label>
                            <input type="text" name="prenoms" id="add_prenoms" class="form-control" required value="{{ old('prenoms') }}">
                        </div>
                        <div class="mb-3">
                            <label for="add_contact" class="form-label">Contact <span class="text-danger">*</span></label>
                            <input type="text" name="contact" id="add_contact" class="form-control" required value="{{ old('contact') }}">
                        </div>
                        <div class="mb-3">
                            <label for="add_agent_search" class="form-label">Agent <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" id="add_agent_search" class="form-control"
                                    autocomplete="off" placeholder="Nom, prénom ou N° agent..."
                                    value="{{ $oldAgentLabel }}">
                                <input type="hidden" name="id_agent" id="add_id_agent" value="{{ old('id_agent') }}">
                                <div id="add_agent_suggestions" class="commis-agent-suggestions list-group shadow-sm" style="display: none;"></div>
                            </div>
                            <small class="text-muted">Si l’agent n’a pas encore de pont, rattachez-en un depuis sa fiche agent.</small>
                        </div>
                        <div class="mb-0">
                            <label for="add_id_pont" class="form-label">Pont <span class="text-danger">*</span></label>
                            <select name="id_pont" id="add_id_pont" class="form-select commis-pont-select" required disabled>
                                <option value="">— Sélectionnez d’abord un agent —</option>
                            </select>
                            <small class="text-muted">Un seul commis par pont. Un code PIN à 6 chiffres sera généré automatiquement.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal édition --}}
    <div class="modal fade" id="editCommisModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editCommisForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier le commis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom</label>
                            <input type="text" name="nom" id="edit_nom" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_prenoms" class="form-label">Prénoms</label>
                            <input type="text" name="prenoms" id="edit_prenoms" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact" class="form-label">Contact</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_code_pin" class="form-label">Code PIN</label>
                            <input type="text" id="edit_code_pin" class="form-control font-monospace" readonly>
                            <small class="text-muted">Généré à la création, non modifiable.</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_agent_search" class="form-label">Agent</label>
                            <div class="position-relative">
                                <input type="text" id="edit_agent_search" class="form-control"
                                    autocomplete="off" placeholder="Nom, prénom ou N° agent...">
                                <input type="hidden" name="id_agent" id="edit_id_agent">
                                <div id="edit_agent_suggestions" class="commis-agent-suggestions list-group shadow-sm" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label for="edit_id_pont" class="form-label">Pont</label>
                            <select name="id_pont" id="edit_id_pont" class="form-select commis-pont-select" required>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal suppression --}}
    <div class="modal fade" id="deleteCommisModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteCommisForm" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Supprimer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Supprimer le commis <strong id="deleteCommisName"></strong> ?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
    .commis-agent-suggestions {
        position: absolute;
        top: calc(100% + 2px);
        left: 0;
        right: 0;
        z-index: 1060;
        max-height: 220px;
        overflow-y: auto;
        border-radius: 0.375rem;
    }

    .commis-agent-suggestions .list-group-item {
        cursor: pointer;
        font-size: 0.9rem;
        padding: 0.55rem 0.85rem;
        border-left: none;
        border-right: none;
    }

    .commis-agent-suggestions .list-group-item:first-child {
        border-top: none;
    }

    .commis-agent-suggestions .list-group-item-action:hover {
        background-color: rgba(67, 94, 190, 0.1);
        color: #1e2d4d;
    }

    .commis-agent-suggestion-numero {
        color: #6c757d;
        font-size: 0.82rem;
    }
</style>
<script>
(function() {
    var autocompleteUrl = @json(route('commis.agents-autocomplete'));
    var pontsUrlTemplate = @json(route('commis.ponts-for-agent', ['agent' => '__AGENT__']));
    var debounceTimer = null;
    var fetchController = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function hideSuggestions(box) {
        if (!box) return;
        box.style.display = 'none';
        box.innerHTML = '';
    }

    function hideAllAgentSuggestions() {
        document.querySelectorAll('.commis-agent-suggestions').forEach(hideSuggestions);
    }

    function setupAgentAutocomplete(searchInput, hiddenInput, suggestionsBox, onSelect) {
        if (!searchInput || !hiddenInput || !suggestionsBox) return;

        function renderSuggestions(items) {
            suggestionsBox.innerHTML = '';

            if (!items.length) {
                suggestionsBox.innerHTML = '<div class="list-group-item text-muted small">Aucun agent trouvé</div>';
                suggestionsBox.style.display = 'block';
                return;
            }

            items.forEach(function(item) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action text-start';

                if (item.numero) {
                    button.innerHTML = '<span class="commis-agent-suggestion-numero">' + escapeHtml(item.numero) + '</span> — <strong>' + escapeHtml(item.name) + '</strong>';
                } else {
                    button.innerHTML = '<strong>' + escapeHtml(item.name) + '</strong>';
                }

                button.addEventListener('mousedown', function(event) {
                    event.preventDefault();
                    searchInput.value = item.label;
                    hiddenInput.value = item.id;
                    hideSuggestions(suggestionsBox);
                    if (typeof onSelect === 'function') {
                        onSelect(item.id);
                    }
                });
                suggestionsBox.appendChild(button);
            });

            suggestionsBox.style.display = 'block';
        }

        function fetchSuggestions(query) {
            if (fetchController) {
                fetchController.abort();
            }
            fetchController = new AbortController();

            var url = new URL(autocompleteUrl, window.location.origin);
            url.searchParams.set('field', 'agent');
            url.searchParams.set('q', query);

            fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                signal: fetchController.signal,
            })
                .then(function(response) {
                    if (!response.ok) return [];
                    return response.json();
                })
                .then(function(items) {
                    renderSuggestions(Array.isArray(items) ? items : []);
                })
                .catch(function(error) {
                    if (error.name !== 'AbortError') {
                        hideSuggestions(suggestionsBox);
                    }
                });
        }

        searchInput.addEventListener('input', function() {
            hiddenInput.value = '';
            var query = searchInput.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 1) {
                hideSuggestions(suggestionsBox);
                if (typeof onSelect === 'function') {
                    onSelect('');
                }
                return;
            }

            debounceTimer = setTimeout(function() {
                fetchSuggestions(query);
            }, 200);
        });

        searchInput.addEventListener('focus', function() {
            var query = searchInput.value.trim();
            if (query.length >= 1 && !hiddenInput.value) {
                fetchSuggestions(query);
            }
        });

        searchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideSuggestions(suggestionsBox);
            }
        });
    }

    document.addEventListener('click', function(event) {
        if (!event.target.closest('.position-relative')) {
            hideAllAgentSuggestions();
        }
    });

    function pontsUrl(agentId, excludeCommisId) {
        var url = pontsUrlTemplate.replace('__AGENT__', agentId);
        if (excludeCommisId) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'exclude_commis=' + excludeCommisId;
        }
        return url;
    }

    function loadPonts(agentId, pontSelect, selectedPontId, excludeCommisId) {
        pontSelect.innerHTML = '<option value="">Chargement...</option>';
        pontSelect.disabled = true;

        if (!agentId) {
            pontSelect.innerHTML = '<option value="">— Sélectionnez d’abord un agent —</option>';
            return;
        }

        fetch(pontsUrl(agentId, excludeCommisId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(r) { return r.json(); })
            .then(function(ponts) {
                pontSelect.innerHTML = '';
                if (!ponts.length) {
                    pontSelect.innerHTML = '<option value="">Aucun pont disponible pour cet agent</option>';
                    return;
                }
                ponts.forEach(function(p) {
                    var opt = document.createElement('option');
                    opt.value = p.id_pont;
                    opt.textContent = p.label;
                    if (selectedPontId && String(p.id_pont) === String(selectedPontId)) {
                        opt.selected = true;
                    }
                    pontSelect.appendChild(opt);
                });
                pontSelect.disabled = false;
            })
            .catch(function() {
                pontSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    var addAgentSearch = document.getElementById('add_agent_search');
    var addAgentId = document.getElementById('add_id_agent');
    var addPont = document.getElementById('add_id_pont');

    setupAgentAutocomplete(addAgentSearch, addAgentId, document.getElementById('add_agent_suggestions'), function(agentId) {
        loadPonts(agentId, addPont, @json(old('id_pont')), null);
    });

    if (addAgentId && addAgentId.value && addPont) {
        loadPonts(addAgentId.value, addPont, @json(old('id_pont')), null);
    }

    var editForm = document.getElementById('editCommisForm');
    var editAgentSearch = document.getElementById('edit_agent_search');
    var editAgentId = document.getElementById('edit_id_agent');
    var editPont = document.getElementById('edit_id_pont');
    var editingCommisId = null;

    setupAgentAutocomplete(editAgentSearch, editAgentId, document.getElementById('edit_agent_suggestions'), function(agentId) {
        loadPonts(agentId, editPont, null, editingCommisId);
    });

    setupAgentAutocomplete(
        document.getElementById('filter_agent_search'),
        document.getElementById('filter_id_agent'),
        document.getElementById('filter_agent_suggestions')
    );

    document.querySelectorAll('.btn-edit-commis').forEach(function(btn) {
        btn.addEventListener('click', function() {
            editingCommisId = btn.getAttribute('data-id');
            editForm.action = btn.getAttribute('data-update-url');
            document.getElementById('edit_nom').value = btn.getAttribute('data-nom');
            document.getElementById('edit_prenoms').value = btn.getAttribute('data-prenoms');
            document.getElementById('edit_contact').value = btn.getAttribute('data-contact');
            document.getElementById('edit_code_pin').value = btn.getAttribute('data-code-pin') || '';
            editAgentId.value = btn.getAttribute('data-id-agent');
            editAgentSearch.value = btn.getAttribute('data-agent-label') || '';
            loadPonts(editAgentId.value, editPont, btn.getAttribute('data-id-pont'), editingCommisId);
        });
    });

    document.querySelectorAll('[data-bs-target="#deleteCommisModal"]').forEach(function(btn) {
        if (!btn.classList.contains('btn-outline-danger')) return;
        btn.addEventListener('click', function() {
            document.getElementById('deleteCommisForm').action = btn.getAttribute('data-delete-url');
            document.getElementById('deleteCommisName').textContent = btn.getAttribute('data-name');
        });
    });

    [document.querySelector('#addCommisModal form'), editForm].forEach(function(form) {
        if (!form) return;
        form.addEventListener('submit', function(event) {
            var hiddenAgent = form.querySelector('input[name="id_agent"]');
            if (hiddenAgent && !hiddenAgent.value) {
                event.preventDefault();
                alert('Veuillez sélectionner un agent dans la liste de suggestions.');
            }
        });
    });

    @if ($errors->any() && old('_token'))
        var addModal = document.getElementById('addCommisModal');
        if (addModal) {
            new bootstrap.Modal(addModal).show();
        }
    @endif
})();
</script>
@endpush
