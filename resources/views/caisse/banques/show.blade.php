@extends('layout.main')

@section('title', $banque->nom_banque)

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Fiche banque</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('caisse.banques.index') }}">Banques</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $banque->code_banque }}</li>
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

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 52px; height: 52px; font-size: 1.2rem;">
                            <i class="bi bi-bank"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">
                                {{ $banque->nom_banque }}
                                <a href="{{ route('caisse.banques.show', $banque) }}" class="badge bg-light text-primary border ms-1 align-middle text-decoration-none">
                                    {{ $banque->code_banque }}
                                </a>
                            </h4>
                            <div class="text-muted small">
                                N° compte : <strong>{{ $banque->numero_compte ?: '—' }}</strong>
                            </div>
                            <div class="text-muted small">
                                Statut :
                                @if ($banque->actif)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('caisse.banques.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4 align-items-stretch">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-uppercase text-muted mb-0">Synthèse</h6>
                            <span class="badge bg-light text-dark border">Banque</span>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Mouvements</span>
                                <span class="fw-semibold">{{ $stats['total_mouvements'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Total entrées</span>
                                <span class="fw-semibold">{{ number_format($stats['total_entrees'], 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#manualApproModal">
                            <i class="bi bi-plus-circle"></i> Approvisionnement manuel
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#usineApproModal">
                            <i class="bi bi-building"></i> Paiement usine
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Synthèse financière</h6>
                    <div class="row g-3">
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Solde actuel</div>
                                <div class="fw-bold text-primary fs-5">
                                    {{ number_format($stats['solde'], 0, ',', ' ') }} FCFA
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Approvisionnements manuels</div>
                                <div class="fw-bold text-success">
                                    {{ number_format($stats['total_manuel'], 0, ',', ' ') }} FCFA
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Paiements usines</div>
                                <div class="fw-bold text-info">
                                    {{ number_format($stats['total_usine'], 0, ',', ' ') }} FCFA
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Mouvements de la banque</span>
                    <span class="text-muted">{{ $mouvements->total() }} mouvement(s)</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('caisse.banques.show', $banque) }}" class="row g-3 align-items-end mb-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="all" @selected($filters['type'] === 'all')>Tous</option>
                                <option value="solde_initial" @selected($filters['type'] === 'solde_initial')>Solde initial</option>
                                <option value="approvisionnement_manuel" @selected($filters['type'] === 'approvisionnement_manuel')>Manuel</option>
                                <option value="paiement_usine" @selected($filters['type'] === 'paiement_usine')>Paiement usine</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
                            <a href="{{ route('caisse.banques.show', $banque) }}" class="btn btn-secondary">Réinitialiser</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Libellé</th>
                                    <th>Référence</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-end">Solde après</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mouvements as $mouvement)
                                    <tr>
                                        <td>{{ $mouvement->date_mouvement?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $mouvement->type_badge_class }}">
                                                {{ $mouvement->type_label }}
                                            </span>
                                        </td>
                                        <td style="max-width: 240px;">
                                            <span class="text-truncate d-inline-block" style="max-width: 240px;" title="{{ $mouvement->libelle }}">
                                                {{ $mouvement->libelle }}
                                            </span>
                                        </td>
                                        <td>{{ $mouvement->reference ?: '—' }}</td>
                                        <td class="text-end text-success fw-semibold">
                                            +{{ number_format((float) $mouvement->montant, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="text-end">{{ number_format((float) $mouvement->solde_apres, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ $mouvement->utilisateur?->full_name ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Aucun mouvement pour cette banque.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($mouvements->hasPages())
                        <div class="d-flex justify-content-center mt-3">{{ $mouvements->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="manualApproModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('caisse.banques.approvisionnement', $banque) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Approvisionnement manuel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="manual_montant_display" class="form-label">Montant</label>
                            <div class="input-group">
                                <input type="text" id="manual_montant_display" class="form-control"
                                    data-amount-input inputmode="numeric" autocomplete="off" placeholder="Ex : 500 000" required>
                                <input type="hidden" name="montant" id="manual_montant" data-amount-target>
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label for="libelle" class="form-label">Motif / source</label>
                            <textarea class="form-control" id="libelle" name="libelle" rows="3"
                                placeholder="Ex : Virement SGBCI, espèces..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="usineApproModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('caisse.banques.paiement-usine', $banque) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Paiement usine</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @error('paiement_usine')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <div class="mb-3">
                            <label for="id_usine" class="form-label">Usine</label>
                            <select name="id_usine" id="id_usine" class="form-select" required>
                                <option value="">Sélectionner une usine</option>
                                @foreach ($usines as $usine)
                                    <option value="{{ $usine->id_usine }}"
                                        data-reste="{{ (float) $usine->reste_a_payer }}"
                                        @selected((int) old('id_usine') === (int) $usine->id_usine)>
                                        {{ $usine->nom_usine }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="alert alert-light border mb-3 d-none" id="usineResteAlert">
                            <span class="text-muted">Montant dû :</span>
                            <strong class="text-success" id="usineResteMontant">0 FCFA</strong>
                        </div>
                        <div class="mb-3">
                            <label for="usine_montant_display" class="form-label">Montant</label>
                            <div class="input-group">
                                <input type="text" id="usine_montant_display" class="form-control"
                                    data-amount-input inputmode="numeric" autocomplete="off" placeholder="Ex : 1 000 000" required>
                                <input type="hidden" name="montant" id="usine_montant" data-amount-target>
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="date_paiement" class="form-label">Date</label>
                            <input type="date" name="date_paiement" id="date_paiement" class="form-control"
                                value="{{ old('date_paiement', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="mode_paiement" class="form-label">Mode</label>
                            <select name="mode_paiement" id="mode_paiement" class="form-select" required>
                                <option value="">Sélectionner</option>
                                @foreach (['Espèces', 'Chèque', 'Virement', 'Mobile Money'] as $mode)
                                    <option value="{{ $mode }}" @selected(old('mode_paiement') === $mode)>{{ $mode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label for="reference_paiement" class="form-label">Référence</label>
                            <input type="text" name="reference_paiement" id="reference_paiement" class="form-control"
                                value="{{ old('reference_paiement') }}">
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
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    <script>
        function formatFcfa(amount) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA';
        }

        function updateUsineReste() {
            const select = document.getElementById('id_usine');
            const alertBox = document.getElementById('usineResteAlert');
            const montantEl = document.getElementById('usineResteMontant');

            if (!select || !alertBox || !montantEl) {
                return;
            }

            const option = select.options[select.selectedIndex];
            const reste = option?.dataset?.reste ? parseFloat(option.dataset.reste) : NaN;

            if (!option?.value || Number.isNaN(reste)) {
                alertBox.classList.add('d-none');
                return;
            }

            alertBox.classList.remove('d-none');
            montantEl.textContent = formatFcfa(reste);

            if (reste <= 0) {
                montantEl.classList.remove('text-success');
                montantEl.classList.add('text-muted');
            } else {
                montantEl.classList.remove('text-muted');
                montantEl.classList.add('text-success');
            }
        }

        document.getElementById('id_usine')?.addEventListener('change', updateUsineReste);
        document.getElementById('usineApproModal')?.addEventListener('shown.bs.modal', updateUsineReste);
    </script>
    @if (session('open_usine_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('usineApproModal')).show();
            });
        </script>
    @endif
@endpush
