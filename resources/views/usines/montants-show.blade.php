@extends('layout.main')

@section('title', 'Situation financière — '.$usine->nom_usine)

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Situation financière — {{ $usine->nom_usine }}</h3>
        </div>
        <a href="{{ route('usines.montants') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
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

    <section class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('usines.montants.show', $usine) }}" class="row g-3 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="id_agent" class="form-label">Agent</label>
                            <select name="id_agent" id="id_agent" class="form-select">
                                <option value="">Tous</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id_agent }}" @selected((string) $filters['id_agent'] === (string) $agent->id_agent)>
                                        {{ $agent->full_name }}
                                        @if ($agent->numero_agent)
                                            ({{ $agent->numero_agent }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control"
                                value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control"
                                value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                            <a href="{{ route('usines.montants.show', $usine) }}" class="btn btn-outline-secondary">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-center mb-3">
        <a href="{{ route('usines.financements.show', $usine) }}"
            class="badge rounded-pill text-decoration-none px-3 py-2"
            style="background-color: #6f42c1; font-size: 0.95rem;"
            title="Voir les financements de cette usine">
            <i class="bi bi-wallet2 me-1"></i>
            Montant financement :
            <strong id="badge-montant-financement">{{ number_format($montantFinancement, 0, ',', ' ') }} FCFA</strong>
        </a>
    </div>

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
                <div class="card-body">
                    <h6 class="card-title" style="color: #842029;">Montant</h6>
                    <h3 class="mb-0" id="card-montant-du" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
                    <small class="text-muted">Somme des montants du bilan des entrées</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
                <div class="card-body">
                    <h6 class="card-title" style="color: #0f5132;">Montant payé</h6>
                    <h3 class="mb-0" id="card-montant-paye" style="color: #0f5132;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</h3>
                    <small class="text-muted">Paiements enregistrés</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
                <div class="card-body"
                    data-montant-paye="{{ $montantPaye }}"
                    data-montant-financement="{{ $montantFinancement }}">
                    <h6 class="card-title" style="color: #664d03;">Reste à payer</h6>
                    <h3 class="mb-0" id="card-reste-a-payer" style="color: #664d03;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h3>
                    <small class="text-muted">Montant − montant payé</small>
                </div>
            </div>
        </div>
    </section>

    <div class="d-flex gap-2 flex-wrap mb-3">
        <button type="button"
            class="btn btn-success btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#paymentModal{{ $usine->id_usine }}"
            @if ($resteAPayer <= 0) disabled title="Aucun montant restant à payer" @endif>
            <i class="bi bi-credit-card"></i> Effectuer un paiement
        </button>
        <a href="{{ route('usines.montants.payments.pdf', $usine) }}" class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-printer"></i> Historique PDF
        </a>
    </div>

    <section class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                    style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
                    <h5 class="card-title mb-0" style="color: #842029;">
                        <i class="bi bi-calendar3 me-2"></i>Bilan des entrées
                    </h5>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0" id="bilan-entrees-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Poids Unipalm</th>
                                <th class="text-end" style="min-width: 9rem;">Poids Usines</th>
                                <th class="text-end">Écart</th>
                                <th class="text-end" style="min-width: 9rem;">Prix unitaire</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entrees as $entree)
                                @php
                                    $date = \Carbon\Carbon::parse($entree->date_entree);
                                @endphp
                                <tr class="entree-row"
                                    data-date="{{ $entree->date_entree }}"
                                    data-poids-unipalm="{{ $entree->poids_unipalm }}"
                                    data-poids-usine="{{ $entree->poids_usine }}"
                                    data-prix-unitaire="{{ $entree->prix_unitaire }}">
                                    <td class="fw-semibold">
                                        <a href="{{ route('usines.montants.day.pdf', ['usine' => $usine, 'date' => $entree->date_entree]) }}"
                                            target="_blank"
                                            class="text-decoration-none"
                                            title="Voir les tickets PDF du {{ $date->format('d/m/Y') }}">
                                            {{ $date->format('d/m/Y') }}
                                            <i class="bi bi-file-earmark-pdf text-danger ms-1"></i>
                                        </a>
                                    </td>
                                    <td class="text-end">{{ number_format($entree->poids_unipalm, 0, ',', ' ') }}</td>
                                    <td class="text-end editable-cell"
                                        data-field="poids_usine"
                                        title="Double-cliquer pour modifier">
                                        <span class="editable-display">{{ number_format((float) $entree->poids_usine, 0, ',', ' ') }}</span>
                                    </td>
                                    <td class="text-end entree-ecart">{{ number_format((float) $entree->ecart, 0, ',', ' ') }}</td>
                                    <td class="text-end editable-cell"
                                        data-field="prix_unitaire"
                                        title="Double-cliquer pour modifier">
                                        <span class="editable-display">{{ number_format((float) $entree->prix_unitaire, 0, ',', ' ') }}</span>
                                    </td>
                                    <td class="text-end fw-semibold entree-montant">{{ number_format((float) $entree->montant, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Aucune entrée pour cette usine.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                    style="background-color: #d1e7dd; border-bottom: 1px solid #badbcc;">
                    <h5 class="card-title mb-0" style="color: #0f5132;">
                        <i class="bi bi-clock-history me-2"></i>Historique des paiements
                    </h5>
                    <a href="{{ route('usines.montants.payments.pdf', $usine) }}" class="btn btn-danger btn-sm" target="_blank">
                        <i class="bi bi-printer"></i> Imprimer
                    </a>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th class="text-end">Montant</th>
                                <th>Mode</th>
                                <th>Référence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paiements as $paiement)
                                <tr>
                                    <td>{{ $paiement->id }}</td>
                                    <td class="fw-semibold">{{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format((float) $paiement->montant, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td>{{ $paiement->mode_paiement }}</td>
                                    <td>{{ $paiement->reference_paiement ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucun paiement enregistré pour cette usine.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($paiements->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">Total</th>
                                    <th class="text-end">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </section>

    @include('usines.partials.payment-modal', [
        'usine' => $usine,
        'banques' => $banques,
        'paymentAction' => route('usines.montants.payment', $usine),
        'redirectTo' => route('usines.montants.show', array_merge(['usine' => $usine], array_filter($filters))),
        'resteAPayer' => $resteAPayer,
        'montantFinancement' => $montantFinancement,
        'source' => 'montants',
    ])

    <div class="modal fade" id="entreeSuccessModal" tabindex="-1" aria-labelledby="entreeSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                        style="width: 64px; height: 64px; background: rgba(25, 135, 84, 0.12);">
                        <i class="bi bi-check-lg text-success" style="font-size: 1.75rem;"></i>
                    </div>
                    <h5 class="fw-semibold mb-2" id="entreeSuccessModalLabel">Modification enregistrée</h5>
                    <p class="text-muted mb-0 small" id="entreeSuccessModalMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-success px-4 rounded-pill" data-bs-dismiss="modal">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/amount-input.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const saveUrl = @json(route('usines.montants.entrees.store', $usine));
        const csrfToken = @json(csrf_token());
        const successModalEl = document.getElementById('entreeSuccessModal');
        const successModal = successModalEl ? new bootstrap.Modal(successModalEl) : null;
        const successMessage = document.getElementById('entreeSuccessModalMessage');
        const totalsEl = document.querySelector('[data-montant-paye]');
        const montantPaye = parseFloat(totalsEl?.dataset.montantPaye || '0');

        @if (session('payment_usine_id') || $errors->has('paiement') || $errors->has('montant') || $errors->has('id_banque'))
            (function () {
                var usineId = @json(session('payment_usine_id') ?? old('payment_usine_id'));
                if (! usineId) {
                    return;
                }
                var modalEl = document.getElementById('paymentModal' + usineId);
                if (modalEl) {
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                }
            })();
        @endif

        function formatNumber(value) {
            return Math.round(Number(value) || 0).toLocaleString('fr-FR').replace(/\u202f/g, ' ');
        }

        function refreshRow(row) {
            const poidsUnipalm = parseFloat(row.dataset.poidsUnipalm || '0');
            const poidsUsine = parseFloat(row.dataset.poidsUsine || '0');
            const prix = parseFloat(row.dataset.prixUnitaire || '0');
            const ecart = poidsUnipalm - poidsUsine;
            const montant = poidsUsine * prix;

            row.querySelector('.entree-ecart').textContent = formatNumber(ecart);
            row.querySelector('.entree-montant').textContent = formatNumber(montant) + ' FCFA';
        }

        function refreshTotals() {
            let total = 0;
            document.querySelectorAll('.entree-row').forEach(function (row) {
                const poidsUsine = parseFloat(row.dataset.poidsUsine || '0');
                const prix = parseFloat(row.dataset.prixUnitaire || '0');
                total += poidsUsine * prix;
            });

            const montantDu = document.getElementById('card-montant-du');
            const reste = document.getElementById('card-reste-a-payer');
            if (montantDu) montantDu.textContent = formatNumber(total) + ' FCFA';
            if (reste) reste.textContent = formatNumber(Math.max(0, total - montantPaye)) + ' FCFA';
        }

        const successTitle = document.getElementById('entreeSuccessModalLabel');

        function showSuccess(message, field) {
            if (!successModal || !successMessage) {
                alert(message);
                return;
            }
            if (successTitle) {
                successTitle.textContent = field === 'poids_usine'
                    ? 'Poids mis à jour'
                    : (field === 'prix_unitaire' ? 'Prix unitaire mis à jour' : 'Modification enregistrée');
            }
            successMessage.textContent = message;
            successModal.show();
        }

        function showError(message) {
            alert(message);
        }

        function exitEdit(cell, value) {
            cell.dataset.editing = '0';
            cell.innerHTML = '<span class="editable-display">' + formatNumber(value) + '</span>';
        }

        async function saveField(row, field, value) {
            const body = new FormData();
            body.append('_token', csrfToken);
            body.append('date_entree', row.dataset.date);
            body.append('field', field);
            body.append(field, String(value));

            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body,
            });

            if (!response.ok) {
                throw new Error('Erreur lors de l\'enregistrement.');
            }

            return response.json();
        }

        document.querySelectorAll('.editable-cell').forEach(function (cell) {
            cell.style.cursor = 'pointer';

            cell.addEventListener('dblclick', function () {
                if (cell.dataset.editing === '1') {
                    return;
                }

                const row = cell.closest('.entree-row');
                const field = cell.dataset.field;
                const currentValue = field === 'poids_usine'
                    ? row.dataset.poidsUsine
                    : row.dataset.prixUnitaire;

                cell.dataset.editing = '1';
                cell.innerHTML = '';

                const input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.step = '0.01';
                input.className = 'form-control form-control-sm text-end';
                input.value = currentValue === '0' || currentValue === '0.00' ? '' : currentValue;
                input.placeholder = '0';
                cell.appendChild(input);
                input.focus();
                input.select();

                let saving = false;

                async function commit() {
                    if (saving) {
                        return;
                    }
                    saving = true;

                    const raw = input.value.trim();
                    const numeric = raw === '' ? 0 : parseFloat(raw);
                    if (isNaN(numeric) || numeric < 0) {
                        exitEdit(cell, currentValue);
                        return;
                    }

                    if (Number(numeric) === Number(currentValue)) {
                        exitEdit(cell, currentValue);
                        return;
                    }

                    try {
                        const data = await saveField(row, field, numeric);
                        row.dataset.poidsUsine = String(data.poids_usine);
                        row.dataset.prixUnitaire = String(data.prix_unitaire);
                        exitEdit(cell, field === 'poids_usine' ? data.poids_usine : data.prix_unitaire);
                        refreshRow(row);
                        refreshTotals();
                        showSuccess(data.message, field);
                    } catch (error) {
                        exitEdit(cell, currentValue);
                        showError(error.message || 'Erreur lors de l\'enregistrement.');
                    }
                }

                input.addEventListener('blur', commit);
                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        input.blur();
                    }
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        saving = true;
                        exitEdit(cell, currentValue);
                    }
                });
            });
        });
    });
</script>
@endpush
