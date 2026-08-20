<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte — {{ $regionName }} - Unipalm</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    @include('layout.partials.favicon')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: Nunito, system-ui, sans-serif;
        }
        #map {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background: #f4f6f9;
            z-index: 1;
        }
        .map-toolbar {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            z-index: 1000;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            pointer-events: none;
        }
        .map-toolbar > * {
            pointer-events: auto;
        }
        .map-toolbar-panel {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            padding: 10px 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .map-title {
            font-weight: 700;
            color: #2c3e50;
            margin-right: 4px;
        }
        .localisation-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 0.45rem 0.85rem;
            border-radius: 0.65rem;
            min-width: 90px;
            text-align: center;
        }
        .localisation-stat--green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .localisation-stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.1;
        }
        .localisation-stat-label {
            font-size: 0.68rem;
            opacity: 0.92;
        }
        .localisation-loader {
            position: absolute;
            inset: 0;
            z-index: 900;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(244, 246, 249, 0.92);
        }
        #mapError {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            max-width: min(640px, calc(100% - 24px));
            margin: 0;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        .leaflet-popup-content {
            min-width: 210px;
        }
        .popup-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.45rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.35rem;
        }
        .popup-info {
            font-size: 0.8rem;
            color: #555;
        }
        .popup-info p {
            margin: 0.25rem 0;
        }
        .popup-info i {
            width: 18px;
            color: #667eea;
        }
        .map-legend {
            background: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.12);
            font-size: 12px;
        }
        .map-legend h4 {
            margin: 0 0 8px;
            font-size: 13px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 0;
        }
        .legend-marker {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .custom-marker {
            background: transparent;
            border: none;
        }
    </style>
</head>
<body>
    <div id="loader" class="localisation-loader">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-3">Chargement de la carte {{ $regionName }}...</div>
    </div>

    <div class="map-toolbar">
        <div class="map-toolbar-panel">
            <span class="map-title"><i class="bi bi-geo-alt-fill text-primary"></i> {{ $regionName }}</span>
            <a href="{{ route('plantations.localisation') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Régions
            </a>
            <button type="button" id="centerMapBtn" class="btn btn-primary btn-sm">
                <i class="bi bi-crosshair"></i> Centrer
            </button>
            <button type="button" id="refreshBtn" class="btn btn-success btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Actualiser
            </button>
        </div>
        <div class="map-toolbar-panel">
            <div class="localisation-stat">
                <div class="localisation-stat-value" id="totalPlantations">—</div>
                <div class="localisation-stat-label">Plantations</div>
            </div>
            <div class="localisation-stat localisation-stat--green">
                <div class="localisation-stat-value" id="totalLocalisees">—</div>
                <div class="localisation-stat-label">Localisées</div>
            </div>
        </div>
    </div>

    <div id="mapError" class="alert alert-danger d-none" role="alert"></div>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiUrl = @json(route('plantations.localisation.api', $regionId));
            const regionName = @json($regionName);
            const hasGeojson = @json($hasGeojson);
            const showUrlTemplate = @json(url('/plantations'));

            let map = null;
            let markers = null;
            let regionLayer = null;

            function escapeHtml(v) {
                return String(v ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function showError(message) {
                const el = document.getElementById('mapError');
                el.textContent = message;
                el.classList.remove('d-none');
            }

            function hideError() {
                document.getElementById('mapError').classList.add('d-none');
            }

            function parseGeoJson(raw) {
                if (!raw) return null;
                if (typeof raw === 'object') return raw;
                return JSON.parse(raw);
            }

            function createIcon() {
                return L.divIcon({
                    html: '<i class="bi bi-geo-alt-fill" style="color:#28a745;font-size:28px;text-shadow:1px 1px 3px rgba(0,0,0,.35);"></i>',
                    className: 'custom-marker',
                    iconSize: [28, 36],
                    iconAnchor: [14, 36],
                    popupAnchor: [0, -36],
                });
            }

            function addLegend() {
                const legend = L.control({ position: 'bottomright' });
                legend.onAdd = function () {
                    const div = L.DomUtil.create('div', 'map-legend');
                    div.innerHTML = `
                        <h4><i class="bi bi-info-circle me-1"></i>Légende</h4>
                        <div class="legend-item">
                            <div class="legend-marker" style="background:#667eea;"></div>
                            <span>Délimitation ${escapeHtml(regionName)}</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-marker" style="background:#28a745;"></div>
                            <span>Plantation localisée</span>
                        </div>
                    `;
                    return div;
                };
                legend.addTo(map);
            }

            function initMap() {
                map = L.map('map', {
                    maxBoundsViscosity: 0.85,
                    zoomControl: true,
                }).setView([6.8276, -5.2893], 8);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19,
                }).addTo(map);

                markers = L.markerClusterGroup({
                    chunkedLoading: true,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    maxClusterRadius: 50,
                });
                map.addLayer(markers);
                addLegend();
            }

            function fitToRegion() {
                if (regionLayer) {
                    const bounds = regionLayer.getBounds();
                    if (bounds.isValid()) {
                        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
                        map.setMaxBounds(bounds.pad(0.15));
                        return true;
                    }
                }
                if (markers && markers.getLayers().length > 0) {
                    map.fitBounds(markers.getBounds(), { padding: [50, 50] });
                    return true;
                }
                return false;
            }

            function superficieTotale(planteur) {
                const cultures = Array.isArray(planteur?.cultures) ? planteur.cultures : [];
                return cultures.reduce(function (sum, c) {
                    const value = parseFloat(c?.superficie_ha);
                    return sum + (Number.isFinite(value) ? value : 0);
                }, 0);
            }

            async function loadMapData() {
                const loader = document.getElementById('loader');
                loader.classList.remove('d-none');
                hideError();

                try {
                    const res = await fetch(apiUrl, { cache: 'no-store' });
                    const json = await res.json();
                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Erreur API localisation');
                    }

                    const regionData = json?.data?.region || {};
                    const inRegion = Array.isArray(json?.data?.planteurs) ? json.data.planteurs : [];
                    const localisees = inRegion.filter(function (p) {
                        const lat = parseFloat(p?.exploitation?.latitude);
                        const lng = parseFloat(p?.exploitation?.longitude);
                        return Number.isFinite(lat) && Number.isFinite(lng);
                    });

                    document.getElementById('totalPlantations').textContent = inRegion.length.toLocaleString('fr-FR');
                    document.getElementById('totalLocalisees').textContent = localisees.length.toLocaleString('fr-FR');

                    if (!map) {
                        initMap();
                    } else {
                        setTimeout(function () { map.invalidateSize(); }, 50);
                    }

                    if (regionLayer) {
                        map.removeLayer(regionLayer);
                        regionLayer = null;
                    }

                    if (regionData.geojson) {
                        try {
                            const geojson = parseGeoJson(regionData.geojson);
                            regionLayer = L.geoJSON(geojson, {
                                style: {
                                    color: '#435ebe',
                                    weight: 2.5,
                                    fillColor: '#667eea',
                                    fillOpacity: 0.12,
                                },
                            }).addTo(map);
                        } catch (geoError) {
                            console.warn('GeoJSON région invalide', geoError);
                            showError('Le tracé GeoJSON de la région est invalide.');
                        }
                    } else if (!hasGeojson) {
                        showError('Aucune délimitation GeoJSON pour « ' + regionName + ' ». Affichage des plantations par nom de région.');
                    }

                    markers.clearLayers();
                    localisees.forEach(function (plantation) {
                        const exp = plantation.exploitation || {};
                        const lat = parseFloat(exp.latitude);
                        const lng = parseFloat(exp.longitude);
                        const superficie = superficieTotale(plantation);
                        const nom = escapeHtml(plantation.nom_prenoms || 'Plantation');
                        const id = plantation.id;
                        const detailUrl = id ? `${showUrlTemplate}/${id}` : '#';

                        const marker = L.marker([lat, lng], { icon: createIcon() });
                        marker.bindPopup(`
                            <div class="popup-title"><i class="bi bi-flower1 me-1"></i>${nom}</div>
                            <div class="popup-info">
                                <p><i class="bi bi-telephone"></i> ${escapeHtml(plantation.telephone || 'N/A')}</p>
                                <p><i class="bi bi-geo"></i> ${escapeHtml(exp.sous_prefecture_village || '')} ${escapeHtml(exp.village || '')}</p>
                                <p><i class="bi bi-rulers"></i> ${superficie > 0 ? superficie.toFixed(2) + ' ha' : 'N/A'}</p>
                                <p><i class="bi bi-globe"></i> ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                                ${id ? `<p class="mt-2 mb-0"><a href="${detailUrl}" target="_blank">Voir la fiche</a></p>` : ''}
                            </div>
                        `);
                        markers.addLayer(marker);
                    });

                    loader.classList.add('d-none');
                    setTimeout(function () {
                        map.invalidateSize();
                        if (!fitToRegion()) {
                            map.setView([6.8276, -5.2893], 7);
                        }
                    }, 80);

                    if (localisees.length === 0) {
                        showError('Aucune plantation géolocalisée à l’intérieur de « ' + regionName + ' ».');
                    }
                } catch (err) {
                    loader.classList.add('d-none');
                    showError(err?.message || 'Impossible de charger la carte.');
                }
            }

            document.getElementById('centerMapBtn').addEventListener('click', function () {
                fitToRegion();
            });

            document.getElementById('refreshBtn').addEventListener('click', loadMapData);
            loadMapData();
        });
    </script>
</body>
</html>
