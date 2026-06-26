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
                    <div class="d-flex gap-2">
                        <a href="{{ route('ponts.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list-ul"></i> Liste des ponts
                        </a>
                        <button type="button" class="btn btn-sm btn-success" id="showActiveBtn">Actifs</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="showAllBtn">Tous</button>
                    </div>
                </div>
                <div class="card-body p-0">
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
            const map = L.map('pontsMap').setView([7.539989, -5.547080], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const markers = [];
            let markerGroup = L.layerGroup().addTo(map);

            function renderMarkers(filterActiveOnly) {
                markerGroup.clearLayers();
                markers.length = 0;

                const list = filterActiveOnly
                    ? ponts.filter(function (pont) { return pont.statut === 'Actif'; })
                    : ponts;

                const bounds = [];

                list.forEach(function (pont) {
                    if (!pont.latitude || !pont.longitude) {
                        return;
                    }

                    const lat = parseFloat(pont.latitude);
                    const lng = parseFloat(pont.longitude);

                    if (Number.isNaN(lat) || Number.isNaN(lng)) {
                        return;
                    }

                    const color = pont.statut === 'Actif' ? '#198754' : '#dc3545';
                    const marker = L.circleMarker([lat, lng], {
                        radius: 8,
                        color: '#fff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.9,
                    });

                    marker.bindPopup(
                        '<div class="pont-popup-title">' + pont.nom_pont + '</div>' +
                        '<div><code>' + pont.code_pont + '</code></div>' +
                        '<div class="pont-popup-meta mt-2">' +
                        '<div><strong>Gérant:</strong> ' + pont.gerant + '</div>' +
                        '<div><strong>Coopérative:</strong> ' + (pont.cooperatif || '—') + '</div>' +
                        '<div><strong>Statut:</strong> ' + pont.statut + '</div>' +
                        '</div>'
                    );

                    marker.addTo(markerGroup);
                    markers.push(marker);
                    bounds.push([lat, lng]);
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
                }
            }

            renderMarkers(false);

            document.getElementById('showActiveBtn').addEventListener('click', function () {
                renderMarkers(true);
            });

            document.getElementById('showAllBtn').addEventListener('click', function () {
                renderMarkers(false);
            });
        });
    </script>
@endpush
