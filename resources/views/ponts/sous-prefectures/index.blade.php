@extends('layout.main')

@section('title', 'Sous-préfectures')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Gestion des sous-préfectures</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.index') }}">Ponts</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ponts.regions.index') }}">Régions</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sous-préfectures</li>
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
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $stats['total'] }}</div>
                    <div class="small opacity-75">Sous-préfectures enregistrées</div>
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
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importSousPrefectureGeoJsonModal">
                            <i class="bi bi-upload"></i> Importer GeoJSON
                        </button>
                        <a href="{{ route('ponts.departements.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-layers"></i> Départements
                        </a>
                        <a href="{{ route('ponts.regions.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-map"></i> Régions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($hasMapSousPrefectures)
        <section class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-map"></i> Carte des sous-préfectures importées</span>
                    </div>
                    <div class="card-body p-0 position-relative">
                        <div id="allSousPrefecturesMapLoader" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="text-muted mt-2">Chargement de la carte...</div>
                        </div>
                        <div id="allSousPrefecturesMap" class="d-none"></div>
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
                    <form method="GET" action="{{ route('ponts.sous-prefectures.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <label for="search" class="form-label">Code, nom, département ou région</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Ex. Abengourou, 01..." value="{{ $search }}">
                        </div>
                        <div class="col-md-6 col-lg-4 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            @if ($search !== '' || $departementId)
                                <a href="{{ route('ponts.sous-prefectures.index') }}" class="btn btn-secondary">
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
                                <th>Département</th>
                                <th>Région</th>
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
                                    <td>
                                        @if ($sousPrefecture->departement)
                                            <a href="{{ route('ponts.departements.sous-prefectures', $sousPrefecture->departement) }}">
                                                {{ $sousPrefecture->departement->nom }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $sousPrefecture->departement?->region?->nom ?? '—' }}</td>
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
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Aucune sous-préfecture. Importez un fichier GeoJSON (propriétés <code>NomSp</code> / <code>CodSp</code>, <code>NomDep</code> / <code>CodDep</code>, <code>NomReg</code> / <code>CodReg</code>).
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

    <div class="modal fade" id="importSousPrefectureGeoJsonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('ponts.sous-prefectures.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Importer GeoJSON — Sous-préfectures</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="sous_prefecture_geojson_file" class="form-label">Fichier <span class="text-danger">*</span></label>
                            <input type="file" name="geojson_file" id="sous_prefecture_geojson_file" class="form-control"
                                accept=".json,.geojson,application/json" required>
                            <div class="form-text">
                                Propriétés reconnues : <code>NomSp</code> / <code>CodSp</code>, <code>NomDep</code> / <code>CodDep</code>, <code>NomReg</code> / <code>CodReg</code>.
                                Les départements et régions doivent déjà exister en base.
                                Fichier volumineux en production ? Utilisez SSH :
                                <code>php artisan geojson:import sous-prefectures /chemin/fichier.geojson</code>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label for="sous_prefecture_import_mode" class="form-label">Mode</label>
                            <select name="mode" id="sous_prefecture_import_mode" class="form-select">
                                <option value="upsert" selected>Mettre à jour ou créer</option>
                                <option value="create">Créer uniquement</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload"></i> Importer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        #sousPrefectureMap, #allSousPrefecturesMap { height: 60vh; min-height: 400px; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const baseUrl = @json(url('/ponts/sous-prefectures'));
            const mapDataUrl = @json(route('ponts.sous-prefectures.map-data'));
            const palette = ['#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#f39c12', '#1abc9c', '#e67e22', '#34495e'];

            let sousPrefectureMap = null;
            let sousPrefectureLayer = null;
            let allSousPrefecturesMap = null;

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

            if (document.getElementById('allSousPrefecturesMap')) {
                fetch(mapDataUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
                    .then(function (res) { return res.json(); })
                    .then(function (json) {
                        const items = json.data || [];
                        const loader = document.getElementById('allSousPrefecturesMapLoader');
                        const mapEl = document.getElementById('allSousPrefecturesMap');

                        if (!items.length) {
                            if (loader) loader.classList.add('d-none');
                            return;
                        }

                        if (loader) loader.classList.add('d-none');
                        if (mapEl) mapEl.classList.remove('d-none');

                        allSousPrefecturesMap = initLeafletMap('allSousPrefecturesMap');
                        const boundsLayers = [];

                        items.forEach(function (item, index) {
                            try {
                                const geojson = JSON.parse(item.geojson);
                                const layer = L.geoJSON(geojson, {
                                    style: {
                                        color: palette[index % palette.length],
                                        weight: 2,
                                        fillOpacity: 0.2,
                                    },
                                }).bindPopup((item.nom || 'Sous-préfecture') + (item.departement ? ' — ' + item.departement : '')).addTo(allSousPrefecturesMap);
                                boundsLayers.push(layer);
                            } catch (e) {
                                console.warn('GeoJSON invalide pour la sous-préfecture', item.id);
                            }
                        });

                        fitMapToContent(allSousPrefecturesMap, boundsLayers);
                    })
                    .catch(function () {
                        const loader = document.getElementById('allSousPrefecturesMapLoader');
                        if (loader) {
                            loader.innerHTML = '<div class="alert alert-warning m-3">Impossible de charger la carte.</div>';
                        }
                    });
            }

            @if ($errors->has('geojson_file'))
                new bootstrap.Modal(document.getElementById('importSousPrefectureGeoJsonModal')).show();
            @endif
        });
    </script>
@endpush
