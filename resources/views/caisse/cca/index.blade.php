@extends('layout.main')

@section('title', 'CCA (Comptes courants associés)')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>CCA (Comptes courants associés)</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">CCA</li>
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

    <section class="row">
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Nombre de CCA</h6>
                    <h4 class="mb-0">{{ number_format($stats['total'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Total prêté</h6>
                    <h4 class="mb-0 text-primary">{{ number_format($stats['total_prete'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Total remboursé</h6>
                    <h4 class="mb-0 text-success">{{ number_format($stats['total_rembourse'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Reste à rembourser</h6>
                    <h4 class="mb-0 text-danger">{{ number_format($stats['reste'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCcaModal">
                            <i class="bi bi-plus-circle"></i> Ajouter un CCA
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#rembourserCcaModal"
                            @disabled($ccasRemboursables->isEmpty())
                            title="{{ $ccasRemboursables->isEmpty() ? 'Aucun CCA à rembourser' : '' }}">
                            <i class="bi bi-arrow-return-left"></i> Rembourser
                        </button>
                    </div>
                    <form method="GET" action="{{ route('caisse.cca.index') }}" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher..."
                            value="{{ $search }}" style="min-width: 220px;">
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                        @if ($search !== '')
                            <a href="{{ route('caisse.cca.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des CCA</span>
                    <span class="text-muted">{{ $ccas->total() }} CCA</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th class="text-end">Total prêté</th>
                                <th class="text-end">Remboursé</th>
                                <th class="text-end">Reste à rembourser</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ccas as $cca)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('caisse.cca.show', $cca) }}" class="text-decoration-none">{{ $cca->nom }}</a>
                                    </td>
                                    <td class="text-end fw-semibold text-primary">
                                        {{ number_format((float) $cca->total_prete, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="text-end text-success">
                                        {{ number_format((float) $cca->total_rembourse, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="text-end fw-semibold text-danger">
                                        {{ number_format(max(0, (float) $cca->total_prete - (float) $cca->total_rembourse), 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#editCcaModal"
                                                data-cca="{{ json_encode([
                                                    'id' => $cca->id_cca,
                                                    'nom' => $cca->nom,
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            @if ($cca->mouvements_count === 0)
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#deleteCcaModal"
                                                    data-id="{{ $cca->id_cca }}"
                                                    data-nom="{{ $cca->nom }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        @if ($search !== '')
                                            Aucun CCA ne correspond à votre recherche.
                                        @else
                                            Aucun CCA enregistré. Cliquez sur « Ajouter un CCA ».
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($ccas->hasPages())
                        <div class="d-flex justify-content-center mt-3">{{ $ccas->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addCcaModal" tabindex="-1" aria-labelledby="addCcaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('caisse.cca.store') }}">
                    @csrf
                    <input type="hidden" name="form_mode" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCcaModalLabel">Ajouter un CCA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-0">
                            <label for="add_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                id="add_nom" name="nom" value="{{ old('nom') }}" placeholder="Nom de l'associé" required>
                            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php($rembSource = old('source', 'banque'))
    @php($rembMode = old('mode_paiement', 'Virement'))
    <div class="modal fade" id="rembourserCcaModal" tabindex="-1" aria-labelledby="rembourserCcaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('caisse.cca.rembourser') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="rembourserCcaModalLabel">Rembourser un CCA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @error('rembourser')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror

                        <div class="mb-3">
                            <label for="remb_id_cca" class="form-label">CCA <span class="text-danger">*</span></label>
                            <select name="id_cca" id="remb_id_cca" class="form-select @error('id_cca') is-invalid @enderror" required>
                                <option value="">Sélectionner un CCA</option>
                                @foreach ($ccasRemboursables as $cca)
                                    @php($resteCca = (float) $cca->total_prete - (float) $cca->total_rembourse)
                                    <option value="{{ $cca->id_cca }}" data-reste="{{ $resteCca }}"
                                        @selected((int) old('id_cca') === (int) $cca->id_cca)>
                                        {{ $cca->nom }} — reste {{ number_format($resteCca, 0, ',', ' ') }} FCFA
                                    </option>
                                @endforeach
                            </select>
                            @error('id_cca')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="remb_source" class="form-label">Source <span class="text-danger">*</span></label>
                            <select name="source" id="remb_source" class="form-select" required>
                                <option value="banque" @selected($rembSource === 'banque')>Banque</option>
                                <option value="caisse" @selected($rembSource === 'caisse')>Caisse</option>
                                <option value="pret_agent" @selected($rembSource === 'pret_agent')>Prêts agents</option>
                            </select>
                        </div>

                        <div data-remb-block="banque" class="@if ($rembSource !== 'banque') d-none @endif">
                            <div class="mb-3">
                                <label for="remb_id_banque" class="form-label">Banque <span class="text-danger">*</span></label>
                                <select name="id_banque" id="remb_id_banque" class="form-select @error('id_banque') is-invalid @enderror">
                                    <option value="">Sélectionner une banque</option>
                                    @foreach ($banques as $banque)
                                        <option value="{{ $banque->id_banque }}" data-solde="{{ (float) $banque->solde }}"
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

                        <div data-remb-block="pret_agent" class="mb-3 @if ($rembSource !== 'pret_agent') d-none @endif">
                            <label for="remb_id_agent" class="form-label">Agent <span class="text-danger">*</span></label>
                            <select name="id_agent" id="remb_id_agent" class="form-select @error('id_agent') is-invalid @enderror">
                                <option value="">Sélectionner un agent</option>
                            </select>
                            @error('id_agent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <p class="form-text mb-0" id="remb_agent_vide" hidden>Ce CCA n'a aucun prêt agent encore dû.</p>
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
                            <div class="form-text" id="remb_reste_info"></div>
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

    <div class="modal fade" id="editCcaModal" tabindex="-1" aria-labelledby="editCcaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editCcaForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCcaModalLabel">Modifier le CCA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-0">
                            <label for="edit_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteCcaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteCcaForm" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer le CCA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirmer la suppression de <strong id="deleteCcaNom"></strong> ?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
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
            const cca = document.getElementById('remb_id_cca');
            const chequeBlock = document.getElementById('remb_cheque_block');
            const chequeInput = document.getElementById('remb_numero_cheque');
            const resteInfo = document.getElementById('remb_reste_info');
            const mode = document.getElementById('remb_mode');
            const agent = document.getElementById('remb_id_agent');
            const agentVide = document.getElementById('remb_agent_vide');
            const montantDisplay = document.getElementById('remb_montant_display');
            const pretsParCca = @json($pretsAgentsParCca);
            const agentSelectionne = @json((string) old('id_agent', ''));

            if (!source) {
                return;
            }

            function formatFcfa(valeur) {
                return new Intl.NumberFormat('fr-FR').format(Math.round(valeur)) + ' FCFA';
            }

            function remplirMontant(valeur) {
                if (!montantDisplay || !valeur) {
                    return;
                }
                montantDisplay.value = String(Math.round(valeur));
                montantDisplay.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function remplirAgents() {
                if (!agent || !cca) {
                    return;
                }
                const lignes = pretsParCca[cca.value] || [];
                const courant = agent.value || agentSelectionne;
                agent.innerHTML = '<option value="">Sélectionner un agent</option>';
                lignes.forEach(function (ligne) {
                    const option = document.createElement('option');
                    option.value = String(ligne.id_agent);
                    option.dataset.reste = String(ligne.reste);
                    option.textContent = ligne.nom + ' — ' + formatFcfa(ligne.reste);
                    if (String(ligne.id_agent) === String(courant)) {
                        option.selected = true;
                    }
                    agent.appendChild(option);
                });
                if (agentVide) {
                    agentVide.hidden = lignes.length > 0;
                }
            }

            function update(remplir) {
                const estBanque = source.value === 'banque';
                const estPret = source.value === 'pret_agent';

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

                const option = cca.options[cca.selectedIndex];
                const resteCca = option && option.value ? parseFloat(option.dataset.reste || '0') : 0;
                resteInfo.textContent = option && option.value
                    ? 'Reste à rembourser : ' + formatFcfa(resteCca)
                    : '';

                if (agent) {
                    agent.required = estPret;
                    agent.disabled = !estPret;
                    const agentOption = agent.options[agent.selectedIndex];
                    if (remplir && estPret && agentOption && agentOption.value) {
                        const resteAgent = parseFloat(agentOption.dataset.reste || '0');
                        remplirMontant(Math.min(resteAgent, resteCca || resteAgent));
                    }
                }
            }

            source.addEventListener('change', function () { update(true); });
            cca.addEventListener('change', function () {
                remplirAgents();
                update(true);
            });
            mode.addEventListener('change', function () { update(false); });
            agent?.addEventListener('change', function () { update(true); });
            remplirAgents();
            update(false);
        })();

        @if (session('open_rembourser_modal') || $errors->hasAny(['rembourser', 'id_cca', 'source', 'id_banque', 'mode_paiement', 'numero_cheque', 'id_agent', 'montant']))
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('rembourserCcaModal')).show();
            });
        @endif

        document.getElementById('editCcaModal')?.addEventListener('show.bs.modal', function (event) {
            const cca = JSON.parse(event.relatedTarget.getAttribute('data-cca'));

            document.getElementById('editCcaForm').action = @json(url('/caisse/cca')) + '/' + cca.id;
            document.getElementById('edit_nom').value = cca.nom || '';
        });

        document.getElementById('deleteCcaModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('deleteCcaForm').action = @json(url('/caisse/cca')) + '/' + button.getAttribute('data-id');
            document.getElementById('deleteCcaNom').textContent = button.getAttribute('data-nom');
        });

        @if ($errors->has('nom') && old('form_mode') === 'add')
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addCcaModal')).show();
            });
        @endif
    </script>
@endpush
