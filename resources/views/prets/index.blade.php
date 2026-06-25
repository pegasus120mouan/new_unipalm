@extends('layout.main')

@section('title', 'Prêts')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Gestion des prêts</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Prêts</li>
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
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-primary">
                        {{ number_format($globalStats['total_prets'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Total prêts accordés (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-success">
                        {{ number_format($globalStats['total_remboursements'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Total remboursements (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-warning">
                        {{ number_format($globalStats['solde_global'], 0, '', ' ') }}
                    </div>
                    <div class="financement-stat-label">Solde restant (FCFA)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6 mb-3">
            <div class="card financement-stat-card h-100">
                <div class="card-body text-center">
                    <div class="financement-stat-value text-info">
                        {{ $globalStats['nb_agents'] }}
                    </div>
                    <div class="financement-stat-label">Agents avec prêt</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="pret-filters">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres avancés — Prêts
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('prets.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="ID, agent, motif..." value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="agent_id" class="form-label">Agent</label>
                            <select name="agent_id" id="agent_id" class="form-select">
                                <option value="">Tous les agents</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id_agent }}" @selected(($filters['agent_id'] ?? '') == $agent->id_agent)>
                                        {{ $agent->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="statut" class="form-label">Statut</label>
                            <select name="statut" id="statut" class="form-select">
                                <option value="">Tous</option>
                                <option value="en_cours" @selected(($filters['statut'] ?? '') === 'en_cours')>En cours</option>
                                <option value="termine" @selected(($filters['statut'] ?? '') === 'termine')>Terminé</option>
                                <option value="annule" @selected(($filters['statut'] ?? '') === 'annule')>Annulé</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control"
                                value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control"
                                value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Appliquer les filtres
                            </button>
                            <a href="{{ route('prets.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
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
                    <h4 class="card-title mb-0">Résumé des prêts par agent</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#listeDetailleeModal">
                            <i class="bi bi-list-ul"></i> Liste détaillée des prêts
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPretModal">
                            <i class="bi bi-plus-lg"></i> Nouveau prêt
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped financement-resume-table mb-0">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th class="text-center">Nombre de prêts</th>
                                <th class="text-end">Montant accordé</th>
                                <th class="text-end">Déjà remboursé</th>
                                <th class="text-end">Solde restant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summaries as $summary)
                                <tr>
                                    <td>
                                        <a href="{{ route('prets.show', $summary->id_agent) }}" class="financement-agent-link">
                                            <i class="bi bi-person-fill"></i>
                                            {{ trim($summary->nom.' '.$summary->prenom) }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $summary->nombre_prets }}</span>
                                    </td>
                                    <td class="text-end montant-positif">
                                        {{ number_format((float) $summary->montant_initial, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end montant-negatif">
                                        {{ number_format((float) $summary->montant_rembourse, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-end montant-solde">
                                        {{ number_format((float) $summary->solde_restant, 0, '', ' ') }} FCFA
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucun prêt trouvé avec les critères sélectionnés.
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
                    <h5 class="modal-title" id="listeDetailleeModalLabel">Liste détaillée des prêts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Agent</th>
                                    <th>Date octroi</th>
                                    <th class="text-end">Montant initial</th>
                                    <th class="text-end">Remboursé</th>
                                    <th class="text-end">Restant</th>
                                    <th>Statut</th>
                                    <th>Motif</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($prets as $pret)
                                    <tr>
                                        <td>#{{ $pret->id_pret }}</td>
                                        <td>{{ $pret->agent?->full_name ?? '-' }}</td>
                                        <td>{{ $pret->date_octroi?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="text-end">{{ number_format((float) $pret->montant_initial, 0, '', ' ') }} FCFA</td>
                                        <td class="text-end">{{ number_format($pret->montantRembourse(), 0, '', ' ') }} FCFA</td>
                                        <td class="text-end">{{ number_format((float) ($pret->montant_restant ?? 0), 0, '', ' ') }} FCFA</td>
                                        <td><span class="badge {{ $pret->statutBadgeClass() }}">{{ $pret->statutLabel() }}</span></td>
                                        <td>{{ $pret->motif ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">Aucun prêt trouvé.</td>
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

    @include('prets.partials.add-modal', [
        'agents' => $agents,
        'redirectTo' => route('prets.index'),
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
