@extends('layout.main')

@section('title', 'Montants usines')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Montants usines</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Montants usines</li>
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

    <section class="row mb-3">
        <div class="col-12 col-md-4 mb-3">
            <div class="card border-0 text-white h-100 shadow-sm" style="background-color: #0d6efd;">
                <div class="card-body d-flex align-items-center justify-content-between py-3 px-4">
                    <div>
                        <div class="fw-bold fs-4">
                            {{ number_format((float) $totals->total_montant, 0, '', ' ') }} FCFA
                        </div>
                        <div class="small opacity-75">Total montant</div>
                    </div>
                    <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-3">
            <div class="card border-0 text-white h-100 shadow-sm" style="background-color: #198754;">
                <div class="card-body d-flex align-items-center justify-content-between py-3 px-4">
                    <div>
                        <div class="fw-bold fs-4">
                            {{ number_format((float) $totals->montant_paye, 0, '', ' ') }} FCFA
                        </div>
                        <div class="small opacity-75">Montant payé</div>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-3">
            <div class="card border-0 text-white h-100 shadow-sm" style="background-color: #fd7e14;">
                <div class="card-body d-flex align-items-center justify-content-between py-3 px-4">
                    <div>
                        <div class="fw-bold fs-4">
                            {{ number_format((float) $totals->reste_a_payer, 0, '', ' ') }} FCFA
                        </div>
                        <div class="small opacity-75">Reste à payer</div>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="usine-amounts-filters">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('usines.amounts') }}" class="row g-3 align-items-end">
                        <div class="col-md-8 col-lg-6">
                            <label for="search" class="form-label">Nom de l'usine</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Rechercher une usine..." value="{{ $search }}">
                        </div>
                        <div class="col-md-4 col-lg-6 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('usines.amounts') }}" class="btn btn-secondary">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des usines</span>
                    <span class="text-muted">{{ $usines->total() }} usine(s)</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">Nom usine</th>
                                <th class="text-center align-middle">Total montant</th>
                                <th class="text-center align-middle">Montant payé</th>
                                <th class="text-center align-middle">Reste à payer</th>
                                <th class="align-middle text-center" style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($usines as $usine)
                                <tr>
                                    <td class="align-middle">
                                        <a href="{{ route('usines.amounts.show', $usine) }}" class="d-flex align-items-center gap-2 text-decoration-none">
                                            <i class="bi bi-building-fill text-primary"></i>
                                            <span class="fw-semibold">{{ $usine->nom_usine }}</span>
                                        </a>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="fw-bold text-success fs-6">
                                            {{ number_format((float) $usine->total_montant, 0, '', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="fw-bold text-success fs-6">
                                            {{ number_format((float) $usine->montant_paye, 0, '', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="fw-bold text-success fs-6">
                                            {{ number_format((float) $usine->reste_a_payer, 0, '', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <button type="button"
                                            class="btn btn-success btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paymentModal{{ $usine->id_usine }}"
                                            @if ((float) $usine->reste_a_payer <= 0) disabled title="Aucun montant restant à payer" @endif>
                                            <i class="bi bi-credit-card"></i> Effectuer un paiement
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        @if ($search !== '')
                                            Aucune usine ne correspond à votre recherche.
                                        @else
                                            Aucune usine enregistrée.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($usines->hasPages())
                    <div class="card-footer">
                        {{ $usines->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    @foreach ($usines as $usine)
        @include('usines.partials.payment-modal', [
            'usine' => $usine,
            'banques' => $banques,
            'redirectTo' => route('usines.amounts', request()->only('search', 'page')),
            'resteAPayer' => $usine->reste_a_payer,
        ])
    @endforeach
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    @if (session('payment_usine_id') || $errors->has('paiement') || $errors->has('montant') || $errors->has('id_banque'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var usineId = @json(session('payment_usine_id') ?? old('payment_usine_id'));
                if (! usineId) {
                    return;
                }
                var modalEl = document.getElementById('paymentModal' + usineId);
                if (modalEl) {
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
        </script>
    @endif
@endpush
