@extends('layout.main')

@section('title', 'Financements')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Financements</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Financements</li>
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

    <section class="row" id="financement-filters">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres avancés — Financements
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('financements.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="search" class="form-label">Numéro ou motif</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Rechercher..." value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="agent" class="form-label">Nom ou prénom agent</label>
                            <input type="text" name="agent" id="agent" class="form-control"
                                placeholder="Ex: Aka, Georges..." value="{{ $filters['agent'] ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control"
                                value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control"
                                value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Appliquer les filtres
                            </button>
                            <a href="{{ route('financements.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    @if (($financementsEnAttente ?? collect())->isNotEmpty())
    <section class="row mb-4" id="financements-en-attente">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="text-danger fw-semibold">
                        <i class="bi bi-hourglass-split"></i>
                        Financements en attente de validation
                    </span>
                    <span class="badge bg-danger">{{ $financementsEnAttente->count() }}</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Agent</th>
                                <th>Date</th>
                                <th class="text-end">Montant</th>
                                <th>Motif</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($financementsEnAttente as $financement)
                                <tr>
                                    <td><code>{{ $financement->code_affiche }}</code></td>
                                    <td>{{ $financement->agent?->full_name ?? ('#'.$financement->id_agent) }}</td>
                                    <td>{{ $financement->date_financement?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-end text-danger fw-semibold">
                                        {{ number_format((float) $financement->montant, 0, '', ' ') }} FCFA
                                    </td>
                                    <td>{{ $financement->motif_affiche ?: '—' }}</td>
                                    <td class="text-end">
                                        @if(auth()->user()?->canAccessModule('financements.valider'))
                                            <button type="button"
                                                class="btn btn-success btn-sm btn-valider-financement"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmValiderFinancementModal"
                                                data-action="{{ route('financements.valider', $financement->Numero_financement) }}"
                                                data-code="{{ $financement->code_affiche }}"
                                                data-agent="{{ $financement->agent?->full_name ?? ('#'.$financement->id_agent) }}"
                                                data-date="{{ $financement->date_financement?->format('d/m/Y') ?? '—' }}"
                                                data-montant="{{ number_format((float) $financement->montant, 0, ',', ' ') }} FCFA"
                                                data-motif="{{ $financement->motif_affiche ?: '—' }}">
                                                <i class="bi bi-check2-circle"></i> Valider
                                            </button>
                                        @else
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card financement-summary-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">Résumé des financements par agent</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#listeDetailleeModal">
                            <i class="bi bi-list-ul"></i> Liste détaillée des financements
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" disabled title="Les financements se créent via une demande sur le compte agent">
                            <i class="bi bi-plus-lg"></i> Nouveau financement
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped financement-resume-table mb-0">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th class="text-center">Nombre de financements</th>
                                <th class="text-end">Montant initial</th>
                                <th class="text-end">Déjà remboursé</th>
                                <th class="text-end">Solde financement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summaries as $summary)
                                <tr>
                                    <td>
                                        <a href="{{ route('financements.show', $summary->id_agent) }}" class="financement-agent-link">
                                            <i class="bi bi-person-fill"></i>
                                            {{ trim($summary->nom.' '.$summary->prenom) }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $summary->nombre_financements }}</span>
                                    </td>
                                    <td class="text-end montant-positif">
                                        {{ number_format((float) $summary->montant_initial, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end montant-negatif">
                                        {{ number_format((float) $summary->montant_rembourse, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end montant-solde">
                                        {{ number_format((float) $summary->solde_financement, 0, '', ' ') }} FCFA
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucun agent trouvé avec les critères sélectionnés.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($summaries->hasPages())
                    <div class="card-footer">
                        {{ $summaries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="modal fade" id="listeDetailleeModal" tabindex="-1" aria-labelledby="listeDetailleeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="listeDetailleeModalLabel">Liste détaillée des financements</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>N° Financement</th>
                                    <th>Agent</th>
                                    <th>Date</th>
                                    <th class="text-end">Montant</th>
                                    <th>Motif</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($financements as $financement)
                                    <tr>
                                        <td>{{ $financement->code_affiche }}</td>
                                        <td>{{ $financement->agent?->full_name ?? '-' }}</td>
                                        <td>{{ $financement->date_financement?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="text-end {{ $financement->isAdvance() ? 'text-success' : 'text-danger' }}">
                                            {{ number_format((float) $financement->montant, 0, '', ' ') }} FCFA
                                        </td>
                                        <td>{{ $financement->motif_affiche }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Aucun financement trouvé.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @include('financements.partials.add-modal', [
        'agents' => $agents,
        'redirectTo' => route('financements.index'),
    ])
    @include('financements.partials.valider-confirm-modal')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('confirmValiderFinancementForm');
            var submitBtn = document.getElementById('confirmValiderFinancementSubmit');

            document.querySelectorAll('.btn-valider-financement').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (form) {
                        form.action = btn.getAttribute('data-action') || '';
                    }
                    document.getElementById('confirmValiderFinCode').textContent = btn.getAttribute('data-code') || '—';
                    document.getElementById('confirmValiderFinAgent').textContent = btn.getAttribute('data-agent') || '—';
                    document.getElementById('confirmValiderFinDate').textContent = btn.getAttribute('data-date') || '—';
                    document.getElementById('confirmValiderFinMontant').textContent = btn.getAttribute('data-montant') || '—';
                    document.getElementById('confirmValiderFinMotif').textContent = btn.getAttribute('data-motif') || '—';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmer la validation';
                    }
                });
            });

            if (form) {
                form.addEventListener('submit', function () {
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validation…';
                    }
                });
            }
        });
    </script>
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('addFinancementModal');
                if (modalEl) {
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
        </script>
    @endif
@endpush
