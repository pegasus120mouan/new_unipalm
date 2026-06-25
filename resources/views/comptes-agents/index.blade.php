@extends('layout.main')

@section('title', 'Comptes des agents')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>
            <i class="bi bi-person-fill"></i>
            Comptes des agents
            <span class="badge bg-light text-dark ms-2 align-middle">{{ $totalAgents }} agent(s)</span>
        </h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Comptes des agents</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <section class="row mb-3">
        <div class="col-12">
            <p class="text-muted mb-0">Vue d'ensemble de tous les agents enregistrés dans le système.</p>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart"></i> Statistiques générales
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Total montant</span>
                            <span>100%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 100%;"></div>
                        </div>
                        <div class="small text-muted mt-1">
                            {{ number_format($globalStats['total_montant'], 0, '', ' ') }} FCFA
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Montant payé</span>
                            <span>{{ $globalStats['pct_paye'] }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $globalStats['pct_paye'] }}%;"></div>
                        </div>
                        <div class="small text-muted mt-1">
                            {{ number_format($globalStats['montant_paye'], 0, '', ' ') }} FCFA
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Reste à payer</span>
                            <span>{{ $globalStats['pct_reste'] }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $globalStats['pct_reste'] }}%;"></div>
                        </div>
                        <div class="small text-muted mt-1">
                            {{ number_format($globalStats['reste_a_payer'], 0, '', ' ') }} FCFA
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="compte-agent-filters">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-list-ul"></i> Liste des agents</span>
                    @if ($agents->total() > 0)
                        <span class="text-muted small">
                            Affichage de {{ $agents->firstItem() }} à {{ $agents->lastItem() }} sur {{ $agents->total() }} agent(s)
                        </span>
                    @endif
                </div>
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('comptes-agents.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="search_nom" class="form-label">Nom</label>
                            <input type="text" name="search_nom" id="search_nom" class="form-control"
                                placeholder="Nom" value="{{ $filters['search_nom'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="search_prenom" class="form-label">Prénom</label>
                            <input type="text" name="search_prenom" id="search_prenom" class="form-control"
                                placeholder="Prénom" value="{{ $filters['search_prenom'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="search_contact" class="form-label">Contact</label>
                            <input type="text" name="search_contact" id="search_contact" class="form-control"
                                placeholder="Contact" value="{{ $filters['search_contact'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="search_chef" class="form-label">Chef d'équipe</label>
                            <input type="text" name="search_chef" id="search_chef" class="form-control"
                                placeholder="Chef d'équipe" value="{{ $filters['search_chef'] }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('comptes-agents.index') }}" class="btn btn-secondary btn-sm">
                                <i class="bi bi-x-lg"></i> Réinitialiser
                            </a>
                        </div>
                    </form>

                    @if ($hasFilters)
                        <div class="alert alert-light border mt-3 mb-0 py-2">
                            <i class="bi bi-info-circle"></i>
                            <strong>{{ $agents->total() }}</strong> agent(s) trouvé(s)
                        </div>
                    @endif
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">Agent</th>
                                <th class="align-middle">Contact</th>
                                <th class="align-middle">Chef d'équipe</th>
                                <th class="align-middle">Statistiques</th>
                                <th class="align-middle">Date création</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $agent)
                                <tr>
                                    <td class="align-middle">
                                        <a href="{{ route('comptes-agents.show', $agent->id_agent) }}" class="text-decoration-none text-dark">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                                    style="width: 32px; height: 32px; font-size: 0.85rem; font-weight: 600;">
                                                    {{ strtoupper(substr($agent->nom, 0, 1)) }}
                                                </div>
                                                <div class="fw-semibold text-uppercase">
                                                    {{ trim($agent->nom.' '.$agent->prenom) }}
                                                </div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="align-middle">
                                        @if ($agent->contact)
                                            <span class="badge bg-info">{{ $agent->contact }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $agent->chef_equipe ?: '—' }}</td>
                                    <td class="align-middle">
                                        @include('comptes-agents.partials.stats-bars', [
                                            'total' => $agent->total_montant,
                                            'paye' => $agent->montant_paye,
                                            'reste' => $agent->reste_a_payer,
                                        ])
                                    </td>
                                    <td class="align-middle">
                                        {{ $agent->date_ajout?->format('d/m/Y') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                                        Aucun agent trouvé pour le moment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($agents->hasPages())
                    <div class="card-footer">
                        {{ $agents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
