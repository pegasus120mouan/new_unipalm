@extends('layout.main')

@section('title')
    Départements — {{ $region->nom }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Départements — {{ $region->nom }}</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.index') }}">Ponts</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.regions.index') }}">Régions</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $region->nom }}</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <section class="row mb-3">
        <div class="col-12 d-flex flex-wrap gap-2">
            <a href="{{ route('ponts.regions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour aux régions
            </a>
            @if (filled($region->geojson))
                <button type="button" class="btn btn-outline-primary btn-sm view-region-map-btn"
                    data-bs-toggle="modal" data-bs-target="#viewRegionMapModal"
                    data-id="{{ $region->id }}">
                    <i class="bi bi-map"></i> Carte de la région
                </button>
            @endif
            <a href="{{ route('ponts.departements.index') }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-layers"></i> Tous les départements
            </a>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center">
                    <div class="small opacity-75">Région</div>
                    <div class="fs-5 fw-bold">{{ $region->nom }}</div>
                    @if ($region->code)
                        <div class="small opacity-75 mt-1">Code {{ $region->code }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['total'] }}</div>
                    <div class="small opacity-75">Département(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['with_geojson'] }}</div>
                    <div class="small opacity-75">Avec tracé GeoJSON</div>
                </div>
            </div>
        </div>
    </section>

    @if ($hasMapDepartements)
        <section class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-map"></i> Carte des départements — {{ $region->nom }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="regionDepartementsMap"></div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des départements</span>
                    <span class="text-muted">{{ $departements->total() }} département(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="regions-pont-table-header">
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Nom</th>
                                <th class="text-center">Sous-préf.</th>
                                <th class="text-center">GeoJSON</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($departements as $departement)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $departement->id }}</span></td>
                                    <td class="fw-semibold">{{ $departement->code ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('ponts.departements.sous-prefectures', $departement) }}">
                                            {{ $departement->nom }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('ponts.departements.sous-prefectures', $departement) }}" class="badge bg-info text-decoration-none">
                                            {{ $departement->sous_prefectures_count ?? 0 }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        @if (filled($departement->geojson))
                                            <span class="badge bg-success">Oui</span>
                                        @else
                                            <span class="badge bg-secondary">Non</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (filled($departement->geojson))
                                            <button type="button" class="btn btn-sm btn-outline-primary view-departement-map-btn"
                                                data-bs-toggle="modal" data-bs-target="#viewDepartementMapModal"
                                                data-id="{{ $departement->id }}">
                                                <i class="bi bi-map"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Aucun département pour cette région.
                                        <a href="{{ route('ponts.departements.index') }}">Importer des départements</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $departements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="viewDepartementMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Carte — <span id="viewDepartementName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="departementMap"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewRegionMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Carte — <span id="viewRegionName"></span></h5>
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
        #departementMap, #regionDepartementsMap, #regionMap { height: 60vh; min-height: 400px; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const departementsBaseUrl = @json(url('/ponts/departements'));
            const regionsBaseUrl = @json(url('/ponts/regions'));
            const departementsForMap = @json($departementsForMap);
            const palette = ['#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#f39c12', '#1abc9c', '#e67e22', '#34495e'];

            let departementMap = null;
            let departementLayer = null;
            let regionMap = null;
            let regionLayer = null;
            let regionDepartementLayer = { layer: null };
            let regionPontLayer = { layer: null };

            function initLeafletMap(elementId) {
                const map = L.map(elementId).setView([7.54, -5.55], 7);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);
                return map;
            }

            function parseGeoJsonData(raw) {
                if (!raw) return null;
                if (typeof raw === 'object') return raw;
                return JSON.parse(raw);
            }

            function fitMapToContent(map, layers, fitOptions) {
                let bounds = null;
                (layers || []).forEach(function (layer) {
                    if (!layer || typeof layer.getBounds !== 'function') return;
                    try {
                        const layerBounds = layer.getBounds();
                        if (layerBounds && layerBounds.isValid()) {
                            bounds = bounds ? bounds.extend(layerBounds) : layerBounds;
                        }
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
                    if (!departement.geojson) return;
                    try {
                        const geojson = parseGeoJsonData(departement.geojson);
                        const color = palette[index % palette.length];
                        const layer = L.geoJSON(geojson, {
                            style: { color: color, weight: 2, fillColor: color, fillOpacity: 0.22 },
                        }).bindPopup('<div class="fw-semibold">' + departement.nom + '</div>');
                        layer.addTo(group);
                        layers.push(layer);
                    } catch (e) {}
                });
                if (layers.length) {
                    group.addTo(map);
                    layerHolder.layer = group;
                }
                return layers;
            }

            if (document.getElementById('regionDepartementsMap') && departementsForMap.length) {
                const map = initLeafletMap('regionDepartementsMap');
                const holder = { layer: null };
                renderDepartementLayers(map, departementsForMap, holder);
                if (holder.layer) {
                    fitMapToContent(map, [holder.layer]);
                }
            }

            async function fetchDepartement(id) {
                const res = await fetch(departementsBaseUrl + '/' + id, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                const json = await res.json();
                if (!json.success) throw new Error('Impossible de charger le département.');
                return json.data;
            }

            async function fetchRegion(id) {
                const res = await fetch(regionsBaseUrl + '/' + id, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                const json = await res.json();
                if (!json.success) throw new Error('Impossible de charger la région.');
                return json.data;
            }

            const deptModal = document.getElementById('viewDepartementMapModal');
            if (deptModal) {
                deptModal.addEventListener('shown.bs.modal', async function (event) {
                    const btn = event.relatedTarget;
                    if (!btn || !btn.dataset.id) return;
                    document.getElementById('viewDepartementName').textContent = 'Chargement...';
                    if (!departementMap) departementMap = initLeafletMap('departementMap');
                    if (departementLayer) {
                        departementMap.removeLayer(departementLayer);
                        departementLayer = null;
                    }
                    try {
                        const data = await fetchDepartement(btn.dataset.id);
                        document.getElementById('viewDepartementName').textContent = data.nom || 'Département';
                        const geojson = parseGeoJsonData(data.geojson);
                        departementLayer = L.geoJSON(geojson, {
                            style: { color: '#2980b9', weight: 2, fillOpacity: 0.2 },
                        }).addTo(departementMap);
                        departementMap.fitBounds(departementLayer.getBounds(), { padding: [24, 24], maxZoom: 13 });
                    } catch (e) {
                        alert('Impossible d\'afficher la carte de ce département.');
                    }
                    setTimeout(function () { departementMap.invalidateSize(); }, 150);
                });
            }

            const regionModal = document.getElementById('viewRegionMapModal');
            if (regionModal) {
                regionModal.addEventListener('shown.bs.modal', async function (event) {
                    const btn = event.relatedTarget;
                    if (!btn || !btn.dataset.id) return;
                    document.getElementById('viewRegionName').textContent = 'Chargement...';
                    if (!regionMap) regionMap = initLeafletMap('regionMap');
                    if (regionLayer) {
                        regionMap.removeLayer(regionLayer);
                        regionLayer = null;
                    }
                    renderDepartementLayers(regionMap, [], regionDepartementLayer);
                    try {
                        const region = await fetchRegion(btn.dataset.id);
                        document.getElementById('viewRegionName').textContent = region.nom || 'Région';
                        const hasDepartements = (region.departements || []).length > 0;
                        renderDepartementLayers(regionMap, region.departements || [], regionDepartementLayer);
                        if (region.geojson) {
                            const geojson = parseGeoJsonData(region.geojson);
                            regionLayer = L.geoJSON(geojson, {
                                style: hasDepartements
                                    ? { color: '#435ebe', weight: 1, dashArray: '6 4', fillOpacity: 0 }
                                    : { color: '#435ebe', weight: 2, fillOpacity: 0.12 },
                            }).addTo(regionMap);
                        }
                        const boundsLayers = [];
                        if (regionDepartementLayer.layer && regionDepartementLayer.layer.getLayers().length) {
                            boundsLayers.push(regionDepartementLayer.layer);
                        } else if (regionLayer) {
                            boundsLayers.push(regionLayer);
                        }
                        fitMapToContent(regionMap, boundsLayers);
                    } catch (e) {
                        alert('Impossible d\'afficher la carte de cette région.');
                    }
                    setTimeout(function () { regionMap.invalidateSize(); }, 150);
                });
            }
        });
    </script>
@endpush
