@extends('layout.main')

@section('title')
    Prêts — {{ $agent->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Détails des prêts</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('prets.index') }}">Prêts</a></li>
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
                            <i class="bi bi-cash-coin"></i> Détails des prêts
                        </h4>
                        <p class="text-muted mb-0">
                            Agent : <strong>{{ $agent->full_name }}</strong>
                            @if ($agent->groupe)
                                • Chef d'équipe : <strong>{{ $agent->groupe->full_name }}</strong>
                            @endif
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPretModal">
                            <i class="bi bi-plus-lg"></i> Nouveau prêt
                        </button>
                        <a href="{{ route('prets.index') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-arrow-left"></i> Retour aux prêts
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-primary">
                        {{ number_format($stats['montant_initial'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Total prêts accordés (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-success">
                        {{ number_format($stats['montant_rembourse'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Total remboursé (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-warning">
                        {{ number_format($stats['solde_restant'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Solde restant (FCFA)</div>
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
                    <form method="GET" action="{{ route('prets.show', $agent) }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="ID, motif, montant..." value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="statut" class="form-label">Statut</label>
                            <select name="statut" id="statut" class="form-select">
                                <option value="">Tous</option>
                                <option value="en_cours" @selected($filters['statut'] === 'en_cours')>En cours</option>
                                <option value="termine" @selected($filters['statut'] === 'termine')>Terminé</option>
                                <option value="annule" @selected($filters['statut'] === 'annule')>Annulé</option>
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
                            <a href="{{ route('prets.show', $agent) }}" class="btn btn-outline-secondary">
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
                    <span><i class="bi bi-list-ul"></i> Historique des prêts</span>
                    <span class="text-muted small">
                        Affichage de {{ $prets->firstItem() ?? 0 }} à {{ $prets->lastItem() ?? 0 }}
                        sur {{ $prets->total() }} prêt(s)
                    </span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped financement-history-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date octroi</th>
                                <th class="text-end">Montant</th>
                                <th class="text-end">Remboursé</th>
                                <th class="text-end">Restant</th>
                                <th>Échéance</th>
                                <th>Statut</th>
                                <th>Motif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prets as $pret)
                                <tr>
                                    <td><strong>#{{ $pret->id_pret }}</strong></td>
                                    <td>{{ $pret->date_octroi?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-end montant-positif">
                                        {{ number_format((float) $pret->montant_initial, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end montant-negatif">
                                        {{ number_format($pret->montantRembourse(), 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end montant-solde">
                                        {{ number_format((float) ($pret->montant_restant ?? 0), 0, '', ' ') }} FCFA
                                    </td>
                                    <td>{{ $pret->date_echeance?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $pret->statutBadgeClass() }}">{{ $pret->statutLabel() }}</span>
                                    </td>
                                    <td>{{ $pret->motif ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Aucun prêt trouvé pour cet agent.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($prets->hasPages())
                    <div class="card-footer">
                        {{ $prets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    @include('prets.partials.add-modal', [
        'agents' => $agents,
        'selectedAgentId' => $agent->id_agent,
        'redirectTo' => route('prets.show', $agent),
    ])
@endsection

@push('scripts')
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('addPretModal');
                if (modalEl) {
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
        </script>
    @endif
@endpush
