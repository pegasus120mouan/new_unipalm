@extends('layout.main')

@section('title', $cca->nom)

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>{{ $cca->nom }}</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('caisse.cca.index') }}">CCA</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $cca->nom }}</li>
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
        <div class="col-12 d-flex flex-wrap gap-2">
            <a href="{{ route('caisse.cca.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#rembourserCcaModal"
                @disabled($resteCaisse <= 0.009)
                title="{{ $resteCaisse <= 0.009 ? 'Le prêt à la caisse est soldé. Le prêt d\'un agent se rembourse depuis sa fiche.' : '' }}">
                <i class="bi bi-arrow-return-left"></i> Rembourser
            </button>
        </div>
    </section>

    <section class="row">
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Total prêté</h6>
                    <h4 class="mb-0 text-primary">{{ number_format((float) $cca->total_prete, 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Remboursé</h6>
                    <h4 class="mb-0 text-success">{{ number_format((float) $cca->total_rembourse, 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Reste à rembourser</h6>
                    <h4 class="mb-0 text-danger">
                        {{ number_format($reste, 0, ',', ' ') }} FCFA
                    </h4>
                    <p class="text-muted small mb-0 mt-1">
                        Prêt caisse : {{ number_format($resteCaisse, 0, ',', ' ') }} FCFA
                        · Prêts agents : {{ number_format(max(0, $reste - $resteCaisse), 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Historique de {{ $cca->nom }}</span>
                    <span class="text-muted">{{ $mouvements->total() }} mouvement(s)</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('caisse.cca.show', $cca) }}" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <select name="type" class="form-select">
                                <option value="all" @selected($filters['type'] === 'all')>Tous les mouvements</option>
                                <option value="pret_caisse" @selected($filters['type'] === 'pret_caisse')>Prêts à la caisse</option>
                                <option value="pret_agent" @selected($filters['type'] === 'pret_agent')>Prêts agents</option>
                                <option value="remboursement" @selected($filters['type'] === 'remboursement')>Remboursements</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_debut" class="form-control" value="{{ $filters['date_debut'] }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_fin" class="form-control" value="{{ $filters['date_fin'] }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Libellé, chèque..."
                                value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
                            @if ($filters['type'] !== 'all' || $filters['search'] !== '' || $filters['date_debut'] || $filters['date_fin'])
                                <a href="{{ route('caisse.cca.show', $cca) }}" class="btn btn-outline-secondary">
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
                                    <th>Type</th>
                                    <th>Libellé</th>
                                    <th>Origine</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-end">Solde après</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mouvements as $mouvement)
                                    <tr>
                                        <td>{{ $mouvement->date_mouvement?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if ($mouvement->type_mouvement === \App\Models\CcaMouvement::TYPE_REMBOURSEMENT)
                                                <span class="badge bg-success">Remboursement</span>
                                            @elseif ($mouvement->type_mouvement === \App\Models\CcaMouvement::TYPE_PRET_AGENT)
                                                <span class="badge bg-warning text-dark">Prêt agent</span>
                                            @else
                                                <span class="badge bg-primary">Prêt caisse</span>
                                            @endif
                                        </td>
                                        <td>{{ $mouvement->libelle ?: '—' }}</td>
                                        <td>
                                            @if ($mouvement->source === \App\Models\CcaMouvement::SOURCE_BANQUE)
                                                Banque {{ $mouvement->banque?->nom_banque }}
                                                @if ($mouvement->mode_paiement)
                                                    — {{ $mouvement->mode_paiement }}
                                                @endif
                                                @if ($mouvement->numero_cheque)
                                                    n° {{ $mouvement->numero_cheque }}
                                                @endif
                                            @elseif ($mouvement->source === \App\Models\CcaMouvement::SOURCE_CAISSE || $mouvement->type_mouvement === \App\Models\CcaMouvement::TYPE_PRET_CAISSE)
                                                Caisse
                                            @elseif ($mouvement->source === \App\Models\CcaMouvement::SOURCE_PRET_AGENT)
                                                Prêt agent
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold {{ $mouvement->diminueLaDette() ? 'text-success' : 'text-primary' }}">
                                            {{ $mouvement->diminueLaDette() ? '−' : '+' }}{{ number_format((float) $mouvement->montant, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="text-end">{{ number_format((float) $mouvement->solde_apres, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ trim($mouvement->utilisateur?->full_name ?? '') ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Aucun mouvement pour ce CCA.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($mouvements->hasPages())
                        <div class="d-flex justify-content-center mt-3">{{ $mouvements->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @php($rembSource = old('source', 'banque'))
    @php($rembMode = old('mode_paiement', 'Virement'))
    <div class="modal fade" id="rembourserCcaModal" tabindex="-1" aria-labelledby="rembourserCcaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('caisse.cca.rembourser') }}">
                    @csrf
                    <input type="hidden" name="id_cca" value="{{ $cca->id_cca }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rembourserCcaModalLabel">Rembourser {{ $cca->nom }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @error('rembourser')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror

                        <div class="alert alert-light border mb-3">
                            Reste du prêt caisse :
                            <strong class="text-danger">{{ number_format($resteCaisse, 0, ',', ' ') }} FCFA</strong>
                            <div class="small text-muted mt-1">Le prêt d'un agent se rembourse depuis sa fiche.</div>
                        </div>

                        <div class="mb-3">
                            <label for="remb_source" class="form-label">Source <span class="text-danger">*</span></label>
                            <select name="source" id="remb_source" class="form-select" required>
                                <option value="banque" @selected($rembSource === 'banque')>Banque</option>
                                <option value="caisse" @selected($rembSource === 'caisse')>Caisse</option>
                            </select>
                        </div>

                        <div data-remb-block="banque" class="@if ($rembSource !== 'banque') d-none @endif">
                            <div class="mb-3">
                                <label for="remb_id_banque" class="form-label">Banque <span class="text-danger">*</span></label>
                                <select name="id_banque" id="remb_id_banque" class="form-select @error('id_banque') is-invalid @enderror">
                                    <option value="">Sélectionner une banque</option>
                                    @foreach ($banques as $banque)
                                        <option value="{{ $banque->id_banque }}"
                                            @selected((int) old('id_banque') === (int) $banque->id_banque)>
                                            {{ $banque->nom_banque }} — {{ number_format((float) $banque->solde, 0, ',', ' ') }} FCFA
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_banque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="remb_mode" class="form-label">Mode <span class="text-danger">*</span></label>
                                <select name="mode_paiement" id="remb_mode" class="form-select @error('mode_paiement') is-invalid @enderror">
                                    <option value="Virement" @selected($rembMode === 'Virement')>Virement</option>
                                    <option value="Chèque" @selected($rembMode === 'Chèque')>Chèque</option>
                                </select>
                                @error('mode_paiement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3 @if ($rembMode !== 'Chèque') d-none @endif" id="remb_cheque_block">
                                <label for="remb_numero_cheque" class="form-label">Numéro du chèque <span class="text-danger">*</span></label>
                                <input type="text" name="numero_cheque" id="remb_numero_cheque"
                                    class="form-control @error('numero_cheque') is-invalid @enderror"
                                    value="{{ old('numero_cheque') }}" autocomplete="off">
                                @error('numero_cheque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div data-remb-block="caisse" class="mb-3 @if ($rembSource !== 'caisse') d-none @endif">
                            <div class="alert alert-light border mb-0">
                                <span class="text-muted">Montant utilisable en caisse :</span>
                                <strong class="text-primary">{{ number_format($montantUtilisableCaisse, 0, ',', ' ') }} FCFA</strong>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="remb_montant_display" class="form-label">Montant <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="remb_montant_display"
                                    class="form-control @error('montant') is-invalid @enderror"
                                    data-amount-input inputmode="numeric" autocomplete="off"
                                    placeholder="Ex : 500 000" required
                                    value="{{ old('montant') ? number_format((float) old('montant'), 0, '', ' ') : '' }}">
                                <input type="hidden" name="montant" id="remb_montant" data-amount-target value="{{ old('montant') }}">
                                <span class="input-group-text">FCFA</span>
                            </div>
                            @error('montant')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Rembourser</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    <script>
        (function () {
            const source = document.getElementById('remb_source');
            const banque = document.getElementById('remb_id_banque');
            const chequeBlock = document.getElementById('remb_cheque_block');
            const chequeInput = document.getElementById('remb_numero_cheque');
            const mode = document.getElementById('remb_mode');

            if (!source || !banque || !mode) {
                return;
            }

            function update() {
                const estBanque = source.value === 'banque';

                document.querySelectorAll('[data-remb-block]').forEach(function (block) {
                    block.classList.toggle('d-none', block.dataset.rembBlock !== source.value);
                });

                banque.required = estBanque;
                banque.disabled = !estBanque;
                mode.disabled = !estBanque;

                const estCheque = estBanque && mode.value === 'Chèque';
                chequeBlock.classList.toggle('d-none', !estCheque);
                chequeInput.required = estCheque;
                chequeInput.disabled = !estCheque;
            }

            source.addEventListener('change', update);
            mode.addEventListener('change', update);
            update();
        })();

        @if (session('open_rembourser_modal') || $errors->hasAny(['rembourser', 'id_cca', 'source', 'id_banque', 'mode_paiement', 'numero_cheque', 'montant']))
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('rembourserCcaModal');
                if (modal) {
                    new bootstrap.Modal(modal).show();
                }
            });
        @endif
    </script>
@endpush
