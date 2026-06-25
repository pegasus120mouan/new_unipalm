@extends('layout.main')

@section('title', 'Montants usines — '.$usine->nom_usine)

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Montants usines — {{ $usine->nom_usine }}</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('usines.amounts') }}">Montants usines</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $usine->nom_usine }}</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
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

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Détail mensuel — {{ $usine->nom_usine }}</span>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <a href="{{ route('usines.amounts.payments.pdf', $usine) }}" class="btn btn-danger btn-sm" target="_blank">
                            <i class="bi bi-printer"></i> Imprimer historique des paiements
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">Mois</th>
                                <th class="text-center align-middle">Nombre de tickets</th>
                                <th class="text-center align-middle">Total montant</th>
                                <th class="text-center align-middle">Montant payé</th>
                                <th class="text-center align-middle">Reste à payer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($monthlyAmounts as $month)
                                <tr>
                                    <td class="align-middle fw-semibold">
                                        {{ \Carbon\Carbon::createFromFormat('Y-m', $month->mois)->locale('fr')->translatedFormat('F Y') }}
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge bg-info">{{ number_format((int) $month->nombre_tickets, 0, '', ' ') }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="fw-bold text-success fs-6">
                                            {{ number_format((float) $month->total_montant, 0, '', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="fw-bold text-success fs-6">
                                            {{ number_format((float) $month->montant_paye, 0, '', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="fw-bold text-success fs-6">
                                            {{ number_format((float) $month->reste_a_payer, 0, '', ' ') }} FCFA
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucun ticket validé pour cette usine.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
