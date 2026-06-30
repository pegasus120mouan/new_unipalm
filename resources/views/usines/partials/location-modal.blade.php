<div class="modal fade" id="usineLocationModal" tabindex="-1" aria-labelledby="usineLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="usineLocationModalLabel">
                    <i class="bi bi-geo-alt"></i> Localisation — <span id="usineLocationTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-0">
                <div id="usineLocationLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Chargement de la carte…</p>
                </div>
                <div id="usineLocationError" class="alert alert-warning m-3 d-none"></div>
                <div id="usineLocationContent" class="d-none">
                    <div id="usineLocationInfo" class="alert alert-light border-bottom rounded-0 mb-0 py-2 px-3 small"></div>
                    <div class="row g-0">
                        <div class="col-lg-7 border-end">
                            <div id="usineLocationMap"></div>
                        </div>
                        <div class="col-lg-5">
                            <div class="p-3 border-bottom bg-light">
                                <div class="fw-semibold">Ponts-bascules à proximité</div>
                                <div class="small text-muted" id="usineLocationPontsCount"></div>
                            </div>
                            <div class="table-responsive usine-location-ponts-list">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Pont</th>
                                            <th>Position</th>
                                            <th class="text-end">Distance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usineLocationPontsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <style>
        #usineLocationMap {
            height: 65vh;
            min-height: 420px;
            width: 100%;
        }

        .usine-location-ponts-list {
            max-height: 65vh;
            min-height: 420px;
            overflow-y: auto;
        }

        .usine-location-pont-row {
            cursor: pointer;
        }

        .usine-location-pont-row.active {
            background-color: rgba(67, 94, 190, 0.12);
        }

        .usine-marker-icon {
            background: transparent;
            border: none;
        }

        .usine-marker-icon .bi {
            font-size: 2.25rem;
            color: #435ebe;
            line-height: 1;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.35));
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('usineLocationModal');
            if (!modalEl) {
                return;
            }

            const loadingEl = document.getElementById('usineLocationLoading');
            const errorEl = document.getElementById('usineLocationError');
            const contentEl = document.getElementById('usineLocationContent');
            const titleEl = document.getElementById('usineLocationTitle');
            const infoEl = document.getElementById('usineLocationInfo');
            const pontsBodyEl = document.getElementById('usineLocationPontsBody');
            const pontsCountEl = document.getElementById('usineLocationPontsCount');
            const palette = ['#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#f39c12', '#1abc9c', '#e67e22', '#34495e'];

            let map = null;
            let markerGroup = null;
            let regionLayer = null;
            let departementLayer = null;
            let pontMarkers = {};
            let modalInstance = null;

            function getModal() {
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modalEl);
                }

                return modalInstance;
            }

            function resetView() {
                loadingEl.classList.remove('d-none');
                errorEl.classList.add('d-none');
                contentEl.classList.add('d-none');
                errorEl.textContent = '';
                pontsBodyEl.innerHTML = '';
                pontMarkers = {};
            }

            function ensureMap() {
                if (map) {
                    return;
                }

                map = L.map('usineLocationMap').setView([7.539989, -5.547080], 7);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);
                markerGroup = L.layerGroup().addTo(map);
            }

            function clearMapLayers() {
                if (markerGroup) {
                    markerGroup.clearLayers();
                }

                if (regionLayer) {
                    map.removeLayer(regionLayer);
                    regionLayer = null;
                }

                if (departementLayer) {
                    map.removeLayer(departementLayer);
                    departementLayer = null;
                }
            }

            function parseGeoJson(raw) {
                if (!raw) {
                    return null;
                }

                if (typeof raw === 'object') {
                    return raw;
                }

                return JSON.parse(raw);
            }

            function renderRegionLayers(data) {
                const hasDepartements = (data.departements || []).length > 0;

                if (hasDepartements) {
                    const group = L.layerGroup();
                    (data.departements || []).forEach(function (departement, index) {
                        if (!departement.geojson) {
                            return;
                        }

                        try {
                            const geojson = parseGeoJson(departement.geojson);
                            const color = palette[index % palette.length];
                            L.geoJSON(geojson, {
                                style: {
                                    color: color,
                                    weight: 2,
                                    fillColor: color,
                                    fillOpacity: 0.22,
                                },
                            }).bindPopup('<div class="fw-semibold">' + (departement.nom || 'Département') + '</div>')
                                .addTo(group);
                        } catch (e) {
                            console.warn('GeoJSON invalide pour le département', departement.id);
                        }
                    });

                    if (group.getLayers().length) {
                        departementLayer = group;
                        departementLayer.addTo(map);
                    }
                }

                if (data.region?.geojson) {
                    try {
                        const geojson = parseGeoJson(data.region.geojson);
                        regionLayer = L.geoJSON(geojson, {
                            style: hasDepartements
                                ? { color: '#435ebe', weight: 1, dashArray: '6 4', fillOpacity: 0 }
                                : { color: '#435ebe', weight: 2, fillOpacity: 0.12 },
                        }).addTo(map);
                    } catch (e) {
                        console.warn('GeoJSON invalide pour la région');
                    }
                }
            }

            function fitMapToMarkers(markers) {
                if (!markers.length) {
                    return;
                }

                const bounds = L.latLngBounds(markers.map(function (marker) {
                    return marker.getLatLng();
                }));

                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 12 });
                }
            }

            function createUsineIcon() {
                return L.divIcon({
                    className: 'usine-marker-icon',
                    html: '<i class="bi bi-geo-alt-fill" aria-hidden="true"></i>',
                    iconSize: [36, 36],
                    iconAnchor: [18, 36],
                    popupAnchor: [0, -36],
                });
            }

            function highlightPontRow(pontId) {
                document.querySelectorAll('.usine-location-pont-row').forEach(function (row) {
                    row.classList.toggle('active', String(row.dataset.pontId) === String(pontId));
                });
            }

            function renderLocation(data) {
                ensureMap();
                clearMapLayers();

                const usine = data.usine;
                const regionNom = data.region?.nom;
                const geolocatedPonts = (data.ponts || []).length;

                infoEl.innerHTML = '<i class="bi bi-map"></i> '
                    + (regionNom
                        ? '<strong>' + regionNom + '</strong> — '
                        : '')
                    + 'Usine <strong>' + usine.nom_usine + '</strong>'
                    + ' — <span class="text-success fw-semibold">' + geolocatedPonts + ' pont(s) géolocalisé(s)</span>';

                pontsCountEl.textContent = regionNom
                    ? geolocatedPonts + ' pont(s) dans la région, triés par distance'
                    : geolocatedPonts + ' pont(s) géolocalisé(s), triés par distance';

                renderRegionLayers(data);

                const usineMarker = L.marker([usine.latitude, usine.longitude], {
                    icon: createUsineIcon(),
                    zIndexOffset: 1000,
                }).bindPopup(
                    '<div class="fw-bold">' + usine.nom_usine + '</div>'
                    + '<div class="small text-muted">Usine</div>'
                    + '<div class="small">' + usine.latitude.toFixed(5) + ', ' + usine.longitude.toFixed(5) + '</div>'
                );
                usineMarker.addTo(markerGroup);

                const markerList = [usineMarker];

                (data.ponts || []).forEach(function (pont) {
                    const color = pont.statut === 'Actif' ? '#198754' : '#dc3545';
                    const marker = L.circleMarker([pont.latitude, pont.longitude], {
                        radius: 8,
                        color: '#fff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.9,
                    });

                    marker.bindPopup(
                        '<div class="fw-bold">' + pont.nom_pont + '</div>'
                        + '<div><code>' + pont.code_pont + '</code></div>'
                        + '<div class="small mt-1"><strong>Distance:</strong> ' + pont.distance_label + '</div>'
                        + '<div class="small"><strong>Gérant:</strong> ' + (pont.gerant || '—') + '</div>'
                    );

                    marker.on('click', function () {
                        highlightPontRow(pont.id_pont);
                    });

                    marker.addTo(markerGroup);
                    pontMarkers[pont.id_pont] = marker;
                    markerList.push(marker);
                });

                pontsBodyEl.innerHTML = '';

                if (!data.ponts?.length) {
                    pontsBodyEl.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Aucun pont géolocalisé dans cette région.</td></tr>';
                } else {
                    data.ponts.forEach(function (pont, index) {
                        const row = document.createElement('tr');
                        row.className = 'usine-location-pont-row';
                        row.dataset.pontId = pont.id_pont;
                        row.innerHTML =
                            '<td>'
                            + '<div class="fw-semibold">' + pont.nom_pont + '</div>'
                            + '<div class="small text-muted"><code>' + pont.code_pont + '</code></div>'
                            + '</td>'
                            + '<td class="small text-nowrap">'
                            + pont.latitude.toFixed(5) + '<br>' + pont.longitude.toFixed(5)
                            + '</td>'
                            + '<td class="text-end text-nowrap">'
                            + '<span class="badge bg-primary">' + (index + 1) + '</span> '
                            + '<strong>' + pont.distance_label + '</strong>'
                            + '</td>';

                        row.addEventListener('click', function () {
                            const pontMarker = pontMarkers[pont.id_pont];
                            if (!pontMarker) {
                                return;
                            }

                            highlightPontRow(pont.id_pont);
                            map.setView(pontMarker.getLatLng(), Math.max(map.getZoom(), 11));
                            pontMarker.openPopup();
                        });

                        pontsBodyEl.appendChild(row);
                    });
                }

                fitMapToMarkers(markerList);

                loadingEl.classList.add('d-none');
                contentEl.classList.remove('d-none');

                setTimeout(function () {
                    map.invalidateSize();
                    fitMapToMarkers(markerList);
                }, 200);
            }

            async function openUsineLocation(usineId, usineName) {
                resetView();
                titleEl.textContent = usineName;
                getModal().show();

                try {
                    const response = await fetch(@json(url('/usines')) + '/' + usineId + '/localisation', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Impossible de charger la localisation.');
                    }

                    renderLocation(payload.data);
                } catch (error) {
                    loadingEl.classList.add('d-none');
                    errorEl.textContent = error.message || 'Erreur lors du chargement.';
                    errorEl.classList.remove('d-none');
                }
            }

            document.querySelectorAll('.usine-location-link').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    openUsineLocation(link.dataset.id, link.dataset.nom);
                });
            });

            modalEl.addEventListener('shown.bs.modal', function () {
                if (map) {
                    map.invalidateSize();
                }
            });
        });
    </script>
@endpush
