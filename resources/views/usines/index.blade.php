@extends('layout.main')

@section('title', 'Usines')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Usines</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Usines</li>
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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUsineModal">
                            <i class="bi bi-building"></i> Enregistrer une usine
                        </button>
                        <button type="button" class="btn btn-danger" disabled title="Bientôt disponible">
                            <i class="bi bi-printer-fill"></i> Imprimer la liste
                        </button>
                        <a href="#usine-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher une usine
                        </a>
                        <button type="button" class="btn btn-warning" disabled title="Bientôt disponible">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i> Exporter la liste
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="usine-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('usines.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-8 col-lg-6">
                            <label for="search" class="form-label">Nom de l'usine</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Rechercher une usine..." value="{{ $search }}">
                        </div>
                        <div class="col-md-4 col-lg-6 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('usines.index') }}" class="btn btn-secondary">
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
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nom usine</th>
                                <th>Localisation</th>
                                <th class="text-center">Tickets</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($usines as $usine)
                                <tr>
                                    <td>
                                        @if ($usine->hasCoordinates())
                                            <a href="#" class="usine-location-link fw-semibold text-decoration-none"
                                                data-id="{{ $usine->id_usine }}"
                                                data-nom="{{ $usine->nom_usine }}"
                                                title="Voir la carte et les ponts à proximité">
                                                <i class="bi bi-geo-alt-fill text-primary"></i>
                                                {{ $usine->nom_usine }}
                                            </a>
                                        @else
                                            {{ $usine->nom_usine }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($usine->hasCoordinates())
                                            <span class="text-success small">
                                                <i class="bi bi-geo-alt"></i>
                                                {{ number_format((float) $usine->latitude, 5) }},
                                                {{ number_format((float) $usine->longitude, 5) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Non définie</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $usine->tickets_count }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-warning edit-usine-btn"
                                            data-bs-toggle="modal" data-bs-target="#editUsineModal"
                                            data-id="{{ $usine->id_usine }}"
                                            data-nom="{{ $usine->nom_usine }}"
                                            data-latitude="{{ $usine->latitude }}"
                                            data-longitude="{{ $usine->longitude }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
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

                    <div class="d-flex justify-content-center">
                        {{ $usines->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addUsineModal" tabindex="-1" aria-labelledby="addUsineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('usines.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUsineModalLabel">Enregistrer une usine</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom_usine" class="form-label">Nom de l'usine</label>
                            <input type="text" name="nom_usine" id="nom_usine"
                                class="form-control @error('nom_usine') is-invalid @enderror"
                                value="{{ old('nom_usine') }}" placeholder="Nom de l'usine" required autofocus>
                            @error('nom_usine')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" name="latitude" id="latitude"
                                    class="form-control @error('latitude') is-invalid @enderror"
                                    step="any" value="{{ old('latitude') }}" placeholder="5.3599517">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" name="longitude" id="longitude"
                                    class="form-control @error('longitude') is-invalid @enderror"
                                    step="any" value="{{ old('longitude') }}" placeholder="-4.0082563">
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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

    <div class="modal fade" id="editUsineModal" tabindex="-1" aria-labelledby="editUsineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editUsineForm" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="edit_usine_id" id="edit_usine_id" value="">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="editUsineModalLabel">Modifier l'usine</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nom_usine" class="form-label">Nom de l'usine</label>
                            <input type="text" name="nom_usine" id="edit_nom_usine" class="form-control" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_latitude" class="form-label">Latitude</label>
                                <input type="number" name="latitude" id="edit_latitude" class="form-control" step="any">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_longitude" class="form-label">Longitude</label>
                                <input type="number" name="longitude" id="edit_longitude" class="form-control" step="any">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->any() && ! old('edit_usine_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addUsineModal')).show();
            });
        </script>
    @endif

    @if ($errors->any() && old('edit_usine_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const usineBaseUrl = @json(url('/usines'));
                const usineId = @json(old('edit_usine_id'));
                const form = document.getElementById('editUsineForm');
                form.action = `${usineBaseUrl}/${usineId}`;
                document.getElementById('edit_usine_id').value = usineId;
                document.getElementById('edit_nom_usine').value = @json(old('nom_usine', ''));
                document.getElementById('edit_latitude').value = @json(old('latitude', ''));
                document.getElementById('edit_longitude').value = @json(old('longitude', ''));
                new bootstrap.Modal(document.getElementById('editUsineModal')).show();
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const usineBaseUrl = @json(url('/usines'));

            document.querySelectorAll('.edit-usine-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const usineId = button.dataset.id;
                    const form = document.getElementById('editUsineForm');
                    form.action = `${usineBaseUrl}/${usineId}`;
                    document.getElementById('edit_usine_id').value = usineId;
                    document.getElementById('edit_nom_usine').value = button.dataset.nom || '';
                    document.getElementById('edit_latitude').value = button.dataset.latitude || '';
                    document.getElementById('edit_longitude').value = button.dataset.longitude || '';
                });
            });
        });
    </script>

    @include('usines.partials.location-modal')
@endsection
