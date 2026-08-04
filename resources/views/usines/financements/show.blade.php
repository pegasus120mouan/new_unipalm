@extends('layout.main')

@section('title', 'Financement — '.$usine->nom_usine)

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Financement — {{ $usine->nom_usine }}</h3>
            <small class="text-muted">Financements reçus de cette usine</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-success btn-sm"
                data-bs-toggle="modal" data-bs-target="#addUsineFinancementModal">
                <i class="bi bi-plus-lg"></i> Enregistrer un financement
            </button>
            <a href="{{ route('usines.financements.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
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
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 text-white" style="background-color: #198754;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75">Total financé</div>
                        <div class="fw-bold fs-4">
                            {{ number_format($stats['total_financement'], 0, '', ' ') }} FCFA
                        </div>
                    </div>
                    <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 text-white" style="background-color: #0d6efd;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75">Nombre de financements</div>
                        <div class="fw-bold fs-4">
                            {{ number_format($stats['nombre_financements'], 0, '', ' ') }}
                        </div>
                    </div>
                    <i class="bi bi-list-check fs-1 opacity-50"></i>
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
                    <form method="GET" action="{{ route('usines.financements.show', $usine) }}" class="row g-3 align-items-end">
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
                            <a href="{{ route('usines.financements.show', $usine) }}" class="btn btn-outline-secondary">
                                Réinitialiser
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
                    <span><i class="bi bi-clock-history me-1"></i> Historique des financements</span>
                    <button type="button" class="btn btn-success btn-sm"
                        data-bs-toggle="modal" data-bs-target="#addUsineFinancementModal">
                        <i class="bi bi-plus-lg"></i> Nouveau
                    </button>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Date</th>
                                <th class="text-end">Montant</th>
                                <th>Banque</th>
                                <th>Mode</th>
                                <th>Référence</th>
                                <th>Motif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($financements as $financement)
                                <tr>
                                    <td><code>{{ $financement->code_affiche }}</code></td>
                                    <td class="fw-semibold">{{ $financement->date_financement?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-end fw-semibold {{ (float) $financement->montant < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format((float) $financement->montant, 0, '', ' ') }} FCFA
                                    </td>
                                    <td>{{ $financement->banque?->nom_banque ?? '—' }}</td>
                                    <td>{{ $financement->mode_paiement ?: '—' }}</td>
                                    <td>{{ $financement->reference_paiement ?: '—' }}</td>
                                    <td>{{ $financement->motif ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Aucun financement enregistré pour cette usine.
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

    @include('usines.financements.partials.add-modal', [
        'usine' => $usine,
        'banques' => $banques,
    ])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    @if (session('open_financement_modal') || $errors->has('financement') || $errors->has('montant') || $errors->has('id_banque'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('addUsineFinancementModal');
                if (modalEl) {
                    (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
                }
            });
        </script>
    @endif
@endpush
