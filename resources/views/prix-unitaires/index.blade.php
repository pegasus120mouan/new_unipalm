@extends('layout.main')

@section('title', 'Prix unitaires')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Prix unitaires</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Prix unitaires</li>
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

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrixModal">
                            <i class="bi bi-plus-circle"></i> Enregistrer un prix unitaire
                        </button>
                        <button type="button" class="btn btn-danger" disabled title="Bientôt disponible">
                            <i class="bi bi-printer-fill"></i> Imprimer la liste
                        </button>
                        <a href="#prix-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher
                        </a>
                        <button type="button" class="btn btn-warning" disabled title="Bientôt disponible">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i> Exporter la liste
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="prix-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres de recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('prix-unitaires.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="usine_id" class="form-label">Usine</label>
                            <select name="usine_id" id="usine_id" class="form-select">
                                <option value="">Toutes les usines</option>
                                @foreach ($usines as $usine)
                                    <option value="{{ $usine->id_usine }}" @selected(($filters['usine_id'] ?? '') == $usine->id_usine)>
                                        {{ $usine->nom_usine }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="date_debut" class="form-label">Date début (depuis)</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control"
                                value="{{ $filters['date_debut'] ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="date_fin" class="form-label">Date fin (jusqu'au)</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control"
                                value="{{ $filters['date_fin'] ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="prix_min" class="form-label">Prix min</label>
                            <input type="number" name="prix_min" id="prix_min" class="form-control" step="0.01" min="0"
                                value="{{ $filters['prix_min'] ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="prix_max" class="form-label">Prix max</label>
                            <input type="number" name="prix_max" id="prix_max" class="form-control" step="0.01" min="0"
                                value="{{ $filters['prix_max'] ?? '' }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('prix-unitaires.index') }}" class="btn btn-secondary">
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
                    <span>Liste des prix unitaires</span>
                    <span class="text-muted">{{ $prixUnitaires->total() }} enregistrement(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nom usine</th>
                                <th class="text-end">Prix unitaire</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prixUnitaires as $prixUnitaire)
                                <tr>
                                    <td>{{ $prixUnitaire->usine?->nom_usine ?? '-' }}</td>
                                    <td class="text-end fw-semibold">
                                        @php
                                            $prixAffiche = (float) $prixUnitaire->prix;
                                            $prixAffiche = fmod($prixAffiche, 1.0) == 0.0
                                                ? number_format($prixAffiche, 0, '.', ' ')
                                                : rtrim(rtrim(number_format($prixAffiche, 2, '.', ' '), '0'), '.');
                                        @endphp
                                        {{ $prixAffiche }}
                                    </td>
                                    <td>{{ $prixUnitaire->date_debut?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        @if ($prixUnitaire->date_fin)
                                            {{ $prixUnitaire->date_fin->format('d/m/Y') }}
                                        @else
                                            <span class="badge bg-success">En cours</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                            class="btn btn-warning btn-sm btn-edit-prix"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPrixModal"
                                            data-id="{{ $prixUnitaire->id }}"
                                            data-usine="{{ $prixUnitaire->id_usine }}"
                                            data-prix="{{ fmod((float) $prixUnitaire->prix, 1.0) == 0.0 ? (int) $prixUnitaire->prix : rtrim(rtrim(number_format((float) $prixUnitaire->prix, 2, '.', ''), '0'), '.') }}"
                                            data-debut="{{ $prixUnitaire->date_debut?->format('Y-m-d') }}"
                                            data-fin="{{ $prixUnitaire->date_fin?->format('Y-m-d') }}"
                                            title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucun prix unitaire trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $prixUnitaires->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addPrixModal" tabindex="-1" aria-labelledby="addPrixModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('prix-unitaires.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPrixModalLabel">Enregistrer un prix unitaire</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small mb-3">
                            Ce prix sera appliqué <strong>rétroactivement</strong> à tous les tickets
                            non payés de l’usine dont la date ticket est dans la période
                            (même s’ils ont été saisis avant).
                        </div>
                        <div class="mb-3">
                            <label for="id_usine" class="form-label">Usine</label>
                            <select name="id_usine" id="id_usine"
                                class="form-select @error('id_usine') is-invalid @enderror" required>
                                <option value="">Sélectionner une usine</option>
                                @foreach ($usines as $usine)
                                    <option value="{{ $usine->id_usine }}" @selected(old('id_usine') == $usine->id_usine)>
                                        {{ $usine->nom_usine }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_usine')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="prix" class="form-label">Prix unitaire (FCFA)</label>
                            <input type="number" name="prix" id="prix" step="0.01" min="0.01"
                                class="form-control @error('prix') is-invalid @enderror"
                                value="{{ old('prix') }}" required>
                            @error('prix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="add_date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="add_date_debut"
                                class="form-control @error('date_debut') is-invalid @enderror"
                                value="{{ old('date_debut') }}" required>
                            @error('date_debut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label for="add_date_fin" class="form-label">Date fin <span class="text-muted">(optionnel)</span></label>
                            <input type="date" name="date_fin" id="add_date_fin"
                                class="form-control @error('date_fin') is-invalid @enderror"
                                value="{{ old('date_fin') }}">
                            @error('date_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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

    <div class="modal fade" id="editPrixModal" tabindex="-1" aria-labelledby="editPrixModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editPrixForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPrixModalLabel">Modifier le prix unitaire</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small mb-3">
                            La modification sera appliquée <strong>rétroactivement</strong> aux tickets
                            non payés de l’usine sur cette période.
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_usine" class="form-label">Usine</label>
                            <select name="id_usine" id="edit_id_usine"
                                class="form-select @error('id_usine') is-invalid @enderror" required>
                                <option value="">Sélectionner une usine</option>
                                @foreach ($usines as $usine)
                                    <option value="{{ $usine->id_usine }}">{{ $usine->nom_usine }}</option>
                                @endforeach
                            </select>
                            @error('id_usine')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="edit_prix" class="form-label">Prix unitaire (FCFA)</label>
                            <input type="number" name="prix" id="edit_prix" step="0.01" min="0.01"
                                class="form-control @error('prix') is-invalid @enderror" required>
                            @error('prix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="edit_date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="edit_date_debut"
                                class="form-control @error('date_debut') is-invalid @enderror" required>
                            @error('date_debut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label for="edit_date_fin" class="form-label">Date fin <span class="text-muted">(optionnel)</span></label>
                            <input type="date" name="date_fin" id="edit_date_fin"
                                class="form-control @error('date_fin') is-invalid @enderror">
                            @error('date_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.getElementById('editPrixForm');
            const updateUrlBase = @json(url('/prix-unitaires'));

            document.querySelectorAll('.btn-edit-prix').forEach(function (button) {
                button.addEventListener('click', function () {
                    editForm.action = updateUrlBase + '/' + button.dataset.id;
                    document.getElementById('edit_id_usine').value = button.dataset.usine || '';
                    const rawPrix = button.dataset.prix || '';
                    document.getElementById('edit_prix').value = rawPrix === '' ? '' : String(parseFloat(rawPrix));
                    document.getElementById('edit_date_debut').value = button.dataset.debut || '';
                    document.getElementById('edit_date_fin').value = button.dataset.fin || '';
                });
            });

            @if (session('edit_prix_id') || ($errors->any() && old('_method') === 'PUT'))
                (function () {
                    const editId = @json(session('edit_prix_id') ?? old('edit_prix_id'));
                    if (editId) {
                        editForm.action = updateUrlBase + '/' + editId;
                    }
                    document.getElementById('edit_id_usine').value = @json(old('id_usine', ''));
                    document.getElementById('edit_prix').value = @json(old('prix', ''));
                    document.getElementById('edit_date_debut').value = @json(old('date_debut', ''));
                    document.getElementById('edit_date_fin').value = @json(old('date_fin', ''));
                    new bootstrap.Modal(document.getElementById('editPrixModal')).show();
                })();
            @elseif ($errors->any())
                new bootstrap.Modal(document.getElementById('addPrixModal')).show();
            @endif
        });
    </script>
    @endpush
@endsection
