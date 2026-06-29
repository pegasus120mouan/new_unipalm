@extends('layout.main')

@section('title', 'Villages')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Gestion des villages</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.index') }}">Ponts</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.regions.index') }}">Régions</a></li>
                <li class="breadcrumb-item active" aria-current="page">Villages</li>
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

    <section class="row mb-4">
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #16a085 0%, #1abc9c 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['total'] }}</div>
                    <div class="small opacity-75">Villages enregistrés</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['with_coordinates'] }}</div>
                    <div class="small opacity-75">Avec coordonnées GPS</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVillageModal">
                            <i class="bi bi-plus-circle"></i> Enregistrer un village
                        </button>
                        <a href="{{ route('ponts.sous-prefectures.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-grid"></i> Sous-préfectures
                        </a>
                        <a href="{{ route('ponts.regions.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-map"></i> Régions
                        </a>
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
                    <form method="GET" action="{{ route('ponts.villages.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <label for="search" class="form-label">Nom, sous-préfecture, département ou région</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Ex. Adiaké, Abidjan..." value="{{ $search }}">
                        </div>
                        <div class="col-md-6 col-lg-4 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            @if ($search !== '' || $sousPrefectureId)
                                <a href="{{ route('ponts.villages.index') }}" class="btn btn-secondary">
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
                    <span>Liste des villages</span>
                    <span class="text-muted">{{ $villages->total() }} village(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="regions-pont-table-header">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Sous-préfecture</th>
                                <th>Département</th>
                                <th>Région</th>
                                <th>Coordonnées</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($villages as $village)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $village->id }}</span></td>
                                    <td class="fw-semibold">{{ $village->nom }}</td>
                                    <td>{{ $village->sousPrefecture?->nom ?? '—' }}</td>
                                    <td>{{ $village->sousPrefecture?->departement?->nom ?? '—' }}</td>
                                    <td>
                                        @if ($village->sousPrefecture?->departement?->region)
                                            <span class="badge bg-info text-dark">{{ $village->sousPrefecture->departement->region->nom }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($village->hasCoordinates())
                                            <span class="text-success small">
                                                <i class="bi bi-geo-alt"></i>
                                                {{ number_format((float) $village->latitude, 5) }},
                                                {{ number_format((float) $village->longitude, 5) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-warning edit-village-btn"
                                                data-bs-toggle="modal" data-bs-target="#editVillageModal"
                                                data-id="{{ $village->id }}"
                                                data-nom="{{ $village->nom }}"
                                                data-id-region="{{ $village->sousPrefecture?->departement?->region_id }}"
                                                data-id-departement="{{ $village->sousPrefecture?->departement_id }}"
                                                data-sous-prefecture-id="{{ $village->sous_prefecture_id }}"
                                                data-latitude="{{ $village->latitude }}"
                                                data-longitude="{{ $village->longitude }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#deleteVillageModal"
                                                data-id="{{ $village->id }}"
                                                data-label="{{ $village->nom }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Aucun village enregistré. Cliquez sur « Enregistrer un village » pour commencer.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $villages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addVillageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('ponts.villages.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Enregistrer un village</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @if ($regions->isEmpty())
                            <div class="alert alert-warning small">
                                Aucune région enregistrée. Importez d'abord les régions, départements et sous-préfectures.
                            </div>
                        @endif
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="id_region" class="form-label">Région *</label>
                                <select name="id_region" id="id_region" class="form-select @error('id_region') is-invalid @enderror" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region->id }}" @selected(old('id_region') == $region->id)>
                                            {{ $region->nom }}{{ $region->code ? ' ('.$region->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="id_departement" class="form-label">Département *</label>
                                <select name="id_departement" id="id_departement" class="form-select @error('id_departement') is-invalid @enderror" required disabled>
                                    <option value="">— Choisir une région d'abord —</option>
                                </select>
                                @error('id_departement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="sous_prefecture_id" class="form-label">Sous-préfecture *</label>
                                <select name="sous_prefecture_id" id="sous_prefecture_id" class="form-select @error('sous_prefecture_id') is-invalid @enderror" required disabled>
                                    <option value="">— Choisir un département d'abord —</option>
                                </select>
                                @error('sous_prefecture_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-12">
                                <label for="nom" class="form-label">Nom du village *</label>
                                <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                                    value="{{ old('nom') }}" placeholder="Ex. Adiaké" required>
                                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" name="latitude" id="latitude" class="form-control @error('latitude') is-invalid @enderror"
                                    step="any" value="{{ old('latitude') }}" placeholder="5.2893">
                                @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" name="longitude" id="longitude" class="form-control @error('longitude') is-invalid @enderror"
                                    step="any" value="{{ old('longitude') }}" placeholder="-3.9821">
                                @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editVillageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editVillageForm" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="village_id" id="edit_village_id" value="">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier le village</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="edit_id_region" class="form-label">Région *</label>
                                <select name="id_region" id="edit_id_region" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region->id }}">
                                            {{ $region->nom }}{{ $region->code ? ' ('.$region->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_id_departement" class="form-label">Département *</label>
                                <select name="id_departement" id="edit_id_departement" class="form-select" required disabled>
                                    <option value="">— Choisir une région d'abord —</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_sous_prefecture_id" class="form-label">Sous-préfecture *</label>
                                <select name="sous_prefecture_id" id="edit_sous_prefecture_id" class="form-select" required disabled>
                                    <option value="">— Choisir un département d'abord —</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="edit_nom" class="form-label">Nom du village *</label>
                                <input type="text" name="nom" id="edit_nom" class="form-control" required>
                            </div>
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
                            <i class="bi bi-save"></i> Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteVillageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Supprimer le village</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Confirmer la suppression de <strong id="deleteVillageLabel"></strong> ?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" id="deleteVillageForm" class="d-inline">
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
        .regions-pont-table-header { background: #111; }
        .regions-pont-table-header th { color: #fff; border: none; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const villageBaseUrl = @json(url('/ponts/villages'));
            const departementsOptionsUrl = @json(url('/ponts/villages/regions'));
            const sousPrefecturesOptionsUrl = @json(url('/ponts/villages/departements'));

            function setupLocationCascade(config) {
                const regionSelect = document.getElementById(config.regionId);
                const departementSelect = document.getElementById(config.departementId);
                const sousPrefectureSelect = document.getElementById(config.sousPrefectureId);

                if (!regionSelect || !departementSelect || !sousPrefectureSelect) {
                    return { setValues: async function () {}, reset: function () {} };
                }

                function resetDepartements(placeholder) {
                    departementSelect.innerHTML = '';
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = placeholder || '— Sélectionner —';
                    departementSelect.appendChild(option);
                    departementSelect.value = '';
                    departementSelect.disabled = true;
                }

                function resetSousPrefectures(placeholder) {
                    sousPrefectureSelect.innerHTML = '';
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = placeholder || '— Sélectionner —';
                    sousPrefectureSelect.appendChild(option);
                    sousPrefectureSelect.value = '';
                    sousPrefectureSelect.disabled = true;
                }

                function formatLabel(item) {
                    return item.nom + (item.code ? ' (' + item.code + ')' : '');
                }

                async function loadDepartements(regionId, selectedId) {
                    resetDepartements();
                    resetSousPrefectures('— Choisir un département d\'abord —');

                    if (!regionId) {
                        resetDepartements('— Choisir une région d\'abord —');
                        return;
                    }

                    const res = await fetch(departementsOptionsUrl + '/' + regionId + '/departements-options', {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });
                    const json = await res.json();
                    const items = json.data || [];

                    departementSelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = items.length ? '— Sélectionner —' : '— Aucun département —';
                    departementSelect.appendChild(placeholder);

                    items.forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = formatLabel(item);
                        departementSelect.appendChild(option);
                    });

                    departementSelect.disabled = items.length === 0;

                    if (selectedId) {
                        departementSelect.value = String(selectedId);
                    }
                }

                async function loadSousPrefectures(departementId, selectedId) {
                    resetSousPrefectures();

                    if (!departementId) {
                        resetSousPrefectures('— Choisir un département d\'abord —');
                        return;
                    }

                    const res = await fetch(sousPrefecturesOptionsUrl + '/' + departementId + '/sous-prefectures-options', {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });
                    const json = await res.json();
                    const items = json.data || [];

                    sousPrefectureSelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = items.length ? '— Sélectionner —' : '— Aucune sous-préfecture —';
                    sousPrefectureSelect.appendChild(placeholder);

                    items.forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = formatLabel(item);
                        sousPrefectureSelect.appendChild(option);
                    });

                    sousPrefectureSelect.disabled = items.length === 0;

                    if (selectedId) {
                        sousPrefectureSelect.value = String(selectedId);
                    }
                }

                regionSelect.addEventListener('change', async function () {
                    await loadDepartements(regionSelect.value || null, null);
                });

                departementSelect.addEventListener('change', async function () {
                    await loadSousPrefectures(departementSelect.value || null, null);
                });

                return {
                    setValues: async function (regionId, departementId, sousPrefectureId) {
                        regionSelect.value = regionId ? String(regionId) : '';
                        await loadDepartements(regionId || null, departementId || null);
                        if (departementId) {
                            await loadSousPrefectures(departementId, sousPrefectureId || null);
                        } else {
                            resetSousPrefectures('— Choisir un département d\'abord —');
                        }
                    },
                    reset: function () {
                        regionSelect.value = '';
                        resetDepartements('— Choisir une région d\'abord —');
                        resetSousPrefectures('— Choisir un département d\'abord —');
                    },
                };
            }

            const addLocationCascade = setupLocationCascade({
                regionId: 'id_region',
                departementId: 'id_departement',
                sousPrefectureId: 'sous_prefecture_id',
            });

            const editLocationCascade = setupLocationCascade({
                regionId: 'edit_id_region',
                departementId: 'edit_id_departement',
                sousPrefectureId: 'edit_sous_prefecture_id',
            });

            const editForm = document.getElementById('editVillageForm');
            const deleteForm = document.getElementById('deleteVillageForm');

            document.getElementById('addVillageModal')?.addEventListener('hidden.bs.modal', function () {
                addLocationCascade.reset();
            });

            document.getElementById('editVillageModal')?.addEventListener('hidden.bs.modal', function () {
                editLocationCascade.reset();
            });

            document.querySelectorAll('.edit-village-btn').forEach(function (button) {
                button.addEventListener('click', async function () {
                    const id = button.dataset.id;
                    editForm.action = villageBaseUrl + '/' + id;
                    document.getElementById('edit_village_id').value = id;
                    document.getElementById('edit_nom').value = button.dataset.nom || '';
                    document.getElementById('edit_latitude').value = button.dataset.latitude || '';
                    document.getElementById('edit_longitude').value = button.dataset.longitude || '';
                    await editLocationCascade.setValues(
                        button.dataset.idRegion || null,
                        button.dataset.idDepartement || null,
                        button.dataset.sousPrefectureId || null
                    );
                });
            });

            document.querySelectorAll('[data-bs-target="#deleteVillageModal"]').forEach(function (button) {
                if (!button.dataset.id) return;
                button.addEventListener('click', function () {
                    deleteForm.action = villageBaseUrl + '/' + button.dataset.id;
                    document.getElementById('deleteVillageLabel').textContent = button.dataset.label || '';
                });
            });

            @if (old('id_region') && ! old('_method'))
                addLocationCascade.setValues(
                    @json(old('id_region')),
                    @json(old('id_departement')),
                    @json(old('sous_prefecture_id'))
                );
            @endif

            @if ($errors->any() && old('_method') === 'PUT')
                (function () {
                    const villageId = @json(old('village_id'));
                    if (editForm && villageId) {
                        editForm.action = villageBaseUrl + '/' + villageId;
                    }
                    document.getElementById('edit_nom').value = @json(old('nom', ''));
                    document.getElementById('edit_latitude').value = @json(old('latitude', ''));
                    document.getElementById('edit_longitude').value = @json(old('longitude', ''));
                    editLocationCascade.setValues(
                        @json(old('id_region', '')),
                        @json(old('id_departement', '')),
                        @json(old('sous_prefecture_id', ''))
                    );
                    new bootstrap.Modal(document.getElementById('editVillageModal')).show();
                })();
            @elseif ($errors->any() && ! old('_method'))
                new bootstrap.Modal(document.getElementById('addVillageModal')).show();
            @endif
        });
    </script>
@endpush
