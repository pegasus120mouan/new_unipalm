@extends('layout.main')

@section('title', 'Liste des banques')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Liste des banques</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Banques</li>
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
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Nombre de banques</h6>
                    <h4 class="mb-0">{{ number_format($stats['total'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Banques actives</h6>
                    <h4 class="mb-0 text-success">{{ number_format($stats['actives'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Solde total</h6>
                    <h4 class="mb-0 text-primary">{{ number_format($stats['solde_total'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBanqueModal">
                            <i class="bi bi-plus-circle"></i> Ajouter une banque
                        </button>
                    </div>
                    <form method="GET" action="{{ route('caisse.banques.index') }}" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher..."
                            value="{{ $search }}" style="min-width: 220px;">
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                        @if ($search !== '')
                            <a href="{{ route('caisse.banques.index') }}" class="btn btn-outline-secondary">
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
                    <span>Banques enregistrées</span>
                    <span class="text-muted">{{ $banques->total() }} banque(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Nom de la banque</th>
                                <th>N° compte</th>
                                <th class="text-end">Solde</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($banques as $banque)
                                <tr>
                                    <td>
                                        <a href="{{ route('caisse.banques.show', $banque) }}" class="fw-semibold text-primary text-decoration-none">
                                            {{ $banque->code_banque }}
                                        </a>
                                    </td>
                                    <td class="fw-semibold">{{ $banque->nom_banque }}</td>
                                    <td>{{ $banque->numero_compte ?: '—' }}</td>
                                    <td class="text-end fw-semibold text-primary">
                                        {{ number_format((float) $banque->solde, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td>
                                        @if ($banque->actif)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#editBanqueModal"
                                                data-banque="{{ json_encode([
                                                    'id' => $banque->id_banque,
                                                    'nom' => $banque->nom_banque,
                                                    'numero_compte' => $banque->numero_compte,
                                                    'solde' => (float) $banque->solde,
                                                    'actif' => $banque->actif,
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#deleteBanqueModal"
                                                data-id="{{ $banque->id_banque }}"
                                                data-nom="{{ $banque->nom_banque }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        @if ($search !== '')
                                            Aucune banque ne correspond à votre recherche.
                                        @else
                                            Aucune banque enregistrée. Cliquez sur « Ajouter une banque ».
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($banques->hasPages())
                        <div class="d-flex justify-content-center mt-3">{{ $banques->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addBanqueModal" tabindex="-1" aria-labelledby="addBanqueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('caisse.banques.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBanqueModalLabel">Ajouter une banque</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_nom_banque" class="form-label">Nom de la banque <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase @error('nom_banque') is-invalid @enderror"
                                id="add_nom_banque" name="nom_banque" value="{{ old('nom_banque') }}"
                                placeholder="Ex : SGBCI, BOA..." required autocapitalize="characters">
                            @error('nom_banque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="add_numero_compte" class="form-label">N° de compte</label>
                            <input type="text" class="form-control @error('numero_compte') is-invalid @enderror"
                                id="add_numero_compte" name="numero_compte" value="{{ old('numero_compte') }}"
                                placeholder="Optionnel">
                        </div>
                        <div class="mb-0">
                            <label for="add_solde_display" class="form-label">Solde initial</label>
                            <div class="input-group">
                                <input type="text" id="add_solde_display" class="form-control"
                                    data-amount-input inputmode="numeric" autocomplete="off"
                                    placeholder="Ex : 0" value="{{ old('solde') ? number_format((float) old('solde'), 0, '', ' ') : '' }}">
                                <input type="hidden" name="solde" id="add_solde" data-amount-target value="{{ old('solde', 0) }}">
                                <span class="input-group-text">FCFA</span>
                            </div>
                            <div class="form-text">Laissez vide ou 0 si le solde est inconnu pour l'instant.</div>
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

    <div class="modal fade" id="editBanqueModal" tabindex="-1" aria-labelledby="editBanqueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editBanqueForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editBanqueModalLabel">Modifier la banque</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nom_banque" class="form-label">Nom de la banque</label>
                            <input type="text" class="form-control text-uppercase" id="edit_nom_banque" name="nom_banque" required autocapitalize="characters">
                        </div>
                        <div class="mb-3">
                            <label for="edit_numero_compte" class="form-label">N° de compte</label>
                            <input type="text" class="form-control" id="edit_numero_compte" name="numero_compte">
                        </div>
                        <div class="mb-3">
                            <label for="edit_solde_display" class="form-label">Solde</label>
                            <div class="input-group">
                                <input type="text" id="edit_solde_display" class="form-control"
                                    data-amount-input inputmode="numeric" autocomplete="off" required>
                                <input type="hidden" name="solde" id="edit_solde" data-amount-target>
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="actif" value="0">
                            <input type="checkbox" class="form-check-input" id="edit_actif" name="actif" value="1">
                            <label class="form-check-label" for="edit_actif">Banque active</label>
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

    <div class="modal fade" id="deleteBanqueModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteBanqueForm" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer la banque</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirmer la suppression de <strong id="deleteBanqueNom"></strong> ?</p>
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
        document.getElementById('editBanqueModal')?.addEventListener('show.bs.modal', function (event) {
            const banque = JSON.parse(event.relatedTarget.getAttribute('data-banque'));
            const soldeDigits = String(Math.round(banque.solde));

            document.getElementById('editBanqueForm').action = @json(url('/caisse/banques')) + '/' + banque.id;
            document.getElementById('edit_nom_banque').value = (banque.nom || '').toUpperCase();
            document.getElementById('edit_numero_compte').value = banque.numero_compte || '';
            document.getElementById('edit_solde_display').value = window.UnipalmAmount.format(soldeDigits);
            document.getElementById('edit_solde').value = soldeDigits;
            document.getElementById('edit_actif').checked = !!banque.actif;
        });

        document.querySelectorAll('#add_nom_banque, #edit_nom_banque').forEach(function (input) {
            input.addEventListener('input', function () {
                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.value = input.value.toUpperCase();
                input.setSelectionRange(start, end);
            });
        });

        document.getElementById('deleteBanqueModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('deleteBanqueForm').action = @json(url('/caisse/banques')) + '/' + button.getAttribute('data-id');
            document.getElementById('deleteBanqueNom').textContent = button.getAttribute('data-nom');
        });

        @if ($errors->any() && ! old('id_banque'))
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addBanqueModal')).show();
            });
        @endif
    </script>
@endpush
