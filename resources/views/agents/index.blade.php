@extends('layout.main')

@section('title', 'Agents')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Agents</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Agents</li>
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

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                            <i class="bi bi-person-plus-fill"></i> Enregistrer un agent
                        </button>
                        <button type="button" class="btn btn-danger" disabled title="Bientôt disponible">
                            <i class="bi bi-printer-fill"></i> Imprimer un agent
                        </button>
                        <a href="#agent-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher un agent
                        </a>
                        <button type="button" class="btn btn-warning" disabled title="Bientôt disponible">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i> Exporter la liste
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="agent-filters">
        <div class="col-12">
            <div class="card border-0 shadow-sm agents-filters-card">
                <div class="card-header agents-filters-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold">
                        <i class="bi bi-funnel-fill me-2"></i>Filtres de recherche
                    </span>
                    @if ($hasFilters)
                        <span class="badge bg-white text-dark">
                            {{ $agents->total() }} résultat(s)
                        </span>
                    @endif
                </div>
                <div class="card-body agents-filters-body">
                    <form method="GET" action="{{ route('agents.index') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6 col-xl-4">
                                <label for="search_agent" class="form-label agents-filter-label">Agent</label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-search"></i></span>
                                        <input type="text" name="search" id="search_agent" class="form-control agent-search-autocomplete"
                                            autocomplete="off"
                                            placeholder="Nom, prénom ou N° agent..."
                                            value="{{ $searchDisplay }}">
                                        <input type="hidden" name="id_agent" id="id_agent" value="{{ $filters['id_agent'] ?? '' }}">
                                    </div>
                                    <div class="agent-filter-suggestions list-group shadow-sm" id="search_agent_suggestions" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label for="search_contact" class="form-label agents-filter-label">Contact</label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input type="text" name="search_contact" id="search_contact" class="form-control agent-filter-autocomplete"
                                            data-field="contact" autocomplete="off"
                                            placeholder="Rechercher par contact..." value="{{ $filters['search_contact'] }}">
                                    </div>
                                    <div class="agent-filter-suggestions list-group shadow-sm" id="search_contact_suggestions" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label for="search_groupe" class="form-label agents-filter-label">Groupe</label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-people"></i></span>
                                        <input type="text" name="search_groupe" id="search_groupe" class="form-control agent-filter-autocomplete"
                                            data-field="groupe" autocomplete="off"
                                            placeholder="Rechercher par groupe..." value="{{ $filters['search_groupe'] }}">
                                    </div>
                                    <div class="agent-filter-suggestions list-group shadow-sm" id="search_groupe_suggestions" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-2 justify-content-end pt-1">
                                    <button type="submit" class="btn btn-dark px-4">
                                        <i class="bi bi-search"></i> Rechercher
                                    </button>
                                    <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    @if ($hasFilters)
                        <div class="agents-active-filters mt-3">
                            <span class="small text-muted me-2">Filtres actifs :</span>
                            @if ($filters['search'] !== '' || $filters['id_agent'])
                                <span class="badge rounded-pill text-bg-light border">Agent : {{ $searchDisplay }}</span>
                            @endif
                            @if ($filters['search_contact'] !== '')
                                <span class="badge rounded-pill text-bg-light border">Contact : {{ $filters['search_contact'] }}</span>
                            @endif
                            @if ($filters['search_groupe'] !== '')
                                <span class="badge rounded-pill text-bg-light border">Groupe : {{ $filters['search_groupe'] }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold">Liste des agents</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                        <small class="text-muted"><i class="bi bi-pencil-square"></i> Cliquez sur Nom, Prénom ou Contact puis Entrée pour enregistrer</small>
                        <span class="badge bg-dark">{{ $agents->total() }} agent(s)</span>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover align-middle mb-0 agents-data-table">
                        <thead class="agents-table-header">
                            <tr>
                                <th>N° Agent</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Contact</th>
                                <th>Groupe</th>
                                <th class="text-center">Ponts</th>
                                <th>Date de création</th>
                                <th>Ajouté par</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $agent)
                                <tr data-agent-row="{{ $agent->id_agent }}">
                                    <td>
                                        @if ($agent->numero_agent)
                                            <a href="{{ route('agents.show', $agent) }}" class="badge bg-primary text-decoration-none">
                                                {{ $agent->numero_agent }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="agent-editable-cell" tabindex="0"
                                        data-field="nom"
                                        data-url="{{ route('agents.inline-update', $agent) }}"
                                        data-value="{{ $agent->nom }}">
                                        <span class="agent-editable-display">{{ $agent->nom }}</span>
                                    </td>
                                    <td class="agent-editable-cell" tabindex="0"
                                        data-field="prenom"
                                        data-url="{{ route('agents.inline-update', $agent) }}"
                                        data-value="{{ $agent->prenom }}">
                                        <span class="agent-editable-display">{{ $agent->prenom }}</span>
                                    </td>
                                    <td class="agent-editable-cell" tabindex="0"
                                        data-field="contact"
                                        data-url="{{ route('agents.inline-update', $agent) }}"
                                        data-value="{{ $agent->contact }}">
                                        <span class="agent-editable-display">
                                            @if ($agent->contact)
                                                <span class="badge bg-info">{{ $agent->contact }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if ($agent->groupe)
                                            <a href="{{ route('groupes.show', $agent->groupe) }}">
                                                {{ $agent->groupe->full_name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($agent->ponts_count > 0)
                                            <a href="{{ route('agents.show', $agent) }}#agent-ponts"
                                                class="badge bg-secondary text-decoration-none"
                                                title="Voir les ponts associés">
                                                {{ $agent->ponts_count }}
                                            </a>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>{{ $agent->date_ajout?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $agent->createur?->full_name ?? '—' }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('agents.destroy', $agent) }}" class="d-inline form-delete-agent">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"
                                                data-agent-name="{{ $agent->full_name }}"
                                                data-agent-numero="{{ $agent->numero_agent }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Aucun agent trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center p-3">
                        {{ $agents->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addAgentModal" tabindex="-1" aria-labelledby="addAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAgentModalLabel">
                        <i class="bi bi-person-plus-fill"></i> Enregistrer un nouvel agent
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('agents.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom" class="form-label">
                                <i class="bi bi-person"></i> Nom
                            </label>
                            <input type="text" name="nom" id="nom"
                                class="form-control @error('nom') is-invalid @enderror"
                                value="{{ old('nom') }}" placeholder="Nom de l'agent" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="prenom" class="form-label">
                                <i class="bi bi-person"></i> Prénoms
                            </label>
                            <input type="text" name="prenom" id="prenom"
                                class="form-control @error('prenom') is-invalid @enderror"
                                value="{{ old('prenom') }}" placeholder="Prénoms de l'agent" required>
                            @error('prenom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="contact" class="form-label">
                                <i class="bi bi-telephone"></i> Contact
                            </label>
                            <input type="text" name="contact" id="contact"
                                class="form-control @error('contact') is-invalid @enderror"
                                value="{{ old('contact') }}" placeholder="Numéro de téléphone" required>
                            @error('contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="id_chef" class="form-label">
                                <i class="bi bi-people"></i> Groupe
                            </label>
                            <select name="id_chef" id="id_chef"
                                class="form-select @error('id_chef') is-invalid @enderror" required>
                                <option value="">Sélectionner un groupe</option>
                                @foreach ($groupes as $groupe)
                                    <option value="{{ $groupe->id_chef }}" @selected(old('id_chef') == $groupe->id_chef)>
                                        {{ $groupe->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_chef')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="agentInlineSuccessModal" tabindex="-1" aria-labelledby="agentInlineSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-4 px-4">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 56px; height: 56px;">
                        <i class="bi bi-check-lg fs-2"></i>
                    </div>
                    <h5 class="mb-2" id="agentInlineSuccessModalLabel">Modification effectuée</h5>
                    <p class="text-muted mb-0 small" id="agentInlineSuccessMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addAgentModal')).show();
            });
        </script>
    @endif
@endsection

@push('scripts')
    <style>
        .agents-filters-card {
            overflow: hidden;
        }

        .agents-filters-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            border-bottom: none;
            padding: 0.9rem 1.25rem;
        }

        .agents-filters-body {
            background: #f8f9fb;
            padding: 1.25rem;
        }

        .agents-filter-label {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6c757d;
            margin-bottom: 0.35rem;
        }

        .agents-filters-body .input-group-text {
            background: #fff;
            color: #495057;
            border-right: 0;
        }

        .agents-filters-body .form-control {
            border-left: 0;
            background: #fff;
        }

        .agents-filters-body .input-group:focus-within .input-group-text,
        .agents-filters-body .input-group:focus-within .form-control {
            border-color: #86b7fe;
            box-shadow: none;
        }

        .agents-active-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px dashed #dee2e6;
        }

        .agents-table-header {
            background: #111;
        }

        .agents-table-header th {
            color: #fff !important;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            padding: 0.95rem 1rem;
            white-space: nowrap;
        }

        .agents-data-table tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
        }

        .agents-data-table tbody tr:hover {
            background-color: rgba(17, 17, 17, 0.03);
        }

        .agent-editable-cell {
            cursor: text;
            min-width: 7rem;
        }

        .agent-editable-cell:hover:not(.is-editing) {
            background-color: rgba(67, 94, 190, 0.06);
            outline: 1px dashed rgba(67, 94, 190, 0.25);
        }

        .agent-editable-cell.is-editing {
            padding: 0.25rem;
        }

        .agent-editable-cell.is-saving {
            opacity: 0.6;
        }

        .agent-editable-input {
            width: 100%;
            min-width: 6rem;
        }

        .agent-filter-suggestions {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            right: 0;
            z-index: 1050;
            max-height: 220px;
            overflow-y: auto;
            border-radius: 0.375rem;
        }

        .agent-filter-suggestions .list-group-item {
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0.55rem 0.85rem;
            border-left: none;
            border-right: none;
        }

        .agent-filter-suggestions .list-group-item:first-child {
            border-top: none;
        }

        .agent-filter-suggestions .list-group-item-action:hover,
        .agent-filter-suggestions .list-group-item-action.active {
            background-color: rgba(67, 94, 190, 0.1);
            color: #1e2d4d;
        }

        .agent-suggestion-numero {
            color: #6c757d;
            font-size: 0.82rem;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const autocompleteUrl = @json(route('agents.autocomplete'));
            let debounceTimer = null;
            let fetchController = null;

            function hideSuggestions(box) {
                if (!box) {
                    return;
                }
                box.style.display = 'none';
                box.innerHTML = '';
            }

            function hideAllSuggestions() {
                document.querySelectorAll('.agent-filter-suggestions').forEach(hideSuggestions);
            }

            function setupAgentSearchAutocomplete() {
                const input = document.getElementById('search_agent');
                const hiddenId = document.getElementById('id_agent');
                const suggestionsBox = document.getElementById('search_agent_suggestions');

                if (!input || !hiddenId || !suggestionsBox) {
                    return;
                }

                function renderAgentSuggestions(items) {
                    suggestionsBox.innerHTML = '';

                    if (!items.length) {
                        suggestionsBox.innerHTML = '<div class="list-group-item text-muted small">Aucun agent trouvé</div>';
                        suggestionsBox.style.display = 'block';
                        return;
                    }

                    items.forEach(function (item) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action text-start';

                        if (item.numero) {
                            button.innerHTML = '<span class="agent-suggestion-numero">' + escapeHtml(item.numero) + '</span> — <strong>' + escapeHtml(item.name) + '</strong>';
                        } else {
                            button.innerHTML = '<strong>' + escapeHtml(item.name) + '</strong>';
                        }

                        button.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            input.value = item.label;
                            hiddenId.value = item.id;
                            hideSuggestions(suggestionsBox);
                        });
                        suggestionsBox.appendChild(button);
                    });

                    suggestionsBox.style.display = 'block';
                }

                async function fetchAgentSuggestions(query) {
                    if (fetchController) {
                        fetchController.abort();
                    }

                    fetchController = new AbortController();

                    try {
                        const url = new URL(autocompleteUrl, window.location.origin);
                        url.searchParams.set('field', 'agent');
                        url.searchParams.set('q', query);

                        const response = await fetch(url.toString(), {
                            headers: { Accept: 'application/json' },
                            signal: fetchController.signal,
                        });

                        if (!response.ok) {
                            return;
                        }

                        const items = await response.json();
                        renderAgentSuggestions(Array.isArray(items) ? items : []);
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            hideSuggestions(suggestionsBox);
                        }
                    }
                }

                input.addEventListener('input', function () {
                    hiddenId.value = '';
                    const query = input.value.trim();
                    clearTimeout(debounceTimer);

                    if (query.length < 1) {
                        hideSuggestions(suggestionsBox);
                        return;
                    }

                    debounceTimer = setTimeout(function () {
                        fetchAgentSuggestions(query);
                    }, 200);
                });

                input.addEventListener('focus', function () {
                    const query = input.value.trim();
                    if (query.length >= 1 && !hiddenId.value) {
                        fetchAgentSuggestions(query);
                    }
                });

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        hideSuggestions(suggestionsBox);
                    }
                });
            }

            function setupFilterAutocomplete(input) {
                const field = input.dataset.field;
                const suggestionsBox = input.closest('.position-relative')?.querySelector('.agent-filter-suggestions');

                if (!field || !suggestionsBox) {
                    return;
                }

                function renderSuggestions(items) {
                    suggestionsBox.innerHTML = '';

                    if (!items.length) {
                        suggestionsBox.innerHTML = '<div class="list-group-item text-muted small">Aucune suggestion</div>';
                        suggestionsBox.style.display = 'block';
                        return;
                    }

                    items.forEach(function (item) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action';
                        button.textContent = item;
                        button.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            input.value = item;
                            hideSuggestions(suggestionsBox);
                        });
                        suggestionsBox.appendChild(button);
                    });

                    suggestionsBox.style.display = 'block';
                }

                async function fetchSuggestions(query) {
                    if (fetchController) {
                        fetchController.abort();
                    }

                    fetchController = new AbortController();

                    try {
                        const url = new URL(autocompleteUrl, window.location.origin);
                        url.searchParams.set('field', field);
                        url.searchParams.set('q', query);

                        const response = await fetch(url.toString(), {
                            headers: { Accept: 'application/json' },
                            signal: fetchController.signal,
                        });

                        if (!response.ok) {
                            return;
                        }

                        const items = await response.json();
                        renderSuggestions(Array.isArray(items) ? items : []);
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            hideSuggestions(suggestionsBox);
                        }
                    }
                }

                input.addEventListener('input', function () {
                    const query = input.value.trim();
                    clearTimeout(debounceTimer);

                    if (query.length < 1) {
                        hideSuggestions(suggestionsBox);
                        return;
                    }

                    debounceTimer = setTimeout(function () {
                        fetchSuggestions(query);
                    }, 200);
                });

                input.addEventListener('focus', function () {
                    const query = input.value.trim();
                    if (query.length >= 1) {
                        fetchSuggestions(query);
                    }
                });

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        hideSuggestions(suggestionsBox);
                    }
                });
            }

            function escapeHtml(text) {
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            setupAgentSearchAutocomplete();
            document.querySelectorAll('.agent-filter-autocomplete').forEach(setupFilterAutocomplete);

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.position-relative')) {
                    hideAllSuggestions();
                }
            });

            const csrfToken = @json(csrf_token());
            const fieldLabels = { nom: 'Nom', prenom: 'Prénom', contact: 'Contact' };
            const successModalEl = document.getElementById('agentInlineSuccessModal');
            const successModal = successModalEl && typeof bootstrap !== 'undefined'
                ? (bootstrap.Modal.getInstance(successModalEl) || new bootstrap.Modal(successModalEl))
                : null;

            function showSuccessModal(field, value) {
                const label = fieldLabels[field] || field;
                const messageEl = document.getElementById('agentInlineSuccessMessage');

                if (messageEl) {
                    messageEl.textContent = `${label} enregistré : ${value}`;
                }

                successModal?.show();
            }

            function displayValue(field, value) {
                if (field === 'contact') {
                    if (!value) {
                        return '<span class="text-muted">—</span>';
                    }

                    return `<span class="badge bg-info">${escapeHtml(value)}</span>`;
                }

                return escapeHtml(value);
            }

            function finishEdit(cell, value, field) {
                cell.classList.remove('is-editing', 'is-saving');
                cell.dataset.value = value;
                cell.innerHTML = `<span class="agent-editable-display">${displayValue(field, value)}</span>`;
            }

            function cancelEdit(cell) {
                finishEdit(cell, cell.dataset.value || '', cell.dataset.field);
            }

            async function saveEdit(cell, input) {
                const field = cell.dataset.field;
                const url = cell.dataset.url;
                const original = (cell.dataset.value || '').trim();
                const value = input.value.trim();

                if (value === '') {
                    alert('Ce champ ne peut pas être vide.');
                    input.focus();
                    return;
                }

                if (value === original) {
                    cancelEdit(cell);
                    return;
                }

                cell.classList.add('is-saving');

                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ field, value }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        const message = data.message
                            || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Erreur lors de la mise à jour.');
                        throw new Error(message);
                    }

                    finishEdit(cell, data.value, field);
                    showSuccessModal(field, data.value);

                    const row = cell.closest('tr');
                    const deleteBtn = row?.querySelector('.form-delete-agent button[type="submit"]');
                    if (deleteBtn && (field === 'nom' || field === 'prenom')) {
                        const nom = row.querySelector('[data-field="nom"]')?.dataset.value || '';
                        const prenom = row.querySelector('[data-field="prenom"]')?.dataset.value || '';
                        deleteBtn.dataset.agentName = `${nom} ${prenom}`.trim();
                    }
                } catch (error) {
                    cell.classList.remove('is-saving');
                    alert(error.message || 'Impossible d\'enregistrer la modification.');
                    input.focus();
                }
            }

            function startEdit(cell) {
                if (cell.classList.contains('is-editing')) {
                    return;
                }

                document.querySelectorAll('.agent-editable-cell.is-editing').forEach(cancelEdit);

                const field = cell.dataset.field;
                const value = cell.dataset.value || '';

                cell.classList.add('is-editing');
                cell.innerHTML = `<input type="text" class="form-control form-control-sm agent-editable-input" value="${escapeHtml(value)}">`;

                const input = cell.querySelector('.agent-editable-input');
                input.focus();
                input.select();

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        saveEdit(cell, input);
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault();
                        cancelEdit(cell);
                    }
                });

                input.addEventListener('blur', function () {
                    setTimeout(function () {
                        if (cell.classList.contains('is-editing') && !cell.classList.contains('is-saving')) {
                            cancelEdit(cell);
                        }
                    }, 120);
                });
            }

            document.querySelectorAll('.agent-editable-cell').forEach(function (cell) {
                cell.addEventListener('click', function () {
                    startEdit(cell);
                });

                cell.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        startEdit(cell);
                    }
                });
            });

            document.querySelectorAll('.form-delete-agent').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    const button = form.querySelector('button[type="submit"]');
                    const name = button?.dataset.agentName || 'cet agent';
                    const numero = button?.dataset.agentNumero || '';
                    const label = numero ? `${name} (${numero})` : name;

                    if (!confirm(`Confirmer la suppression de ${label} ?`)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
@endpush
