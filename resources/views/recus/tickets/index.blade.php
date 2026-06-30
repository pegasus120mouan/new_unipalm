@extends('layout.main')

@section('title', 'Reçus tickets / bordereaux')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Reçus de paiement tickets / bordereaux</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reçus tickets / bordereaux</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>{{ session('success') }}</span>
                @if (session('last_recu_id'))
                    <a href="{{ route('recus.tickets.pdf', session('last_recu_id')) }}" target="_blank" class="btn btn-light btn-sm">
                        <i class="bi bi-printer"></i> Imprimer le reçu
                    </a>
                @endif
            </div>
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
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Total reçus</h6>
                    <h4 class="mb-0">{{ number_format($stats['total'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Tickets</h6>
                    <h4 class="mb-0 text-info">{{ number_format($stats['tickets'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Bordereaux</h6>
                    <h4 class="mb-0 text-primary">{{ number_format($stats['bordereaux'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Montant encaissé</h6>
                    <h4 class="mb-0 text-success">{{ number_format($stats['montant_total'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('recus.tickets.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="all" @selected($filters['type'] === 'all')>Tous</option>
                                <option value="ticket" @selected($filters['type'] === 'ticket')>Tickets</option>
                                <option value="bordereau" @selected($filters['type'] === 'bordereau')>Bordereaux</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="agent_id" class="form-label">Agent</label>
                            <select name="agent_id" id="agent_id" class="form-select">
                                <option value="">Tous les agents</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id_agent }}" @selected($filters['agent_id'] == $agent->id_agent)>
                                        {{ $agent->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-md-2">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                value="{{ $filters['search'] }}" placeholder="N° reçu, document...">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    @if ($hasFilters)
                        <div class="mt-2">
                            <a href="{{ route('recus.tickets.index') }}" class="btn btn-sm btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Liste des reçus</span>
                    <span class="text-muted">{{ $recus->total() }} reçu(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>N° Reçu</th>
                                <th>Type</th>
                                <th>N° Document</th>
                                <th>Agent</th>
                                <th class="text-end">Montant payé</th>
                                <th class="text-end">Reste à payer</th>
                                <th>Caissier</th>
                                <th>Source</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recus as $recu)
                                <tr @if (session('last_recu_id') == $recu->id_recu) class="table-success" @endif>
                                    <td>{{ $recu->date_creation?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td><strong>{{ $recu->numero_recu }}</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $recu->type_document === 'ticket' ? 'info' : 'primary' }}">
                                            {{ $recu->typeLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $recu->numero_document }}</td>
                                    <td>
                                        <div>{{ $recu->nom_agent }}</div>
                                        @if ($recu->contact_agent)
                                            <small class="text-muted">{{ $recu->contact_agent }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $recu->montant_paye, 0, '', ' ') }} FCFA</td>
                                    <td class="text-end">{{ number_format((float) $recu->reste_a_payer, 0, '', ' ') }} FCFA</td>
                                    <td>{{ $recu->nom_caissier }}</td>
                                    <td>{{ $recu->sourceLabel() }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('recus.tickets.pdf', $recu) }}" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="bi bi-printer"></i> Imprimer PDF
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Aucun reçu trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($recus->hasPages())
                    <div class="card-footer">
                        {{ $recus->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
