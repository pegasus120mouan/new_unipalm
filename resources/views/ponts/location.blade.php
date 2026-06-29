@extends('layout.main')

@section('title', 'Localisation des ponts')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Localisation des ponts-bascules</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('ponts.index') }}">Ponts</a></li>
                <li class="breadcrumb-item active" aria-current="page">Localisation</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <section class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card border-0 text-white h-100" style="background-color: #435ebe;">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5">{{ $stats['total'] }}</div>
                    <div class="small opacity-75">Total ponts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 text-white h-100" style="background-color: #198754;">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5">{{ $stats['actifs'] }}</div>
                    <div class="small opacity-75">Actifs</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 text-white h-100" style="background-color: #dc3545;">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5">{{ $stats['inactifs'] }}</div>
                    <div class="small opacity-75">Inactifs</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 text-white h-100" style="background-color: #0dcaf0;">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5">{{ $stats['geolocalises'] }}</div>
                    <div class="small opacity-75">Géolocalisés</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-geo-alt"></i> Carte des ponts-bascules</span>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <label for="regionFilter" class="small text-muted mb-0 text-nowrap">Région</label>
                            <select id="regionFilter" class="form-select form-select-sm" style="min-width: 200px;">
                                <option value="">Toutes les régions</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="{{ route('ponts.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list-ul"></i> Liste des ponts
                        </a>
                        <button type="button" class="btn btn-sm btn-success" id="showActiveBtn">Actifs</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="showAllBtn">Tous</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="regionMapInfo" class="alert alert-light border-bottom rounded-0 mb-0 py-2 px-3 small d-none"></div>
                    <div id="pontsMap"></div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <style>
        #pontsMap {
            height: 70vh;
            min-height: 480px;
            width: 100%;
        }

        .pont-popup-title {
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .pont-popup-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ponts = @json($ponts);
            const regionsBaseUrl = @json(url('/ponts/localisation/regions'));
            const map = L.map('pontsMap').setView([7.539989, -5.547080], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            let markerGroup = L.layerGroup().addTo(map);
            let regionLayer = null;
            let filterActiveOnly = false;
            let selectedRegionId = '';

            function pontHasCoordinates(pont) {
                if (pont.has_coordinates === false) {
                    return false;
                }

                const lat = parseFloat(pont.latitude);
                const lng = parseFloat(pont.longitude);

                return !Number.isNaN(lat) && !Number.isNaN(lng) && !(lat === 0 && lng === 0);
            }

            function filteredPonts() {
                let list = filterActiveOnly
                    ? ponts.filter(function (pont) { return pont.statut === 'Actif'; })
                    : ponts.slice();

                if (selectedRegionId) {
                    list = list.filter(function (pont) {
                        return String(pont.id_region) === String(selectedRegionId);
                    });
                }

                return list;
            }

            function updateRegionInfo(regionPonts, regionNom) {
                const infoEl = document.getElementById('regionMapInfo');
                if (!infoEl) return;

                if (!selectedRegionId) {
                    infoEl.classList.add('d-none');
                    return;
                }

                const total = regionPonts.length;
                const geolocalises = regionPonts.filter(pontHasCoordinates).length;

                let message = '<i class="bi bi-map"></i> <strong>' + regionNom + '</strong>';
                message += ' — ' + total + ' pont(s)';
                message += ', <span class="text-success fw-semibold">' + geolocalises + ' sur la carte</span>';

                if (geolocalises < total) {
                    message += ' <span class="text-muted">(' + (total - geolocalises) + ' sans coordonnées GPS)</span>';
                }

                infoEl.innerHTML = message;
                infoEl.classList.remove('d-none');
            }

            function clearRegionLayer() {
                if (regionLayer) {
                    map.removeLayer(regionLayer);
                    regionLayer = null;
                }
            }

            async function loadRegionLayer(regionId) {
                clearRegionLayer();

                if (!regionId) {
                    return null;
                }

                const res = await fetch(regionsBaseUrl + '/' + regionId, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                const json = await res.json();

                if (!json.success || !json.data?.geojson) {
                    return json.data || null;
                }

                try {
                    const geojson = JSON.parse(json.data.geojson);
                    regionLayer = L.geoJSON(geojson, {
                        style: { color: '#435ebe', weight: 2, fillOpacity: 0.15 },
                    }).addTo(map);
                } catch (e) {
                    console.warn('GeoJSON invalide pour la région', regionId);
                }

                return json.data;
            }

            function renderMarkers() {
                markerGroup.clearLayers();

                const list = filteredPonts();
                const markerLayers = [];
                const regionBounds = regionLayer ? regionLayer.getBounds() : null;

                list.forEach(function (pont) {
                    if (!pontHasCoordinates(pont)) {
                        return;
                    }

                    const lat = parseFloat(pont.latitude);
                    const lng = parseFloat(pont.longitude);
                    const color = pont.statut === 'Actif' ? '#198754' : '#dc3545';
                    const marker = L.circleMarker([lat, lng], {
                        radius: 8,
                        color: '#fff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.9,
                    });

                    const outsideRegion = regionBounds && !regionBounds.contains([lat, lng]);
                    const outsideNote = outsideRegion
                        ? '<div class="text-warning small mt-1"><i class="bi bi-exclamation-triangle"></i> Coordonnées hors de la région</div>'
                        : '';

                    marker.bindPopup(
                        '<div class="pont-popup-title">' + pont.nom_pont + '</div>' +
                        '<div><code>' + pont.code_pont + '</code></div>' +
                        '<div class="pont-popup-meta mt-2">' +
                        '<div><strong>Gérant:</strong> ' + pont.gerant + '</div>' +
                        '<div><strong>Région:</strong> ' + (pont.region || '—') + '</div>' +
                        '<div><strong>Coopérative:</strong> ' + (pont.cooperatif || '—') + '</div>' +
                        '<div><strong>Statut:</strong> ' + pont.statut + '</div>' +
                        outsideNote +
                        '</div>'
                    );

                    marker.addTo(markerGroup);
                    if (!outsideRegion) {
                        markerLayers.push(marker);
                    }
                });

                if (regionLayer) {
                    const fitGroup = L.featureGroup([regionLayer]);
                    markerLayers.forEach(function (layer) {
                        fitGroup.addLayer(layer);
                    });
                    map.fitBounds(fitGroup.getBounds(), {
                        padding: [40, 40],
                        maxZoom: 13,
                    });
                } else if (markerLayers.length > 0) {
                    const fitGroup = L.featureGroup(markerLayers);
                    map.fitBounds(fitGroup.getBounds(), {
                        padding: [40, 40],
                        maxZoom: 12,
                    });
                } else if (!selectedRegionId) {
                    map.setView([7.539989, -5.547080], 7);
                }
            }

            async function refreshMap() {
                const regionSelect = document.getElementById('regionFilter');
                const regionOption = regionSelect?.selectedOptions?.[0];
                const regionNom = regionOption && regionOption.value ? regionOption.textContent : '';

                if (selectedRegionId) {
                    const regionData = await loadRegionLayer(selectedRegionId);
                    updateRegionInfo(filteredPonts(), regionData?.nom || regionNom);
                } else {
                    clearRegionLayer();
                    updateRegionInfo([], '');
                }

                renderMarkers();
            }

            renderMarkers();

            document.getElementById('regionFilter').addEventListener('change', function () {
                selectedRegionId = this.value;
                refreshMap();
            });

            document.getElementById('showActiveBtn').addEventListener('click', function () {
                filterActiveOnly = true;
                refreshMap();
            });

            document.getElementById('showAllBtn').addEventListener('click', function () {
                filterActiveOnly = false;
                refreshMap();
            });
        });
    </script>
@endpush
