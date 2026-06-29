@extends('layout.main')

@section('title')
    Sous-préfectures — {{ $departement->nom }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Sous-préfectures — {{ $departement->nom }}</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.index') }}">Ponts</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.regions.index') }}">Régions</a></li>
                @if ($departement->region)
                    <li class="breadcrumb-item">
                        <a href="{{ route('ponts.regions.departements', $departement->region) }}">{{ $departement->region->nom }}</a>
                    </li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $departement->nom }}</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <section class="row mb-3">
        <div class="col-12 d-flex flex-wrap gap-2">
            @if ($departement->region)
                <a href="{{ route('ponts.regions.departements', $departement->region) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour aux départements
                </a>
            @endif
            <a href="{{ route('ponts.sous-prefectures.index') }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-grid"></i> Toutes les sous-préfectures
            </a>
            <a href="{{ route('ponts.sous-prefectures.index') }}" class="btn btn-success btn-sm">
                <i class="bi bi-upload"></i> Importer GeoJSON
            </a>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                <div class="card-body text-center">
                    <div class="small opacity-75">Département</div>
                    <div class="fs-5 fw-bold">{{ $departement->nom }}</div>
                    @if ($departement->code)
                        <div class="small opacity-75 mt-1">Code {{ $departement->code }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['total'] }}</div>
                    <div class="small opacity-75">Sous-préfecture(s)</div>
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

    @if ($hasMapSousPrefectures)
        <section class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-map"></i> Carte des sous-préfectures — {{ $departement->nom }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="departementSousPrefecturesMap"></div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des sous-préfectures</span>
                    <span class="text-muted">{{ $sousPrefectures->total() }} sous-préfecture(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="regions-pont-table-header">
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Nom</th>
                                <th class="text-center">GeoJSON</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sousPrefectures as $sousPrefecture)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $sousPrefecture->id }}</span></td>
                                    <td class="fw-semibold">{{ $sousPrefecture->code ?? '—' }}</td>
                                    <td>{{ $sousPrefecture->nom }}</td>
                                    <td class="text-center">
                                        @if (filled($sousPrefecture->geojson))
                                            <span class="badge bg-success">Oui</span>
                                        @else
                                            <span class="badge bg-secondary">Non</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (filled($sousPrefecture->geojson))
                                            <button type="button" class="btn btn-sm btn-outline-primary view-sous-prefecture-map-btn"
                                                data-bs-toggle="modal" data-bs-target="#viewSousPrefectureMapModal"
                                                data-id="{{ $sousPrefecture->id }}">
                                                <i class="bi bi-map"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucune sous-préfecture pour ce département.
                                        <a href="{{ route('ponts.sous-prefectures.index') }}">Importer un fichier GeoJSON</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $sousPrefectures->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="viewSousPrefectureMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Carte — <span id="viewSousPrefectureName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="sousPrefectureMap"></div>
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
        #sousPrefectureMap, #departementSousPrefecturesMap { height: 60vh; min-height: 400px; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const baseUrl = @json(url('/ponts/sous-prefectures'));
            const sousPrefecturesForMap = @json($sousPrefecturesForMap);
            const palette = ['#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#f39c12', '#1abc9c', '#e67e22', '#34495e'];

            let sousPrefectureMap = null;
            let sousPrefectureLayer = null;

            function initLeafletMap(elementId) {
                const map = L.map(elementId).setView([7.54, -5.55], 7);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);
                return map;
            }

            function fitMapToContent(map, layers) {
                if (!layers.length) return;
                const bounds = L.latLngBounds([]);
                layers.forEach(function (layer) {
                    try {
                        bounds.extend(layer.getBounds());
                    } catch (e) {}
                });
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [24, 24] });
                }
            }

            function renderSousPrefectureLayers(map, items) {
                const layers = [];
                (items || []).forEach(function (item, index) {
                    if (!item.geojson) return;
                    try {
                        const geojson = JSON.parse(item.geojson);
                        const layer = L.geoJSON(geojson, {
                            style: {
                                color: palette[index % palette.length],
                                weight: 2,
                                fillOpacity: 0.25,
                            },
                        }).bindPopup(item.nom || 'Sous-préfecture').addTo(map);
                        layers.push(layer);
                    } catch (e) {
                        console.warn('GeoJSON invalide', item.id);
                    }
                });
                fitMapToContent(map, layers);
            }

            if (document.getElementById('departementSousPrefecturesMap') && sousPrefecturesForMap.length) {
                const map = initLeafletMap('departementSousPrefecturesMap');
                renderSousPrefectureLayers(map, sousPrefecturesForMap);
            }

            async function fetchSousPrefecture(id) {
                const res = await fetch(baseUrl + '/' + id, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                const json = await res.json();
                if (!json.success) {
                    throw new Error('Impossible de charger la sous-préfecture.');
                }
                return json.data;
            }

            const mapModal = document.getElementById('viewSousPrefectureMapModal');
            if (mapModal) {
                mapModal.addEventListener('shown.bs.modal', async function (event) {
                    const btn = event.relatedTarget;
                    if (!btn || !btn.dataset.id) return;

                    document.getElementById('viewSousPrefectureName').textContent = 'Chargement...';

                    if (!sousPrefectureMap) {
                        sousPrefectureMap = initLeafletMap('sousPrefectureMap');
                    }

                    if (sousPrefectureLayer) {
                        sousPrefectureMap.removeLayer(sousPrefectureLayer);
                        sousPrefectureLayer = null;
                    }

                    try {
                        const data = await fetchSousPrefecture(btn.dataset.id);
                        document.getElementById('viewSousPrefectureName').textContent = data.nom || 'Sous-préfecture';

                        if (!data.geojson) {
                            alert('Aucun tracé GeoJSON pour cette sous-préfecture.');
                            return;
                        }

                        const geojson = JSON.parse(data.geojson);
                        sousPrefectureLayer = L.geoJSON(geojson, {
                            style: { color: '#8e44ad', weight: 2, fillOpacity: 0.25 },
                        }).addTo(sousPrefectureMap);
                        fitMapToContent(sousPrefectureMap, [sousPrefectureLayer]);
                    } catch (e) {
                        alert('Impossible d\'afficher la carte de cette sous-préfecture.');
                    }

                    setTimeout(function () { sousPrefectureMap.invalidateSize(); }, 150);
                });
            }
        });
    </script>
@endpush
