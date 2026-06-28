@extends('layout.main')

@section('title', 'Types de ponts')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Types de ponts</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.index') }}">Ponts</a></li>
                <li class="breadcrumb-item active" aria-current="page">Types de ponts</li>
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

    @if ($errors->has('type_pont'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('type_pont') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypePontModal">
                            <i class="bi bi-plus-circle"></i> Enregistrer un type
                        </button>
                        @if (auth()->user()->canAccessModule('ponts.index'))
                            <a href="{{ route('ponts.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-list-ul"></i> Liste des ponts
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-funnel"></i> Recherche</div>
                <div class="card-body">
                    <form method="GET" action="{{ route('ponts.types.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <label for="search" class="form-label">Libellé</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Ex. 6 mètres..." value="{{ $search }}">
                        </div>
                        <div class="col-md-6 col-lg-4 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            @if ($search !== '')
                                <a href="{{ route('ponts.types.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                </a>
                            @endif
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
                    <span>Liste des types de ponts</span>
                    <span class="text-muted">{{ $types->total() }} type(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="types-pont-table-header">
                            <tr>
                                <th>#</th>
                                <th>Libellé</th>
                                <th class="text-center">Ponts associés</th>
                                <th>Date création</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($types as $type)
                                <tr>
                                    <td>{{ $type->id_type_pont }}</td>
                                    <td class="fw-semibold">{{ $type->libelle }}</td>
                                    <td class="text-center">
                                        @if ($type->ponts_count > 0)
                                            <span class="badge bg-primary">{{ $type->ponts_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>{{ $type->created_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-warning edit-type-btn"
                                                data-bs-toggle="modal" data-bs-target="#editTypePontModal"
                                                data-id="{{ $type->id_type_pont }}"
                                                data-libelle="{{ $type->libelle }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#deleteTypePontModal"
                                                data-id="{{ $type->id_type_pont }}"
                                                data-label="{{ $type->libelle }}"
                                                data-count="{{ $type->ponts_count }}"
                                                @if ($type->ponts_count > 0) disabled title="Type utilisé par {{ $type->ponts_count }} pont(s)" @endif>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        @if ($search !== '')
                                            Aucun type ne correspond à la recherche.
                                        @else
                                            Aucun type de pont enregistré.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $types->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addTypePontModal" tabindex="-1" aria-labelledby="addTypePontModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('ponts.types.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTypePontModalLabel">Enregistrer un type de pont</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="libelle" class="form-label">Libellé *</label>
                            <input type="text" name="libelle" id="libelle"
                                class="form-control @error('libelle') is-invalid @enderror"
                                value="{{ old('libelle') }}"
                                placeholder="Ex. 21 metres, 6 METRES → enregistré comme 21 mètres" required>
                            @error('libelle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTypePontModal" tabindex="-1" aria-labelledby="editTypePontModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editTypePontForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="editTypePontModalLabel">Modifier le type de pont</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_libelle" class="form-label">Libellé *</label>
                            <input type="text" name="libelle" id="edit_libelle" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteTypePontModal" tabindex="-1" aria-labelledby="deleteTypePontModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteTypePontModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Supprimer le type
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Confirmer la suppression du type <strong id="deleteTypePontLabel"></strong> ?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" id="deleteTypePontForm" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .types-pont-table-header {
            background: #111;
        }

        .types-pont-table-header th {
            color: #fff !important;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            padding: 0.95rem 1rem;
            white-space: nowrap;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.getElementById('editTypePontForm');
            const deleteForm = document.getElementById('deleteTypePontForm');
            const typesBaseUrl = @json(url('/ponts/types'));

            document.querySelectorAll('.edit-type-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    editForm.action = typesBaseUrl + '/' + button.dataset.id;
                    document.getElementById('edit_libelle').value = button.dataset.libelle || '';
                });
            });

            document.querySelectorAll('[data-bs-target="#deleteTypePontModal"]').forEach(function (button) {
                if (!button.dataset.id || button.disabled) {
                    return;
                }

                button.addEventListener('click', function () {
                    deleteForm.action = typesBaseUrl + '/' + button.dataset.id;
                    document.getElementById('deleteTypePontLabel').textContent = button.dataset.label || '';
                });
            });

            @if ($errors->has('libelle') && ! old('_method'))
                new bootstrap.Modal(document.getElementById('addTypePontModal')).show();
            @endif
        });
    </script>
@endpush
