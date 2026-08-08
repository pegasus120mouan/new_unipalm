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
                        <span class="text-muted ms-2">(cliquez sur un champ modifiable — enregistrement automatique à la sortie du champ ou avec Entrée — impossible si payé)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">{{ $tickets->total() }} ticket(s)</span>
                        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Tous les tickets
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    @include('tickets.partials.modifications-table', [
                        'emptyMessage' => 'Aucun ticket trouvé.',
                    ])
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="ticketModifiedModal" tabindex="-1" aria-labelledby="ticketModifiedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title" id="ticketModifiedModalLabel">
                        <i class="bi bi-check-circle me-2"></i>Modification effectuée
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body py-4 text-center">
                    <p class="mb-0 fs-5">Les modifications du ticket ont été enregistrées.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .ticket-editable-cell {
            cursor: pointer;
        }
        .ticket-editable-cell:hover {
            background-color: rgba(67, 94, 190, 0.08);
        }
        .ticket-editable-cell.is-editing {
            background-color: rgba(67, 94, 190, 0.12);
            padding: 0.25rem;
        }
        .ticket-editable-cell .form-control,
        .ticket-editable-cell .form-select {
            min-width: 8rem;
        }
        .ticket-autocomplete-editor .list-group {
            min-width: 10rem;
        }
        tr.ticket-row-readonly td {
            cursor: not-allowed;
        }
        tr.ticket-row-readonly:hover {
            background-color: rgba(108, 117, 125, 0.06);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const USINES = @json($usinesForAutocomplete);
            const AGENTS = @json($agentsForAutocomplete);
            const VEHICULES = @json($vehiculesForAutocomplete);
            const AGENTS_PONTS = @json($agentsPontsMap ?? new \stdClass());
            const UPDATE_BASE = @json(url('/tickets'));
            const CSRF = @json(csrf_token());
            const successModalEl = document.getElementById('ticketModifiedModal');
            const successModal = successModalEl ? new bootstrap.Modal(successModalEl) : null;

            let activeCell = null;

            function buildInlineAutocomplete(config) {
                const wrap = document.createElement('div');
                wrap.className = 'ticket-cell-editor ticket-autocomplete-editor position-relative';

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.value = config.selectedId || '';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = config.selectedLabel || '';
                input.placeholder = config.placeholder || 'Rechercher...';
                input.autocomplete = 'off';

                const suggestions = document.createElement('div');
                suggestions.className = 'list-group position-absolute w-100 shadow-sm';
                suggestions.style.cssText = 'z-index: 1050; display: none; max-height: 180px; overflow-y: auto; top: 100%;';

                function selectItem(item) {
                    hidden.value = String(item.id);
                    input.value = config.getLabel(item);
                    suggestions.style.display = 'none';
                }

                function renderSuggestions(term) {
                    const search = term.trim().toLowerCase();
                    suggestions.innerHTML = '';

                    if (!search) {
                        suggestions.style.display = 'none';
                        return;
                    }

                    const matches = config.items.filter(function (item) {
                        return config.filter(item, search);
                    }).slice(0, 15);

                    if (!matches.length) {
                        suggestions.innerHTML = '<div class="list-group-item text-muted small">Aucun résultat</div>';
                        suggestions.style.display = 'block';
                        hidden.value = '';
                        return;
                    }

                    matches.forEach(function (item) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action py-1 small';
                        button.textContent = config.getLabel(item);
                        button.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            selectItem(item);
                        });
                        suggestions.appendChild(button);
                    });

                    suggestions.style.display = 'block';

                    const exact = matches.find(function (item) {
                        return config.getLabel(item).toLowerCase() === search;
                    });
                    if (exact) {
                        hidden.value = String(exact.id);
                    }
                }

                input.addEventListener('input', function () {
                    hidden.value = '';
                    renderSuggestions(input.value);
                });

                input.addEventListener('focus', function () {
                    if (input.value.trim()) {
                        renderSuggestions(input.value);
                    }
                });

                wrap.appendChild(hidden);
                wrap.appendChild(input);
                wrap.appendChild(suggestions);

                wrap.getEditorValue = function () {
                    return hidden.value;
                };

                wrap.focusEditor = function () {
                    input.focus();
                    input.select();
                };

                wrap.getInput = function () {
                    return input;
                };

                return wrap;
            }

            function editorValue(editor) {
                if (typeof editor.getEditorValue === 'function') {
                    return editor.getEditorValue();
                }

                return editor.value;
            }

            function buildSelect(options, value, emptyLabel) {
                const select = document.createElement('select');
                select.className = 'form-select form-select-sm';
                if (emptyLabel !== undefined) {
                    const empty = document.createElement('option');
                    empty.value = '';
                    empty.textContent = emptyLabel;
                    select.appendChild(empty);
                }
                options.forEach(function (opt) {
                    const option = document.createElement('option');
                    option.value = String(opt.id);
                    option.textContent = opt.label;
                    if (String(opt.id) === String(value)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                return select;
            }

            function pontsForAgent(agentId) {
                return AGENTS_PONTS[String(agentId)] || AGENTS_PONTS[agentId] || [];
            }

            function syncPontCellForAgent(row, agentId) {
                const pontCell = row.querySelector('[data-field="id_pont"]');
                if (!pontCell) {
                    return;
                }
                const ponts = pontsForAgent(agentId);
                const current = String(pontCell.dataset.value || '');
                const stillValid = ponts.some(function (p) { return String(p.id) === current; });

                if (ponts.length === 1) {
                    pontCell.dataset.value = String(ponts[0].id);
                    pontCell.dataset.label = ponts[0].nom || ponts[0].label;
                    const display = pontCell.querySelector('.ticket-cell-display');
                    if (display) {
                        display.textContent = ponts[0].nom || ponts[0].label;
                    }
                    return;
                }

                if (!stillValid) {
                    pontCell.dataset.value = '';
                    pontCell.dataset.label = '';
                    const display = pontCell.querySelector('.ticket-cell-display');
                    if (display) {
                        display.textContent = '—';
                    }
                }
            }

            function buildInput(type, value, step) {
                const input = document.createElement('input');
                input.type = type;
                input.className = 'form-control form-control-sm';
                input.value = value ?? '';
                if (step) {
                    input.step = step;
                }
                if (type === 'number') {
                    input.min = '0';
                }
                return input;
            }

            function cancelEdit(cell) {
                if (!cell || !cell.classList.contains('is-editing')) {
                    return;
                }
                delete cell.dataset.editSnapshot;
                const editor = cell.querySelector('.ticket-cell-editor');
                const display = cell.querySelector('.ticket-cell-display');
                if (editor) {
                    editor.remove();
                }
                if (display) {
                    display.style.display = '';
                }
                cell.classList.remove('is-editing');
                if (activeCell === cell) {
                    activeCell = null;
                }
            }

            function commitEditor(cell) {
                const field = cell.dataset.field;
                const editor = cell.querySelector('.ticket-cell-editor');
                const display = cell.querySelector('.ticket-cell-display');
                if (!editor || !display) {
                    return { committed: false, changed: false };
                }

                const snapshot = cell.dataset.editSnapshot ?? cell.dataset.value ?? '';
                let value = editorValue(editor);
                let label = display.textContent.trim();

                if (field === 'id_usine') {
                    const found = USINES.find(function (u) { return String(u.id) === String(value); });
                    label = found ? found.label : label;
                    cell.dataset.label = label;
                } else if (field === 'id_agent') {
                    const found = AGENTS.find(function (a) { return String(a.id) === String(value); });
                    label = found ? found.name : label;
                    cell.dataset.label = label;
                    cell.dataset.value = value;
                    display.textContent = label || '—';
                    const row = cell.closest('tr');
                    if (row) {
                        syncPontCellForAgent(row, value);
                    }
                    const changed = String(value) !== String(snapshot);
                    delete cell.dataset.editSnapshot;
                    editor.remove();
                    display.style.display = '';
                    cell.classList.remove('is-editing');
                    activeCell = null;
                    return { committed: true, changed: changed };
                } else if (field === 'id_pont') {
                    const row = cell.closest('tr');
                    const agentCell = row ? row.querySelector('[data-field="id_agent"]') : null;
                    const agentId = agentCell ? agentCell.dataset.value : '';
                    const ponts = pontsForAgent(agentId);
                    if (ponts.length > 0 && !value) {
                        alert('Veuillez sélectionner un pont-bascule pour cet agent.');
                        return { committed: false, changed: false };
                    }
                    const found = ponts.find(function (p) { return String(p.id) === String(value); });
                    label = found ? (found.nom || found.label) : (value ? label : '—');
                    cell.dataset.label = found ? (found.nom || found.label) : '';
                } else if (field === 'vehicule_id') {
                    const found = VEHICULES.find(function (v) { return String(v.id) === String(value); });
                    label = found ? found.label : (cell.dataset.label || label);
                    cell.dataset.label = label;
                    if (!value) {
                        alert('Veuillez sélectionner un véhicule dans la liste.');
                        if (typeof editor.focusEditor === 'function') {
                            editor.focusEditor();
                        }
                        return { committed: false, changed: false };
                    }
                } else if (field === 'prix_unitaire') {
                    if (value === '' || parseFloat(value) <= 0) {
                        display.innerHTML = '<span class="badge bg-warning ticket-prix-badge">En attente</span>';
                        cell.dataset.value = '';
                    } else {
                        display.textContent = Number(value).toLocaleString('fr-FR');
                        cell.dataset.value = value;
                    }
                    const changed = String(cell.dataset.value) !== String(snapshot);
                    delete cell.dataset.editSnapshot;
                    editor.remove();
                    display.style.display = '';
                    cell.classList.remove('is-editing');
                    activeCell = null;
                    return { committed: true, changed: changed };
                } else if (field === 'date_ticket' || field === 'created_at') {
                    if (value) {
                        const parts = value.split('-');
                        label = parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
                    }
                } else if (field === 'poids') {
                    label = value ? Number(value).toLocaleString('fr-FR') : '—';
                }

                cell.dataset.value = value;
                if (field !== 'prix_unitaire') {
                    display.textContent = label || '—';
                }
                const changed = String(cell.dataset.value) !== String(snapshot);
                delete cell.dataset.editSnapshot;
                editor.remove();
                display.style.display = '';
                cell.classList.remove('is-editing');
                activeCell = null;
                return { committed: true, changed: changed };
            }

            function maybeSaveRow(row, commitResult) {
                if (commitResult && commitResult.committed && commitResult.changed) {
                    saveRow(row);
                }
            }

            function activateEditor(cell) {
                if (cell.dataset.editable === undefined && cell.closest('tr').dataset.editable !== '1') {
                    return;
                }
                const row = cell.closest('tr');
                if (!row || row.dataset.editable !== '1') {
                    return;
                }
                if (activeCell && activeCell !== cell) {
                    const prevRow = activeCell.closest('tr');
                    const commitResult = commitEditor(activeCell);
                    if (prevRow) {
                        maybeSaveRow(prevRow, commitResult);
                    }
                }
                if (cell.classList.contains('is-editing')) {
                    return;
                }

                const field = cell.dataset.field;
                const value = cell.dataset.value || '';
                cell.dataset.editSnapshot = value;
                const display = cell.querySelector('.ticket-cell-display');
                display.style.display = 'none';
                cell.classList.add('is-editing');

                let editor;
                if (field === 'id_usine') {
                    editor = buildSelect(USINES, value);
                } else if (field === 'id_agent') {
                    editor = buildSelect(AGENTS.map(function (a) { return { id: a.id, label: a.name }; }), value);
                } else if (field === 'id_pont') {
                    const agentCell = row.querySelector('[data-field="id_agent"]');
                    const agentId = agentCell ? agentCell.dataset.value : '';
                    const ponts = pontsForAgent(agentId).map(function (p) {
                        return { id: p.id, label: p.label || p.nom };
                    });
                    if (!ponts.length) {
                        display.style.display = '';
                        cell.classList.remove('is-editing');
                        delete cell.dataset.editSnapshot;
                        alert('Aucun pont-bascule associé à cet agent.');
                        return;
                    }
                    editor = buildSelect(ponts, value, '— Sélectionner un pont —');
                } else if (field === 'vehicule_id') {
                    editor = buildInlineAutocomplete({
                        items: VEHICULES,
                        selectedId: value,
                        selectedLabel: cell.dataset.label || display.textContent.trim(),
                        placeholder: 'Rechercher un véhicule...',
                        filter: function (item, term) {
                            return item.label.toLowerCase().includes(term);
                        },
                        getLabel: function (item) {
                            return item.label;
                        },
                    });
                } else if (field === 'poids') {
                    editor = buildInput('number', value, '1');
                } else if (field === 'prix_unitaire') {
                    editor = buildInput('number', value, '0.01');
                } else if (field === 'date_ticket' || field === 'created_at') {
                    editor = buildInput('date', value);
                } else {
                    display.style.display = '';
                    cell.classList.remove('is-editing');
                    return;
                }

                editor.classList.add('ticket-cell-editor');
                cell.appendChild(editor);
                if (typeof editor.focusEditor === 'function') {
                    editor.focusEditor();
                } else {
                    editor.focus();
                }
                activeCell = cell;

                const keyTarget = typeof editor.getInput === 'function' ? editor.getInput() : editor;
                keyTarget.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveRow(row, cell);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        cancelEdit(cell);
                    }
                });

                if (editor.tagName === 'SELECT') {
                    editor.addEventListener('change', function () {
                        editor.dataset.changed = '1';
                    });
                }
            }

            function collectRowPayload(row) {
                if (activeCell && activeCell.closest('tr') === row) {
                    const commitResult = commitEditor(activeCell);
                    if (!commitResult.committed) {
                        return null;
                    }
                }

                function cellValue(field) {
                    const cell = row.querySelector('[data-field="' + field + '"]');
                    return cell ? cell.dataset.value : '';
                }

                return {
                    date_ticket: cellValue('date_ticket'),
                    numero_ticket: row.dataset.numeroTicket,
                    id_usine: parseInt(cellValue('id_usine'), 10),
                    id_agent: parseInt(cellValue('id_agent'), 10),
                    id_pont: cellValue('id_pont') ? parseInt(cellValue('id_pont'), 10) : null,
                    vehicule_id: parseInt(cellValue('vehicule_id'), 10),
                    poids: cellValue('poids'),
                    prix_unitaire: cellValue('prix_unitaire') || null,
                    created_at: cellValue('created_at') || null,
                };
            }

            function updateRowFromResponse(row, ticket) {
                const map = {
                    date_ticket: ['date_ticket', 'date_ticket_display'],
                    id_usine: ['id_usine', 'usine_name'],
                    id_agent: ['id_agent', 'agent_name'],
                    id_pont: ['id_pont', 'pont_name'],
                    vehicule_id: ['vehicule_id', 'vehicule_label'],
                    poids: ['poids', 'poids_display'],
                    created_at: ['created_at', 'created_at_display'],
                    prix_unitaire: ['prix_unitaire', 'prix_unitaire_display'],
                };

                Object.keys(map).forEach(function (field) {
                    const cell = row.querySelector('[data-field="' + field + '"]');
                    if (!cell) {
                        return;
                    }
                    const valKey = map[field][0];
                    const displayKey = map[field][1];
                    cell.dataset.value = ticket[valKey] ?? '';
                    if (field === 'id_usine' || field === 'id_agent' || field === 'vehicule_id' || field === 'id_pont') {
                        cell.dataset.label = ticket[displayKey] ?? '';
                    }
                    const display = cell.querySelector('.ticket-cell-display');
                    if (!display) {
                        return;
                    }
                    if (field === 'prix_unitaire') {
                        if (ticket.prix_unitaire_display) {
                            display.textContent = ticket.prix_unitaire_display;
                        } else {
                            display.innerHTML = '<span class="badge bg-warning ticket-prix-badge">En attente</span>';
                        }
                    } else {
                        display.textContent = ticket[displayKey] ?? '—';
                    }
                });

                const montantCell = row.querySelector('.ticket-montant-cell');
                if (montantCell && ticket.montant_display) {
                    montantCell.textContent = ticket.montant_display;
                }
            }

            function saveRow(row, fromCell) {
                if (row.dataset.editable !== '1') {
                    return;
                }
                if (fromCell && fromCell.classList.contains('is-editing')) {
                    const commitResult = commitEditor(fromCell);
                    if (!commitResult.committed) {
                        return;
                    }
                }

                const payload = collectRowPayload(row);
                if (!payload) {
                    return;
                }
                const ticketId = row.dataset.ticketId;

                fetch(UPDATE_BASE + '/' + ticketId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                })
                    .then(function (r) {
                        return r.json().then(function (body) {
                            return { ok: r.ok, status: r.status, body: body };
                        }).catch(function () {
                            return { ok: false, status: r.status, body: {} };
                        });
                    })
                    .then(function (res) {
                        if (!res.ok || !res.body.ok) {
                            if (res.status === 403) {
                                alert('Vous n\'avez pas la permission de modifier ce ticket.');
                                return;
                            }
                            if (res.status === 422 && res.body.errors) {
                                const firstError = Object.values(res.body.errors).flat()[0];
                                alert(firstError || 'Données invalides.');
                                return;
                            }
                            alert(res.body.message || 'Impossible d\'enregistrer les modifications.');
                            return;
                        }
                        updateRowFromResponse(row, res.body.ticket);
                        if (successModal) {
                            successModal.show();
                        }
                    })
                    .catch(function () {
                        alert('Erreur réseau lors de l\'enregistrement.');
                    });
            }

            document.querySelectorAll('.ticket-editable-cell').forEach(function (cell) {
                cell.addEventListener('click', function () {
                    activateEditor(cell);
                });
            });

            document.addEventListener('click', function (e) {
                if (!activeCell) {
                    return;
                }
                if (!activeCell.contains(e.target)) {
                    const row = activeCell.closest('tr');
                    const commitResult = commitEditor(activeCell);
                    if (row) {
                        maybeSaveRow(row, commitResult);
                    }
                }
            });
        });
    </script>
@endsection
