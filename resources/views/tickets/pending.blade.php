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

    @php
        $canValidateTickets = auth()->user()->canValidateTickets();
    @endphp

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span>Tickets en attente de validation</span>
                        <span class="text-muted ms-2">(validation non effectuée)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted">{{ $tickets->total() }} ticket(s)</span>
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
                <div class="card-body table-responsive">
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
                            @error('prix_unitaire')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Valider</button>
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
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Valider avec ce prix
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if ($canValidateTickets && $errors->has('prix_unitaire') && ! old('ticket_ids'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('validateTicketModal')).show();
            });
        </script>
    @endif

    @if ($canValidateTickets && $errors->has('prix_unitaire') && old('ticket_ids'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('bulkValidateModal')).show();
            });
        </script>
    @endif

    @if ($canValidateTickets)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('validateTicketForm');
            const prixInput = document.getElementById('prix_unitaire');
            const info = document.getElementById('validateTicketInfo');
            const validateBaseUrl = @json(url('/tickets'));

            const selectAll = document.getElementById('selectAllTickets');
            const checkboxes = () => Array.from(document.querySelectorAll('.ticket-checkbox'));
            const bulkValidateBtn = document.getElementById('bulkValidateBtn');
            const bulkTicketCount = document.getElementById('bulkTicketCount');
            const bulkIdsContainer = document.getElementById('bulkTicketIdsContainer');
            const bulkPrixInput = document.getElementById('bulk_prix_unitaire');

            function getSelectedIds() {
                return checkboxes()
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);
            }

            function updateBulkState() {
                const selected = getSelectedIds();
                const count = selected.length;

                bulkValidateBtn.disabled = count === 0;
                bulkTicketCount.textContent = `${count} ticket(s) sélectionné(s)`;

                bulkIdsContainer.innerHTML = '';
                selected.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ticket_ids[]';
                    input.value = id;
                    bulkIdsContainer.appendChild(input);
                });

                if (selectAll) {
                    const selectable = checkboxes();
                    selectAll.checked = selectable.length > 0 && selectable.every((checkbox) => checkbox.checked);
                    selectAll.indeterminate = count > 0 && count < selectable.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes().forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    updateBulkState();
                });
            }

            checkboxes().forEach((checkbox) => {
                checkbox.addEventListener('change', updateBulkState);
            });

            document.getElementById('bulkValidateModal').addEventListener('show.bs.modal', updateBulkState);

            document.querySelectorAll('.validate-ticket-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    const ticketId = button.dataset.ticketId;
                    const ticketNumero = button.dataset.ticketNumero;
                    const prixUnitaire = button.dataset.prixUnitaire;

                    form.action = `${validateBaseUrl}/${ticketId}/validate`;
                    info.textContent = `Ticket n° ${ticketNumero}`;
                    prixInput.value = prixUnitaire || '';
                    prixInput.readOnly = prixUnitaire !== '';
                });
            });

            document.getElementById('validateTicketModal').addEventListener('hidden.bs.modal', () => {
                prixInput.readOnly = false;
                prixInput.value = '';
                info.textContent = '';
            });

            document.getElementById('bulkValidateModal').addEventListener('hidden.bs.modal', () => {
                bulkPrixInput.value = '';
                document.getElementById('update_all_usine').checked = false;
            });

            @if (old('prix_unitaire') && old('ticket_ids'))
                bulkPrixInput.value = @json(old('prix_unitaire'));
                document.getElementById('update_all_usine').checked = @json((bool) old('update_all_usine'));
                @foreach (old('ticket_ids', []) as $ticketId)
                    (function () {
                        const checkbox = document.querySelector('.ticket-checkbox[value="{{ $ticketId }}"]');
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    })();
                @endforeach
                updateBulkState();
            @endif
        });
    </script>
    @endif
@endsection
