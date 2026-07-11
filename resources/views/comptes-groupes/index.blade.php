@extends('layout.main')

@section('title', 'Comptes groupes')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3><i class="bi bi-people-fill"></i> Comptes groupes</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Comptes groupes</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <section class="row mb-3">
        <div class="col-12">
            <p class="text-muted mb-0">Vue d'ensemble des montants dus par chef d'équipe (tickets validés des agents).</p>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-0 text-white h-100 shadow-sm" style="background-color: #0d6efd;">
                <div class="card-body d-flex align-items-center justify-content-between py-3 px-4">
                    <div>
                        <div class="fw-bold fs-5">{{ number_format($globalStats['montant_total'], 0, '', ' ') }} FCFA</div>
                        <div class="small opacity-75">Montant Total</div>
                    </div>
                    <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-0 text-white h-100 shadow-sm" style="background-color: #198754;">
                <div class="card-body d-flex align-items-center justify-content-between py-3 px-4">
                    <div>
                        <div class="fw-bold fs-5">{{ number_format($globalStats['montant_paye'], 0, '', ' ') }} FCFA</div>
                        <div class="small opacity-75">Déjà Payé</div>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-0 text-white h-100 shadow-sm" style="background-color: #dc3545;">
                <div class="card-body d-flex align-items-center justify-content-between py-3 px-4">
                    <div>
                        <div class="fw-bold fs-5">{{ number_format($globalStats['montant_du'], 0, '', ' ') }} FCFA</div>
                        <div class="small opacity-75">Montant Dû</div>
                    </div>
                    <i class="bi bi-exclamation-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-0 text-white h-100 shadow-sm" style="background-color: #fd7e14;">
                <div class="card-body d-flex align-items-center justify-content-between py-3 px-4">
                    <div>
                        <div class="fw-bold fs-5">{{ number_format($globalStats['nombre_tickets'], 0, '', ' ') }}</div>
                        <div class="small opacity-75">Total Tickets</div>
                    </div>
                    <i class="bi bi-ticket-perforated fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4" id="compte-groupe-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('comptes-groupes.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Nom du chef d'équipe..." value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="chef_id" class="form-label">Chef d'équipe</label>
                            <select name="chef_id" id="chef_id" class="form-select">
                                <option value="">Tous les chefs</option>
                                @foreach ($chefsListe as $chef)
                                    <option value="{{ $chef->id_chef }}" @selected($filters['chef_id'] == $chef->id_chef)>
                                        {{ $chef->full_name }}
                                    </option>
                                @endforeach
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
                        <div class="col-md-6 col-lg-2">
                            <label for="statut_paiement" class="form-label">Statut</label>
                            <select name="statut_paiement" id="statut_paiement" class="form-select">
                                <option value="">Tous</option>
                                <option value="du" @selected($filters['statut_paiement'] === 'du')>Avec montant dû</option>
                                <option value="solde" @selected($filters['statut_paiement'] === 'solde')>Soldé</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Appliquer les filtres
                            </button>
                            <a href="{{ route('comptes-groupes.index') }}" class="btn btn-secondary btn-sm">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-list-ul"></i> Liste des Chefs d'Équipe</span>
                    <span class="badge bg-primary">{{ $groupes->total() }} chef(s)</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Chef d'Équipe</th>
                                <th class="text-center">Agents</th>
                                <th class="text-center">Tickets</th>
                                <th class="text-center">Financement en attente</th>
                                <th class="text-end">Montant Total</th>
                                <th class="text-end">Payé</th>
                                <th class="text-end">Montant Dû</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groupes as $groupe)
                                @php
                                    $showParams = array_filter([
                                        'groupe' => $groupe->id_chef,
                                        'date_debut' => $filters['date_debut'] ?: null,
                                        'date_fin' => $filters['date_fin'] ?: null,
                                    ]);
                                    $nbDemandes = (int) ($groupe->demandes_paiement_en_attente ?? 0);
                                    $detailsUrl = route('comptes-groupes.show', $showParams)
                                        .($nbDemandes > 0 ? '#demandes-avance-section' : '');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ $detailsUrl }}" class="text-decoration-none fw-semibold">
                                            {{ $groupe->nom_chef }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $groupe->nombre_agents }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div>{{ number_format((int) $groupe->nombre_tickets, 0, '', ' ') }}</div>
                                        @if ((int) $groupe->tickets_non_payes > 0)
                                            <small class="text-danger d-block">({{ $groupe->tickets_non_payes }} non payés)</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($nbDemandes > 0)
                                            <a href="{{ $detailsUrl }}" class="badge bg-danger text-decoration-none">
                                                {{ $nbDemandes }}
                                            </a>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format((float) $groupe->montant_total, 0, '', ' ') }} FCFA</td>
                                    <td class="text-end text-success">{{ number_format((float) $groupe->montant_paye, 0, '', ' ') }} FCFA</td>
                                    <td class="text-end {{ (float) $groupe->montant_du > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ number_format((float) $groupe->montant_du, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ $detailsUrl }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye"></i> Détails
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                                        Aucun chef d'équipe trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($groupes->hasPages())
                    <div class="card-footer">
                        {{ $groupes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
