@extends('layout.main')

@section('title')
    Financements — {{ $agent->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Détails Financements</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('financements.index') }}">Financements</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $agent->full_name }}</li>
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
            <div class="card financement-detail-header">
                <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1">
                            <i class="bi bi-cash-stack"></i> Détails Financements
                        </h4>
                        <p class="text-muted mb-0">
                            Agent : <strong>{{ $agent->full_name }}</strong>
                            @if ($agent->groupe)
                                • Chef d'équipe : <strong>{{ $agent->groupe->full_name }}</strong>
                            @endif
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFinancementModal">
                            <i class="bi bi-plus-lg"></i> Nouveau Financement
                        </button>
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#historiqueModal">
                            <i class="bi bi-file-pdf"></i> Voir historique de financement
                        </button>
                        <a href="{{ route('financements.index') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-arrow-left"></i> Retour aux financements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-primary">
                        {{ number_format($stats['montant_initial'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Montant Initial (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-success">
                        {{ number_format($stats['montant_rembourse'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Montant Remboursé (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-warning">
                        {{ number_format($stats['solde_financement'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Solde Financement (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-info">
                        {{ $stats['total_operations'] }}
                    </div>
                    <div class="financement-stat-label">Total Opérations</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres de recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('financements.show', $agent) }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Numéro ou motif..." value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="type_filter" class="form-label">Type</label>
                            <select name="type_filter" id="type_filter" class="form-select">
                                <option value="">Tous</option>
                                <option value="financement" @selected($filters['type_filter'] === 'financement')>Financements</option>
                                <option value="remboursement" @selected($filters['type_filter'] === 'remboursement')>Remboursements</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control"
                                value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control"
                                value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                            <a href="{{ route('financements.show', $agent) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card financement-summary-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-list-ul"></i> Historique des financements</span>
                    <span class="text-muted small">
                        Affichage de {{ $financements->firstItem() ?? 0 }} à {{ $financements->lastItem() ?? 0 }}
                        sur {{ $financements->total() }} opération(s)
                    </span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped financement-history-table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Numéro</th>
                                <th>Type</th>
                                <th>Montant</th>
                                <th>Motif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($financements as $financement)
                                <tr>
                                    <td><strong>{{ $financement->date_financement?->format('d/m/Y') ?? '-' }}</strong></td>
                                    <td><code>{{ $financement->code_affiche }}</code></td>
                                    <td>
                                        @if ($financement->isAdvance())
                                            <span class="badge bg-info">
                                                <i class="bi bi-plus-circle"></i> Financement
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-dash-circle"></i> Remboursement
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="{{ $financement->isAdvance() ? 'text-success' : 'text-danger' }}">
                                            {{ $financement->isAdvance() ? '+' : '' }}{{ number_format((float) $financement->montant, 0, '', ' ') }} FCFA
                                        </strong>
                                    </td>
                                    <td>
                                        @if ($financement->motif)
                                            {{ $financement->motif }}
                                        @else
                                            <em class="text-muted">Aucun motif</em>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucune opération trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($financements->hasPages())
                    <div class="card-footer">
                        {{ $financements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    @include('financements.partials.add-modal', [
        'agents' => $agents,
        'selectedAgentId' => $agent->id_agent,
        'redirectTo' => route('financements.show', $agent),
    ])

    @include('financements.partials.historique-modal', ['agent' => $agent])
@endsection

@push('scripts')
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
