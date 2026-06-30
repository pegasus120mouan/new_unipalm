@extends('layout.main')

@section('title', 'Approvisionnement caisse')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Approvisionnement caisse</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('caisse.banques.index') }}">Banques</a></li>
                <li class="breadcrumb-item active" aria-current="page">Approvisionnement caisse</li>
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

    @if ($errors->has('approvisionnement_caisse'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('approvisionnement_caisse') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row">
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Solde total des banques</h6>
                    <h4 class="mb-0 text-primary">{{ number_format($stats['solde_total'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Banques actives</h6>
                    <h4 class="mb-0 text-success">{{ number_format($stats['nombre_banques'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Total versé vers la caisse</h6>
                    <h4 class="mb-0">{{ number_format($stats['total_verse_caisse'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3 d-flex flex-wrap gap-3 align-items-stretch">
                    <button type="button" class="btn btn-success align-self-center" data-bs-toggle="modal" data-bs-target="#approCaisseModal"
                        @disabled($banques->isEmpty())>
                        <i class="bi bi-cash-coin"></i> Approvisionnement caisse
                    </button>
                    <div class="flex-grow-1" style="min-width: 280px;">
                        @include('caisse.partials.solde-badge', [
                            'stats' => $stats,
                            'modalId' => 'utilisableCaisseBanqueModal',
                            'redirectTo' => route('caisse.banques.approvisionnement-caisse.index'),
                        ])
                    </div>
                    <a href="{{ route('caisse.banques.index') }}" class="btn btn-outline-secondary btn-sm align-self-center">
                        <i class="bi bi-bank"></i> Liste des banques
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Soldes des banques</span>
                    <span class="text-muted">{{ $banques->count() }} banque(s)</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Banque</th>
                                <th>N° compte</th>
                                <th class="text-end">Solde disponible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($banques as $banque)
                                <tr>
                                    <td>
                                        <a href="{{ route('caisse.banques.show', $banque) }}" class="text-decoration-none fw-semibold">
                                            {{ $banque->code_banque }}
                                        </a>
                                    </td>
                                    <td class="fw-semibold">{{ $banque->nom_banque }}</td>
                                    <td>{{ $banque->numero_compte ?: '—' }}</td>
                                    <td class="text-end fw-semibold text-primary">
                                        {{ number_format((float) $banque->solde, 0, ',', ' ') }} FCFA
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Aucune banque active.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Historique banques → caisse</span>
                    <span class="text-muted">{{ $historique->total() }} opération(s)</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('caisse.banques.approvisionnement-caisse.index') }}" class="row g-2 mb-3">
                        <div class="col-md-4">
                            <select name="id_banque" class="form-select">
                                <option value="">Toutes les banques</option>
                                @foreach ($banques as $banque)
                                    <option value="{{ $banque->id_banque }}" @selected((int) ($filters['id_banque'] ?? 0) === (int) $banque->id_banque)>
                                        {{ $banque->nom_banque }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_debut" class="form-control" value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_fin" class="form-control" value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
                            @if ($filters['id_banque'] || $filters['date_debut'] || $filters['date_fin'])
                                <a href="{{ route('caisse.banques.approvisionnement-caisse.index') }}" class="btn btn-outline-secondary">
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
                                    <th>Banque</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-end">Solde banque après</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($historique as $mouvement)
                                    <tr>
                                        <td>{{ $mouvement->date_mouvement?->format('d/m/Y H:i') }}</td>
                                        <td class="fw-semibold">{{ $mouvement->banque?->nom_banque ?? '—' }}</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ number_format((float) $mouvement->montant, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="text-end">
                                            {{ number_format((float) $mouvement->solde_apres, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td>{{ $mouvement->utilisateur?->full_name ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Aucun approvisionnement banque → caisse enregistré.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($historique->hasPages())
                        <div class="d-flex justify-content-center mt-3">{{ $historique->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="approCaisseModal" tabindex="-1" aria-labelledby="approCaisseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('caisse.banques.approvisionnement-caisse.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="approCaisseModalLabel">Approvisionnement caisse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="id_banque" class="form-label">Banque <span class="text-danger">*</span></label>
                            <select name="id_banque" id="id_banque" class="form-select @error('id_banque') is-invalid @enderror" required>
                                <option value="">Sélectionner une banque</option>
                                @foreach ($banques as $banque)
                                    <option value="{{ $banque->id_banque }}"
                                        data-solde="{{ (float) $banque->solde }}"
                                        @selected((int) old('id_banque') === (int) $banque->id_banque)>
                                        {{ $banque->nom_banque }} — {{ number_format((float) $banque->solde, 0, ',', ' ') }} FCFA
                                    </option>
                                @endforeach
                            </select>
                            @error('id_banque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="alert alert-light border mb-3 d-none" id="banqueSoldeAlert">
                            <span class="text-muted">Solde disponible :</span>
                            <strong class="text-primary" id="banqueSoldeMontant">0 FCFA</strong>
                        </div>
                        <div class="mb-0">
                            <label for="appro_montant_display" class="form-label">Montant <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="appro_montant_display"
                                    class="form-control @error('montant') is-invalid @enderror"
                                    data-amount-input inputmode="numeric" autocomplete="off"
                                    placeholder="Ex : 500 000" required
                                    value="{{ old('montant') ? number_format((float) old('montant'), 0, '', ' ') : '' }}">
                                <input type="hidden" name="montant" id="appro_montant" data-amount-target value="{{ old('montant') }}">
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
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    <script>
        function formatFcfa(amount) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA';
        }

        function updateBanqueSolde() {
            const select = document.getElementById('id_banque');
            const alertBox = document.getElementById('banqueSoldeAlert');
            const montantEl = document.getElementById('banqueSoldeMontant');

            if (!select || !alertBox || !montantEl) {
                return;
            }

            const option = select.options[select.selectedIndex];
            const solde = option?.dataset?.solde ? parseFloat(option.dataset.solde) : NaN;

            if (!option?.value || Number.isNaN(solde)) {
                alertBox.classList.add('d-none');
                return;
            }

            alertBox.classList.remove('d-none');
            montantEl.textContent = formatFcfa(solde);
        }

        document.getElementById('id_banque')?.addEventListener('change', updateBanqueSolde);
        document.getElementById('approCaisseModal')?.addEventListener('shown.bs.modal', updateBanqueSolde);

        @if (session('open_appro_caisse_modal') || $errors->has('montant') || $errors->has('id_banque') || request()->query('open_appro'))
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('approCaisseModal')).show();
            });
        @endif

        @if (session('open_utilisable_modal') || $errors->has('montant_utilisable'))
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('utilisableCaisseBanqueModal')).show();
            });
        @endif
    </script>
@endpush
