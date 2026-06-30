@extends('layout.main')

@section('title', 'Solde de la caisse')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Solde de la caisse</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Solde de la caisse</li>
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

    <section class="row mb-3">
        <div class="col-12">
            @include('caisse.partials.solde-badge', [
                'stats' => $stats,
                'badgeClass' => 'display-6',
                'redirectTo' => route('caisse.solde.index'),
            ])
        </div>
    </section>

    @if ($canModule('caisse.approvisionnement'))
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#caisseApproModal">
                            <i class="bi bi-cash-coin"></i> Approvisionnement caisse
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Historique des approvisionnements</span>
                    <span class="text-muted">{{ $approvisionnements->total() }} opération(s)</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('caisse.solde.index') }}" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <select name="origine" class="form-select">
                                <option value="all" @selected($filters['origine'] === 'all')>Toutes les origines</option>
                                <option value="manuel" @selected($filters['origine'] === 'manuel')>Manuels</option>
                                <option value="banque" @selected($filters['origine'] === 'banque')>Depuis banques</option>
                                <option value="usine" @selected($filters['origine'] === 'usine')>Paiements usines</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_debut" class="form-control" value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_fin" class="form-control" value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher..."
                                value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
                            @if ($filters['origine'] !== 'all' || $filters['search'] !== '' || $filters['date_debut'] || $filters['date_fin'])
                                <a href="{{ route('caisse.solde.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Montant</th>
                                    <th>Origine</th>
                                    <th>Motif</th>
                                    <th class="text-end">Solde après</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($approService = app(\App\Services\ApprovisionnementService::class))
                                @forelse ($approvisionnements as $appro)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($appro->date_transaction)->format('d/m/Y H:i') }}</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ number_format((float) $appro->montant, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td>
                                            @if ($approService->isUsineSource($appro->source))
                                                <span class="badge bg-info">Usine</span>
                                                {{ $approService->usineNameFromSource($appro->source) }}
                                            @elseif ($approService->isBanqueSource($appro->source))
                                                <span class="badge bg-warning text-dark">Banque</span>
                                                {{ $approService->banqueNameFromSource($appro->source) }}
                                            @else
                                                <span class="badge bg-secondary">Manuel</span>
                                                {{ $appro->source ?: '—' }}
                                            @endif
                                        </td>
                                        <td>{{ $appro->motifs ?: '—' }}</td>
                                        <td class="text-end">{{ number_format((float) $appro->solde, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ trim($appro->nom_utilisateur) ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Aucun approvisionnement enregistré.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($approvisionnements->hasPages())
                        <div class="d-flex justify-content-center mt-3">{{ $approvisionnements->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($canModule('caisse.approvisionnement'))
        <div class="modal fade" id="caisseApproModal" tabindex="-1" aria-labelledby="caisseApproModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('caisse.approvisionnement.store') }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ route('caisse.solde.index') }}">
                        <div class="modal-header">
                            <h5 class="modal-title" id="caisseApproModalLabel">Approvisionnement caisse</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-0">
                                <label for="caisse_montant_display" class="form-label">Montant <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="caisse_montant_display"
                                        class="form-control @error('montant') is-invalid @enderror"
                                        data-amount-input inputmode="numeric" autocomplete="off"
                                        placeholder="Ex : 500 000" required
                                        value="{{ old('montant') ? number_format((float) old('montant'), 0, '', ' ') : '' }}">
                                    <input type="hidden" name="montant" id="caisse_montant" data-amount-target value="{{ old('montant') }}">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                                @error('montant')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    @if ($errors->has('montant') || request()->query('open_appro') || session('open_appro_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('caisseApproModal')).show();
            });
        </script>
    @endif
    @if ($errors->has('montant_utilisable') || session('open_utilisable_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('utilisableCaisseModal')).show();
            });
        </script>
    @endif
@endpush
