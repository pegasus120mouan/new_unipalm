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
                        <span class="text-muted ms-2">(cliquez sur un champ modifiable, puis Entrée pour enregistrer — impossible si payé)</span>
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
            const UPDATE_BASE = @json(url('/tickets'));
            const CSRF = @json(csrf_token());
            const successModalEl = document.getElementById('ticketModifiedModal');
            const successModal = successModalEl ? new bootstrap.Modal(successModalEl) : null;

            let activeCell = null;

            function buildSelect(options, value) {
                const select = document.createElement('select');
                select.className = 'form-select form-select-sm';
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
                    return;
                }

                let value = editor.value;
                let label = display.textContent.trim();

                if (field === 'id_usine') {
                    const found = USINES.find(function (u) { return String(u.id) === String(value); });
                    label = found ? found.label : label;
                    cell.dataset.label = label;
                } else if (field === 'id_agent') {
                    const found = AGENTS.find(function (a) { return String(a.id) === String(value); });
                    label = found ? found.name : label;
                    cell.dataset.label = label;
                } else if (field === 'vehicule_id') {
                    const found = VEHICULES.find(function (v) { return String(v.id) === String(value); });
                    label = found ? found.label : label;
                    cell.dataset.label = label;
                } else if (field === 'prix_unitaire') {
                    if (value === '' || parseFloat(value) <= 0) {
                        display.innerHTML = '<span class="badge bg-warning ticket-prix-badge">En attente</span>';
                        cell.dataset.value = '';
                    } else {
                        display.textContent = Number(value).toLocaleString('fr-FR');
                        cell.dataset.value = value;
                    }
                    editor.remove();
                    display.style.display = '';
                    cell.classList.remove('is-editing');
                    activeCell = null;
                    return;
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
                editor.remove();
                display.style.display = '';
                cell.classList.remove('is-editing');
                activeCell = null;
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
                    commitEditor(activeCell);
                }
                if (cell.classList.contains('is-editing')) {
                    return;
                }

                const field = cell.dataset.field;
                const value = cell.dataset.value || '';
                const display = cell.querySelector('.ticket-cell-display');
                display.style.display = 'none';
                cell.classList.add('is-editing');

                let editor;
                if (field === 'id_usine') {
                    editor = buildSelect(USINES, value);
                } else if (field === 'id_agent') {
                    editor = buildSelect(AGENTS.map(function (a) { return { id: a.id, label: a.name }; }), value);
                } else if (field === 'vehicule_id') {
                    editor = buildSelect(VEHICULES, value);
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
                editor.focus();
                activeCell = cell;

                editor.addEventListener('keydown', function (e) {
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
                    commitEditor(activeCell);
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
                    if (field === 'id_usine' || field === 'id_agent' || field === 'vehicule_id') {
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
                    commitEditor(fromCell);
                }

                const payload = collectRowPayload(row);
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
                            return { ok: r.ok, body: body };
                        });
                    })
                    .then(function (res) {
                        if (!res.ok || !res.body.ok) {
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
                    commitEditor(activeCell);
                }
            });
        });
    </script>
@endsection
