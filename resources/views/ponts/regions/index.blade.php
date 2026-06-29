@extends('layout.main')

@section('title', 'Gestion des régions')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Gestion des régions</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.index') }}">Ponts</a></li>
                <li class="breadcrumb-item active" aria-current="page">Régions</li>
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

    @if (session('import_warnings'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Import — détails :</strong>
            <ul class="mb-0 mt-2">
                @foreach (session('import_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row mb-4">
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['total'] }}</div>
                    <div class="small opacity-75">Régions enregistrées</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['with_geojson'] }}</div>
                    <div class="small opacity-75">Avec tracé GeoJSON</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importGeoJsonModal">
                            <i class="bi bi-upload"></i> Importer GeoJSON
                        </button>
                        <a href="{{ route('ponts.departements.index') }}" class="btn btn-outline-success">
                            <i class="bi bi-layers"></i> Départements
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRegionModal">
                            <i class="bi bi-plus-circle"></i> Enregistrer une région
                        </button>
                        @if (auth()->user()->canAccessModule('ponts.location'))
                            <a href="{{ route('ponts.location') }}" class="btn btn-outline-info">
                                <i class="bi bi-geo-alt"></i> Localisation des ponts
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($hasMapRegions)
        <section class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span><i class="bi bi-map"></i> Carte des régions importées</span>
                        <span class="text-muted small" id="allRegionsMapCount">{{ $stats['with_geojson'] }} tracé(s)</span>
                    </div>
                    <div class="card-body p-0 position-relative">
                        <div id="allRegionsMapLoader" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="text-muted mt-2">Chargement de la carte...</div>
                        </div>
                        <div id="allRegionsMap" class="d-none"></div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-funnel"></i> Recherche</div>
                <div class="card-body">
                    <form method="GET" action="{{ route('ponts.regions.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <label for="search" class="form-label">Code ou nom</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Ex. ABJ, Abidjan..." value="{{ $search }}">
                        </div>
                        <div class="col-md-6 col-lg-4 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            @if ($search !== '')
                                <a href="{{ route('ponts.regions.index') }}" class="btn btn-secondary">
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
                    <span>Liste des régions</span>
                    <span class="text-muted">{{ $regions->total() }} région(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="regions-pont-table-header">
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Nom</th>
                                <th class="text-center">Départements</th>
                                <th class="text-center">GeoJSON</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($regions as $region)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $region->id }}</span></td>
                                    <td class="fw-semibold">{{ $region->code ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('ponts.regions.departements', $region) }}" class="fw-semibold text-decoration-none">
                                            {{ $region->nom ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('ponts.regions.departements', $region) }}" class="badge bg-info text-decoration-none">
                                            {{ $region->departements_count }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        @if (filled($region->geojson))
                                            <span class="badge bg-success">Oui</span>
                                        @else
                                            <span class="badge bg-secondary">Non</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('ponts.regions.departements', $region) }}"
                                                class="btn btn-sm btn-outline-info" title="Voir les départements">
                                                <i class="bi bi-layers"></i>
                                            </a>
                                            @if (filled($region->geojson))
                                                <button type="button" class="btn btn-sm btn-outline-primary view-map-btn"
                                                    data-bs-toggle="modal" data-bs-target="#viewRegionMapModal"
                                                    data-id="{{ $region->id }}">
                                                    <i class="bi bi-map"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-warning edit-region-btn"
                                                data-bs-toggle="modal" data-bs-target="#editRegionModal"
                                                data-id="{{ $region->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#deleteRegionModal"
                                                data-id="{{ $region->id }}"
                                                data-label="{{ $region->nom ?? $region->code }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Aucune région enregistrée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $regions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="importGeoJsonModal" tabindex="-1" aria-labelledby="importGeoJsonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('ponts.regions.import') }}" enctype="multipart/form-data" id="importGeoJsonForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importGeoJsonModalLabel">
                            <i class="bi bi-upload me-2"></i>Importer un fichier GeoJSON
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="geojson_file" class="form-label">Fichier <span class="text-danger">*</span></label>
                            <input type="file" name="geojson_file" id="geojson_file" class="form-control"
                                accept=".json,.geojson,application/json" required>
                            <div class="form-text">
                                Formats acceptés : <code>.geojson</code>, <code>.json</code>.
                                Propriétés reconnues : <code>NomReg</code> / <code>CodReg</code> (ou <code>nom</code> / <code>code</code>).
                                Les districts partageant le même <code>CodReg</code> sont fusionnés en une seule région.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="import_mode" class="form-label">Mode d'import</label>
                            <select name="mode" id="import_mode" class="form-select">
                                <option value="upsert" selected>Créer ou mettre à jour (par code / nom)</option>
                                <option value="create">Créer uniquement (ignorer les doublons)</option>
                            </select>
                        </div>
                        <div id="importPreviewInfo" class="alert alert-info d-none mb-3"></div>
                        <div id="importPreviewMapWrap" class="d-none">
                            <label class="form-label">Aperçu du tracé</label>
                            <div id="importPreviewMap"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Importer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addRegionModal" tabindex="-1" aria-labelledby="addRegionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('ponts.regions.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRegionModalLabel">Enregistrer une région</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="add_code" class="form-label">Code</label>
                                <input type="text" name="code" id="add_code" class="form-control" maxlength="10"
                                    value="{{ old('code') }}" placeholder="Ex. ABJ">
                            </div>
                            <div class="col-md-8">
                                <label for="add_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" id="add_nom" class="form-control" maxlength="100"
                                    value="{{ old('nom') }}" required placeholder="Ex. Abidjan">
                            </div>
                            <div class="col-12">
                                <label for="add_geojson" class="form-label">GeoJSON</label>
                                <input type="file" id="add_geojson_file" class="form-control mb-2" accept=".json,.geojson,application/json">
                                <textarea name="geojson" id="add_geojson" class="form-control font-monospace" rows="8"
                                    placeholder='{"type":"Feature","properties":{"nom":"..."},"geometry":{...}}'>{{ old('geojson') }}</textarea>
                                <div class="form-text">Importez un fichier .geojson ou collez le tracé d'une région (optionnel).</div>
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

    <div class="modal fade" id="editRegionModal" tabindex="-1" aria-labelledby="editRegionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editRegionForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRegionModalLabel">Modifier la région</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="edit_code" class="form-label">Code</label>
                                <input type="text" name="code" id="edit_code" class="form-control" maxlength="10">
                            </div>
                            <div class="col-md-8">
                                <label for="edit_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" id="edit_nom" class="form-control" maxlength="100" required>
                            </div>
                            <div class="col-12">
                                <label for="edit_geojson" class="form-label">GeoJSON</label>
                                <input type="file" id="edit_geojson_file" class="form-control mb-2" accept=".json,.geojson,application/json">
                                <textarea name="geojson" id="edit_geojson" class="form-control font-monospace" rows="8"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteRegionModal" tabindex="-1" aria-labelledby="deleteRegionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteRegionForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteRegionModalLabel">Supprimer la région</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        Confirmer la suppression de <strong id="deleteRegionLabel"></strong> ?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewRegionMapModal" tabindex="-1" aria-labelledby="viewRegionMapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewRegionMapModalLabel">Carte — <span id="viewRegionName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="regionMapPontInfo" class="alert alert-light border-bottom rounded-0 mb-0 py-2 px-3 small d-none"></div>
                    <div id="regionMap"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <style>
        .regions-pont-table-header { background: #111; }
        .regions-pont-table-header th { color: #fff; border: none; }
        #regionMap, #allRegionsMap, #importPreviewMap { height: 60vh; min-height: 400px; }
        #importPreviewMap { height: 45vh; min-height: 280px; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const regionsBaseUrl = @json(url('/ponts/regions'));
            const mapDataUrl = @json(route('ponts.regions.map-data'));
            const pontsMapDataUrl = @json(route('ponts.regions.ponts-map-data'));
            const editForm = document.getElementById('editRegionForm');
            const deleteForm = document.getElementById('deleteRegionForm');
            let regionMap = null;
            let regionLayer = null;
            let regionPontLayer = { layer: null };
            let regionDepartementLayer = { layer: null };
            let allPontsLayer = { layer: null };
            let allRegionsMap = null;
            let importPreviewMap = null;
            let importPreviewLayer = null;

            const palette = ['#435ebe', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#3498db'];

            async function fetchRegion(id) {
                const res = await fetch(regionsBaseUrl + '/' + id, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                const json = await res.json();
                if (!json.success) {
                    throw new Error('Impossible de charger la région.');
                }
                return json.data;
            }

            function bindGeoJsonFileInput(fileInputId, textareaId) {
                const fileInput = document.getElementById(fileInputId);
                const textarea = document.getElementById(textareaId);
                if (!fileInput || !textarea) return;

                fileInput.addEventListener('change', function () {
                    const file = fileInput.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = function () {
                        textarea.value = reader.result;
                    };
                    reader.readAsText(file);
                });
            }

            bindGeoJsonFileInput('add_geojson_file', 'add_geojson');
            bindGeoJsonFileInput('edit_geojson_file', 'edit_geojson');

            function initLeafletMap(elementId) {
                const map = L.map(elementId).setView([7.54, -5.55], 7);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);
                return map;
            }

            function renderPontMarkers(map, ponts, layerHolder) {
                if (layerHolder.layer) {
                    map.removeLayer(layerHolder.layer);
                    layerHolder.layer = null;
                }

                const group = L.layerGroup();
                const bounds = [];

                (ponts || []).forEach(function (pont) {
                    if (!pont.has_coordinates && (!pont.latitude || !pont.longitude)) {
                        return;
                    }

                    const lat = parseFloat(pont.latitude);
                    const lng = parseFloat(pont.longitude);

                    if (Number.isNaN(lat) || Number.isNaN(lng) || (lat === 0 && lng === 0)) {
                        return;
                    }

                    const color = pont.statut === 'Actif' ? '#e74c3c' : '#6c757d';
                    const marker = L.circleMarker([lat, lng], {
                        radius: 9,
                        color: '#fff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.95,
                    });

                    marker.bindPopup(
                        '<div class="fw-semibold">' + (pont.nom_pont || 'Pont') + '</div>' +
                        '<div><code>' + (pont.code_pont || '') + '</code></div>' +
                        '<div class="small mt-1"><strong>Gérant:</strong> ' + (pont.gerant || '—') + '</div>' +
                        '<div class="small"><strong>Statut:</strong> ' + (pont.statut || '—') + '</div>'
                    );

                    marker.addTo(group);
                    bounds.push([lat, lng]);
                });

                if (bounds.length) {
                    group.addTo(map);
                    layerHolder.layer = group;
                }

                return bounds;
            }

            function parseGeoJsonData(raw) {
                if (!raw) {
                    return null;
                }

                if (typeof raw === 'object') {
                    return raw;
                }

                return JSON.parse(raw);
            }

            function fitMapToContent(map, layers, markers, fitOptions) {
                let bounds = null;

                (layers || []).forEach(function (layer) {
                    if (!layer || typeof layer.getBounds !== 'function') {
                        return;
                    }

                    try {
                        const layerBounds = layer.getBounds();
                        if (layerBounds && layerBounds.isValid()) {
                            bounds = bounds ? bounds.extend(layerBounds) : layerBounds;
                        }
                    } catch (e) {}
                });

                (markers || []).forEach(function (marker) {
                    if (!marker || typeof marker.getLatLng !== 'function') {
                        return;
                    }

                    try {
                        const latLng = marker.getLatLng();
                        bounds = bounds ? bounds.extend(latLng) : L.latLngBounds(latLng, latLng);
                    } catch (e) {}
                });

                if (bounds && bounds.isValid()) {
                    map.fitBounds(bounds, fitOptions || { padding: [24, 24], maxZoom: 13 });
                    return true;
                }

                return false;
            }

            function renderDepartementLayers(map, departements, layerHolder) {
                if (layerHolder.layer) {
                    map.removeLayer(layerHolder.layer);
                    layerHolder.layer = null;
                }

                const group = L.layerGroup();
                const layers = [];

                (departements || []).forEach(function (departement, index) {
                    if (!departement.geojson) {
                        return;
                    }

                    try {
                        const geojson = JSON.parse(departement.geojson);
                        const color = palette[index % palette.length];
                        const layer = L.geoJSON(geojson, {
                            style: {
                                color: color,
                                weight: 2,
                                fillColor: color,
                                fillOpacity: 0.22,
                            },
                        }).bindPopup(
                            '<div class="fw-semibold">' + (departement.nom || 'Département') + '</div>' +
                            (departement.code ? '<div><code>' + departement.code + '</code></div>' : '')
                        );

                        layer.addTo(group);
                        layers.push(layer);
                    } catch (e) {
                        console.warn('GeoJSON invalide pour le département', departement.id);
                    }
                });

                if (layers.length) {
                    group.addTo(map);
                    layerHolder.layer = group;
                }

                return layers;
            }

            function updateRegionMapInfo(ponts, regionNom, departements) {
                const infoEl = document.getElementById('regionMapPontInfo');
                if (!infoEl) return;

                const total = (ponts || []).length;
                const geolocalises = (ponts || []).filter(function (p) { return p.has_coordinates; }).length;
                const departementCount = (departements || []).length;

                if (!total && !departementCount) {
                    infoEl.classList.add('d-none');
                    return;
                }

                let message = '';

                if (departementCount) {
                    message += '<i class="bi bi-layers"></i> <span class="text-primary fw-semibold">'
                        + departementCount + ' département(s)</span>';
                }

                if (total) {
                    if (message) message += ' — ';
                    message += '<i class="bi bi-signpost-split"></i> ' + total + ' pont(s) dans ' + regionNom;
                    message += ', <span class="text-danger fw-semibold">' + geolocalises + ' sur la carte</span>';
                    if (geolocalises < total) {
                        message += ' <span class="text-muted">(' + (total - geolocalises) + ' sans GPS)</span>';
                    }
                }

                infoEl.innerHTML = message;
                infoEl.classList.remove('d-none');
            }

            if (document.getElementById('allRegionsMap')) {
                Promise.all([
                    fetch(mapDataUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' }).then(r => r.json()),
                    fetch(pontsMapDataUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' }).then(r => r.json()),
                ]).then(function ([regionsJson, pontsJson]) {
                        const mapRegionsData = regionsJson.data || [];
                        const allPonts = pontsJson.data || [];
                        const loader = document.getElementById('allRegionsMapLoader');
                        const mapEl = document.getElementById('allRegionsMap');

                        if (!mapRegionsData.length && !allPonts.length) {
                            if (loader) loader.classList.add('d-none');
                            return;
                        }

                        if (loader) loader.classList.add('d-none');
                        if (mapEl) mapEl.classList.remove('d-none');

                        allRegionsMap = initLeafletMap('allRegionsMap');
                        const boundsLayers = [];

                        mapRegionsData.forEach(function (region, index) {
                            try {
                                const geojson = JSON.parse(region.geojson);
                                const layer = L.geoJSON(geojson, {
                                    style: {
                                        color: palette[index % palette.length],
                                        weight: 2,
                                        fillOpacity: 0.2,
                                    },
                                }).bindPopup(region.nom || 'Région').addTo(allRegionsMap);
                                boundsLayers.push(layer);
                            } catch (e) {
                                console.warn('GeoJSON invalide pour la région', region.id);
                            }
                        });

                        const pontBounds = renderPontMarkers(allRegionsMap, allPonts, allPontsLayer);
                        const fitGroup = L.featureGroup(boundsLayers);
                        if (allPontsLayer.layer) {
                            allPontsLayer.layer.eachLayer(function (layer) {
                                fitGroup.addLayer(layer);
                            });
                        }
                        if (fitGroup.getLayers().length) {
                            allRegionsMap.fitBounds(fitGroup.getBounds(), { padding: [24, 24] });
                        }
                    })
                    .catch(function () {
                        const loader = document.getElementById('allRegionsMapLoader');
                        if (loader) {
                            loader.innerHTML = '<div class="alert alert-warning m-3">Impossible de charger la carte.</div>';
                        }
                    });
            }

            const importFileInput = document.getElementById('geojson_file');
            const importPreviewInfo = document.getElementById('importPreviewInfo');
            const importPreviewMapWrap = document.getElementById('importPreviewMapWrap');

            if (importFileInput) {
                importFileInput.addEventListener('change', function () {
                    const file = importFileInput.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = function () {
                        try {
                            const data = JSON.parse(reader.result);
                            const features = data.type === 'FeatureCollection'
                                ? (data.features || [])
                                : (data.type === 'Feature' ? [data] : []);

                            importPreviewInfo.textContent = features.length
                                ? features.length + ' entité(s) détectée(s) dans le fichier.'
                                : '1 entité géographique détectée.';
                            importPreviewInfo.classList.remove('d-none');
                            importPreviewMapWrap.classList.remove('d-none');

                            if (!importPreviewMap) {
                                importPreviewMap = initLeafletMap('importPreviewMap');
                            }

                            if (importPreviewLayer) {
                                importPreviewMap.removeLayer(importPreviewLayer);
                            }

                            importPreviewLayer = L.geoJSON(data, {
                                style: { color: '#435ebe', weight: 2, fillOpacity: 0.15 },
                            }).addTo(importPreviewMap);

                            importPreviewMap.fitBounds(importPreviewLayer.getBounds(), { padding: [24, 24] });
                            setTimeout(function () { importPreviewMap.invalidateSize(); }, 150);
                        } catch (e) {
                            importPreviewInfo.textContent = 'Fichier JSON invalide.';
                            importPreviewInfo.classList.remove('d-none');
                            importPreviewMapWrap.classList.add('d-none');
                        }
                    };
                    reader.readAsText(file);
                });
            }

            document.getElementById('importGeoJsonModal')?.addEventListener('hidden.bs.modal', function () {
                importFileInput.value = '';
                importPreviewInfo.classList.add('d-none');
                importPreviewMapWrap.classList.add('d-none');
                if (importPreviewLayer && importPreviewMap) {
                    importPreviewMap.removeLayer(importPreviewLayer);
                    importPreviewLayer = null;
                }
            });

            document.querySelectorAll('.edit-region-btn').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    try {
                        const region = await fetchRegion(btn.dataset.id);
                        editForm.action = regionsBaseUrl + '/' + region.id;
                        document.getElementById('edit_code').value = region.code || '';
                        document.getElementById('edit_nom').value = region.nom || '';
                        document.getElementById('edit_geojson').value = region.geojson || '';
                    } catch (e) {
                        alert(e.message);
                    }
                });
            });

            document.querySelectorAll('[data-bs-target="#deleteRegionModal"]').forEach(function (btn) {
                if (!btn.dataset.id) return;
                btn.addEventListener('click', function () {
                    deleteForm.action = regionsBaseUrl + '/' + btn.dataset.id;
                    document.getElementById('deleteRegionLabel').textContent = btn.dataset.label || 'cette région';
                });
            });

            const mapModal = document.getElementById('viewRegionMapModal');
            if (mapModal) {
                mapModal.addEventListener('shown.bs.modal', async function (event) {
                    const btn = event.relatedTarget;
                    if (!btn || !btn.dataset.id) return;

                    document.getElementById('viewRegionName').textContent = 'Chargement...';

                    if (!regionMap) {
                        regionMap = initLeafletMap('regionMap');
                    }

                    if (regionLayer) {
                        regionMap.removeLayer(regionLayer);
                        regionLayer = null;
                    }
                    renderDepartementLayers(regionMap, [], regionDepartementLayer);
                    renderPontMarkers(regionMap, [], regionPontLayer);

                    try {
                        const region = await fetchRegion(btn.dataset.id);
                        document.getElementById('viewRegionName').textContent = region.nom || 'Région';
                        updateRegionMapInfo(region.ponts || [], region.nom || 'Région', region.departements || []);

                        const hasDepartements = (region.departements || []).length > 0;

                        renderDepartementLayers(regionMap, region.departements || [], regionDepartementLayer);

                        if (region.geojson) {
                            try {
                                const geojson = parseGeoJsonData(region.geojson);
                                regionLayer = L.geoJSON(geojson, {
                                    style: hasDepartements
                                        ? { color: '#435ebe', weight: 1, dashArray: '6 4', fillOpacity: 0 }
                                        : { color: '#435ebe', weight: 2, fillOpacity: 0.12 },
                                }).addTo(regionMap);
                            } catch (geoError) {
                                console.warn('GeoJSON région invalide', geoError);
                                regionLayer = null;
                            }
                        }

                        renderPontMarkers(regionMap, region.ponts || [], regionPontLayer);

                        const boundsLayers = [];
                        if (regionDepartementLayer.layer && regionDepartementLayer.layer.getLayers().length) {
                            boundsLayers.push(regionDepartementLayer.layer);
                        } else if (regionLayer) {
                            boundsLayers.push(regionLayer);
                        }

                        const markerLayers = [];
                        if (regionPontLayer.layer) {
                            regionPontLayer.layer.eachLayer(function (layer) {
                                markerLayers.push(layer);
                            });
                        }

                        if (!fitMapToContent(regionMap, boundsLayers, markerLayers)) {
                            if (!region.geojson && !hasDepartements) {
                                alert('Aucun tracé GeoJSON pour cette région.');
                            }
                        }
                    } catch (e) {
                        console.error('Erreur carte région', e);
                        alert('Impossible d\'afficher la carte de cette région.');
                    }

                    setTimeout(function () { regionMap.invalidateSize(); }, 150);
                });
            }

            @if ($errors->has('geojson_file'))
                new bootstrap.Modal(document.getElementById('importGeoJsonModal')).show();
            @elseif ($errors->any() && old('nom'))
                @if (old('_method') === 'PUT')
                    new bootstrap.Modal(document.getElementById('editRegionModal')).show();
                @else
                    new bootstrap.Modal(document.getElementById('addRegionModal')).show();
                @endif
            @endif
        });
    </script>
@endpush
