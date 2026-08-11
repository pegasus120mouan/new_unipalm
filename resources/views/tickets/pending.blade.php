@extends('layout.main')

@section('title', 'Tickets en attente')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Tickets en attente</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tickets en attente</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <div id="pendingAlerts">
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
    </div>

    @php
        $canValidateTickets = auth()->user()->canValidateTickets();
    @endphp

    @include('tickets.partials.search-filters', [
        'searchAction' => route('tickets.pending'),
        'resetAction' => route('tickets.pending'),
        'agentLabel' => 'Chargé de mission',
        'showVehiculeFilter' => true,
    ])

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span>Tickets en attente de validation</span>
                        <span class="text-muted ms-2">(validation non effectuée)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted" id="pendingTicketsTotal">{{ $tickets->total() }} ticket(s)</span>
                        @if ($canValidateTickets)
                        <button type="button" class="btn btn-sm btn-success" id="bulkValidateBtn" disabled
                            data-bs-toggle="modal" data-bs-target="#bulkValidateModal">
                            <i class="bi bi-check2-all"></i> Valider la sélection
                        </button>
                        @endif
                        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Tous les tickets
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive" id="pendingTicketsWrapper">
                    @include('tickets.partials.table', [
                        'emptyMessage' => 'Aucun ticket en attente.',
                        'showValidateAction' => $canValidateTickets,
                        'showBulkSelection' => $canValidateTickets,
                        'compactView' => true,
                    ])
                </div>
            </div>
        </div>
    </section>

    @if ($canValidateTickets)
    <div class="modal fade" id="validateTicketModal" tabindex="-1" aria-labelledby="validateTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="validateTicketModalLabel">Ajouter le prix unitaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="" id="validateTicketForm">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3" id="validateTicketInfo"></p>
                        <div class="mb-3">
                            <label for="prix_unitaire" class="form-label">Prix unitaire</label>
                            <input type="number" name="prix_unitaire" id="prix_unitaire"
                                class="form-control @error('prix_unitaire') is-invalid @enderror"
                                step="0.01" min="0.01" placeholder="0.00" required
                                value="{{ old('prix_unitaire') }}">
                            <div class="invalid-feedback" id="validateTicketError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="validateTicketSubmit">Valider</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkValidateModal" tabindex="-1" aria-labelledby="bulkValidateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkValidateModalLabel">Saisir le prix unitaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('tickets.validate-bulk') }}" id="bulkValidateForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info py-2" id="bulkTicketCount">
                            0 ticket(s) sélectionné(s)
                        </div>
                        <div class="mb-3">
                            <label for="bulk_prix_unitaire" class="form-label">Prix unitaire (FCFA)</label>
                            <input type="number" name="prix_unitaire" id="bulk_prix_unitaire"
                                class="form-control" step="0.01" min="0.01"
                                placeholder="Entrez le prix unitaire" required>
                            <div class="form-text">Le prix sera appliqué à tous les tickets sélectionnés.</div>
                            <div class="invalid-feedback" id="bulkValidateError"></div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="update_all_usine"
                                id="update_all_usine" value="1">
                            <label class="form-check-label" for="update_all_usine">
                                Appliquer automatiquement ce prix à tous les tickets en attente de la même usine
                            </label>
                            <div class="form-text text-warning">
                                Si coché, tous les tickets non validés de la même usine recevront ce prix unitaire.
                            </div>
                        </div>
                        <div id="bulkTicketIdsContainer"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success" id="bulkValidateSubmit">
                            <i class="bi bi-check-lg"></i> Valider avec ce prix
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if ($canValidateTickets)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('validateTicketForm');
            const prixInput = document.getElementById('prix_unitaire');
            const info = document.getElementById('validateTicketInfo');
            const validateBaseUrl = @json(url('/tickets'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value;

            const selectAll = document.getElementById('selectAllTickets');
            const checkboxes = () => Array.from(document.querySelectorAll('.ticket-checkbox'));
            const bulkValidateBtn = document.getElementById('bulkValidateBtn');
            const bulkTicketCount = document.getElementById('bulkTicketCount');
            const bulkIdsContainer = document.getElementById('bulkTicketIdsContainer');
            const bulkPrixInput = document.getElementById('bulk_prix_unitaire');
            const pendingAlerts = document.getElementById('pendingAlerts');
            const pendingTotal = document.getElementById('pendingTicketsTotal');

            function showAlert(message, type) {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show`;
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>`;
                pendingAlerts.prepend(alert);
            }

            function getSelectedCheckboxes() {
                return checkboxes().filter((checkbox) => checkbox.checked);
            }

            function getSelectedIds() {
                return getSelectedCheckboxes().map((checkbox) => checkbox.value);
            }

            function updateBulkState() {
                const selected = getSelectedCheckboxes();
                const count = selected.length;

                bulkValidateBtn.disabled = count === 0;
                bulkTicketCount.textContent = `${count} ticket(s) sélectionné(s)`;

                bulkIdsContainer.innerHTML = '';
                selected.forEach((checkbox) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ticket_ids[]';
                    input.value = checkbox.value;
                    bulkIdsContainer.appendChild(input);
                });

                if (selectAll) {
                    const selectable = checkboxes();
                    selectAll.checked = selectable.length > 0 && selectable.every((checkbox) => checkbox.checked);
                    selectAll.indeterminate = count > 0 && count < selectable.length;
                }
            }

            function bindSelectionEvents() {
                const selectAllEl = document.getElementById('selectAllTickets');
                if (selectAllEl) {
                    selectAllEl.addEventListener('change', () => {
                        checkboxes().forEach((checkbox) => {
                            checkbox.checked = selectAllEl.checked;
                        });
                        updateBulkState();
                    });
                }

                checkboxes().forEach((checkbox) => {
                    checkbox.addEventListener('change', updateBulkState);
                });

                document.querySelectorAll('.validate-ticket-btn').forEach((button) => {
                    button.addEventListener('click', () => {
                        const ticketId = button.dataset.ticketId;
                        const ticketNumero = button.dataset.ticketNumero;
                        const prixUnitaire = button.dataset.prixUnitaire;

                        form.action = `${validateBaseUrl}/${ticketId}/validate`;
                        info.textContent = `Ticket n° ${ticketNumero}`;
                        prixInput.value = prixUnitaire || '';
                        prixInput.readOnly = prixUnitaire !== '';
                        document.getElementById('validateTicketError').textContent = '';
                        prixInput.classList.remove('is-invalid');
                    });
                });

                updateBulkState();
            }

            function removeValidatedRows(ids) {
                const idSet = new Set(ids.map(String));
                idSet.forEach((id) => {
                    const checkbox = document.querySelector(`.ticket-checkbox[value="${id}"]`);
                    const row = checkbox
                        ? checkbox.closest('tr')
                        : document.querySelector(`.validate-ticket-btn[data-ticket-id="${id}"]`)?.closest('tr');
                    row?.remove();
                });

                if (pendingTotal) {
                    const current = parseInt(pendingTotal.textContent, 10) || 0;
                    const remaining = Math.max(0, current - idSet.size);
                    pendingTotal.textContent = `${remaining} ticket(s)`;
                }

                const tbody = document.querySelector('#pendingTicketsWrapper tbody');
                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                    const colCount = document.querySelectorAll('#pendingTicketsWrapper thead th').length || 14;
                    tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center text-muted py-4">Aucun ticket en attente.</td></tr>`;
                }

                updateBulkState();
            }

            function setSubmitting(button, submitting) {
                if (!button) return;
                button.disabled = submitting;
                button.dataset.originalHtml = button.dataset.originalHtml || button.innerHTML;
                button.innerHTML = submitting
                    ? '<span class="spinner-border spinner-border-sm me-1"></span> Validation…'
                    : button.dataset.originalHtml;
            }

            async function submitValidation(formEl, submitBtn, errorEl, prixEl) {
                const formData = new FormData(formEl);
                setSubmitting(submitBtn, true);
                if (errorEl) errorEl.textContent = '';
                if (prixEl) prixEl.classList.remove('is-invalid');

                try {
                    const response = await fetch(formEl.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const message = data.message
                            || data.errors?.prix_unitaire?.[0]
                            || data.errors?.ticket_ids?.[0]
                            || 'La validation a échoué.';
                        if (prixEl) prixEl.classList.add('is-invalid');
                        if (errorEl) {
                            errorEl.textContent = message;
                            errorEl.style.display = 'block';
                        }
                        showAlert(message, 'danger');
                        return;
                    }

                    const modalEl = formEl.closest('.modal');
                    if (modalEl) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }

                    removeValidatedRows(data.validated_ids || []);
                    showAlert(data.message || 'Validation effectuée.', 'success');

                    if (data.usines_updated && data.usines_updated.length) {
                        // Mise à jour visuelle du prix sur les tickets restants (même usine)
                        const prix = formData.get('prix_unitaire');
                        document.querySelectorAll('.ticket-checkbox').forEach((checkbox) => {
                            if (!checkbox.dataset.prixUnitaire) {
                                checkbox.dataset.prixUnitaire = prix;
                            }
                        });
                    }
                } catch (error) {
                    showAlert('Erreur réseau lors de la validation.', 'danger');
                } finally {
                    setSubmitting(submitBtn, false);
                }
            }

            bindSelectionEvents();

            document.getElementById('bulkValidateModal').addEventListener('show.bs.modal', () => {
                updateBulkState();
                const selected = getSelectedCheckboxes();
                const prices = selected
                    .map((checkbox) => checkbox.dataset.prixUnitaire)
                    .filter((value) => value !== '');
                const uniquePrices = [...new Set(prices)];
                if (uniquePrices.length === 1) {
                    bulkPrixInput.value = String(parseFloat(uniquePrices[0]));
                    bulkPrixInput.readOnly = true;
                } else {
                    bulkPrixInput.value = '';
                    bulkPrixInput.readOnly = false;
                }
                document.getElementById('bulkValidateError').textContent = '';
                bulkPrixInput.classList.remove('is-invalid');
            });

            document.getElementById('validateTicketModal').addEventListener('hidden.bs.modal', () => {
                prixInput.readOnly = false;
                prixInput.value = '';
                info.textContent = '';
                document.getElementById('validateTicketError').textContent = '';
                prixInput.classList.remove('is-invalid');
            });

            document.getElementById('bulkValidateModal').addEventListener('hidden.bs.modal', () => {
                bulkPrixInput.value = '';
                bulkPrixInput.readOnly = false;
                document.getElementById('update_all_usine').checked = false;
                document.getElementById('bulkValidateError').textContent = '';
                bulkPrixInput.classList.remove('is-invalid');
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitValidation(
                    form,
                    document.getElementById('validateTicketSubmit'),
                    document.getElementById('validateTicketError'),
                    prixInput,
                );
            });

            document.getElementById('bulkValidateForm').addEventListener('submit', function (event) {
                event.preventDefault();
                submitValidation(
                    this,
                    document.getElementById('bulkValidateSubmit'),
                    document.getElementById('bulkValidateError'),
                    bulkPrixInput,
                );
            });
        });
    </script>
    @endif
@endsection
