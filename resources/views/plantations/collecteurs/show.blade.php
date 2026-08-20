@extends('layout.main')

@section('title', 'Détail collecteur')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Détail collecteur</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('plantations.collecteurs') }}">Collecteurs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Détail</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @php
        $fullName = trim(($collecteur['nom'] ?? '').' '.($collecteur['prenoms'] ?? ''));
        $avatarUrl = $collecteur['avatar_url'] ?? null;
        $defaultAvatar = "data:image/svg+xml," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#E9ECEF"/><circle cx="40" cy="32" r="14" fill="#ADB5BD"/><path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/></svg>');
        $zoneName = $collecteur['zone_nom'] ?? $collecteur['nom_zone'] ?? 'Non assigné';
    @endphp

    <div class="collecteur-detail-header mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <a href="{{ route('plantations.collecteurs') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <img src="{{ $avatarUrl ?: $defaultAvatar }}"
                alt="Avatar"
                class="collecteur-detail-avatar"
                onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';">
            <div class="text-white">
                <h2 class="mb-1 fw-bold">{{ $fullName !== '' ? $fullName : 'Collecteur #'.$collecteurId }}</h2>
                <div class="d-flex flex-wrap align-items-center gap-3 opacity-90">
                    <span><i class="bi bi-telephone me-1"></i>{{ $collecteur['contact'] ?? 'N/A' }}</span>
                    <span><i class="bi bi-geo-alt me-1"></i>{{ $zoneName }}</span>
                    <span class="badge bg-white text-primary">{{ $collecteur['role'] ?? 'collecteur' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('plantations.collecteurs.show', $collecteurId) }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label fw-semibold">Date début</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control"
                        value="{{ $dateDebut }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label fw-semibold">Date fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control"
                        value="{{ $dateFin }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('plantations.collecteurs.show', [
                        'id' => $collecteurId,
                        'date_debut' => now()->startOfYear()->format('Y-m-d'),
                        'date_fin' => now()->endOfYear()->format('Y-m-d'),
                    ]) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-calendar3"></i> Année en cours
                    </a>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('plantations.collecteurs.show', $collecteurId) }}"
                        class="btn btn-outline-danger w-100" title="Réinitialiser">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
            @if ($filtreActif)
                <div class="mt-2">
                    <span class="badge bg-info">
                        Filtre actif :
                        {{ $dateDebut ?: 'Début' }} → {{ $dateFin ?: 'Fin' }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 collecteur-stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="collecteur-stat-icon collecteur-stat-icon--blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="collecteur-stat-value">{{ number_format($stats['nombre_exploitants'], 0, ',', ' ') }}</div>
                        <div class="text-muted">Planteurs recensés</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 collecteur-stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="collecteur-stat-icon collecteur-stat-icon--green">
                        <i class="bi bi-map-fill"></i>
                    </div>
                    <div>
                        <div class="collecteur-stat-value">{{ number_format($stats['superficie_totale'], 2, ',', ' ') }} ha</div>
                        <div class="text-muted">Superficie totale</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 collecteur-stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="collecteur-stat-icon collecteur-stat-icon--orange">
                        <i class="bi bi-bounding-box"></i>
                    </div>
                    <div>
                        <div class="collecteur-stat-value">{{ number_format($stats['nombre_parcelles'], 0, ',', ' ') }}</div>
                        <div class="text-muted">Parcelles enregistrées</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header collecteur-table-header">
                    <i class="bi bi-flower1 me-1"></i> Répartition par Culture
                </div>
                <div class="card-body p-0">
                    @if (empty($statsParCulture))
                        <p class="text-muted text-center py-4 mb-0">Aucune donnée pour cette période</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type de culture</th>
                                        <th>Planteurs</th>
                                        <th>Superficie (ha)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statsParCulture as $culture)
                                        <tr>
                                            <td>
                                                <span class="badge bg-success-subtle text-success">
                                                    {{ $culture['type_culture'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ number_format((float) ($culture['nombre_exploitants'] ?? 0), 0, ',', ' ') }}</td>
                                            <td>{{ number_format((float) ($culture['superficie_totale'] ?? 0), 2, ',', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header collecteur-table-header">
                    <i class="bi bi-graph-up me-1"></i> Évolution Mensuelle
                </div>
                <div class="card-body p-0">
                    @if (empty($statsMensuel))
                        <p class="text-muted text-center py-4 mb-0">Aucune donnée pour cette période</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mois</th>
                                        <th>Planteurs</th>
                                        <th>Superficie (ha)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statsMensuel as $mois)
                                        <tr>
                                            <td><strong>{{ $mois['mois'] ?? '—' }}</strong></td>
                                            <td>{{ number_format((float) ($mois['nombre_exploitants'] ?? 0), 0, ',', ' ') }}</td>
                                            <td>{{ number_format((float) ($mois['superficie_totale'] ?? 0), 2, ',', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <i class="bi bi-clock-history me-1"></i> Derniers Planteurs Recensés (20 derniers)
                </div>
                <div class="card-body p-0">
                    @if (empty($derniersExploitants))
                        <p class="text-muted text-center py-4 mb-0">Aucun planteur pour cette période</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 align-middle">
                                <thead class="collecteurs-table-header">
                                    <tr>
                                        <th>Nom &amp; Prénoms</th>
                                        <th>Téléphone</th>
                                        <th>Région</th>
                                        <th>Village</th>
                                        <th>Superficie (ha)</th>
                                        <th>Date</th>
                                        <th class="text-center">Carte</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($derniersExploitants as $exploitant)
                                        @php
                                            $planteurId = $exploitant['id'] ?? null;
                                            $dateEnreg = $exploitant['date_enregistrement'] ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                @if ($planteurId)
                                                    <a href="{{ route('plantations.show', $planteurId) }}" class="fw-semibold text-primary text-decoration-none">
                                                        {{ $exploitant['nom_prenoms'] ?? '—' }}
                                                    </a>
                                                @else
                                                    <strong class="text-primary">{{ $exploitant['nom_prenoms'] ?? '—' }}</strong>
                                                @endif
                                            </td>
                                            <td>{{ $exploitant['telephone'] ?? 'N/A' }}</td>
                                            <td>{{ $exploitant['region'] ?? 'N/A' }}</td>
                                            <td>{{ $exploitant['village'] ?? 'N/A' }}</td>
                                            <td>{{ number_format((float) ($exploitant['superficie_totale'] ?? 0), 2, ',', ' ') }}</td>
                                            <td>
                                                {{ $dateEnreg ? \Illuminate\Support\Carbon::parse($dateEnreg)->format('d/m/Y') : '—' }}
                                            </td>
                                            <td class="text-center">
                                                @if ($planteurId)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-info btn-map-planteur"
                                                        data-id="{{ $planteurId }}"
                                                        title="Voir la carte">
                                                        <i class="bi bi-geo-alt-fill"></i> Carte
                                                    </button>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="parcellesMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-geo-alt me-2"></i>Cartographie des parcelles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="parcellesMapHint" class="alert alert-info d-none mb-2"></div>
                    <div id="parcellesMap" style="height:70vh;width:100%;background:#fff;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .collecteur-detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.28);
        }

        .collecteur-detail-avatar {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.9);
            background: #fff;
        }

        .collecteur-stat-card {
            transition: transform 0.2s ease;
        }

        .collecteur-stat-card:hover {
            transform: translateY(-3px);
        }

        .collecteur-stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .collecteur-stat-icon--blue {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .collecteur-stat-icon--green {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }

        .collecteur-stat-icon--orange {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        .collecteur-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.1;
        }

        .collecteur-table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-weight: 600;
        }

        .collecteurs-table-header {
            background: #111;
        }

        .collecteurs-table-header th {
            color: #fff !important;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            white-space: nowrap;
        }

        .bg-success-subtle {
            background-color: #e8f5e9;
        }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiBaseUrl = @json(route('plantations.api'));
            let mapInstance = null;
            let pendingMapPlanteur = null;

            function buildApiUrl(params) {
                const qs = new URLSearchParams(params || {}).toString();
                return qs ? `${apiBaseUrl}?${qs}` : apiBaseUrl;
            }

            async function fetchPlanteurDetails(id) {
                const targetId = String(id);
                const res = await fetch(buildApiUrl({ action: 'planteurs', id: targetId }), { cache: 'no-store' });
                const json = await res.json();
                if (!res.ok || !json?.success) {
                    throw new Error(json?.error || json?.message || 'Erreur API');
                }

                const list = Array.isArray(json?.data?.planteurs) ? json.data.planteurs : null;
                let planteur = null;

                if (list) {
                    planteur = list.find(function (item) { return String(item?.id) === targetId; }) || null;
                    if (!planteur && list.length === 1 && String(list[0]?.id) === targetId) {
                        planteur = list[0];
                    }
                } else if (json?.data && String(json.data.id) === targetId) {
                    planteur = json.data;
                }

                if (!planteur || String(planteur.id) !== targetId) {
                    throw new Error('Planteur introuvable (id ' + targetId + ').');
                }

                return planteur;
            }

            function drawParcelles(planteur) {
                const hintEl = document.getElementById('parcellesMapHint');
                const mapDiv = document.getElementById('parcellesMap');

                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }
                mapDiv.innerHTML = '';
                delete mapDiv._leaflet_id;

                hintEl.classList.add('d-none');
                hintEl.classList.remove('alert-warning');
                hintEl.classList.add('alert-info');

                const cultures = Array.isArray(planteur?.cultures) ? planteur.cultures : [];
                const fromCultures = cultures.flatMap((c) => Array.isArray(c?.parcelles) ? c.parcelles : []);
                const parcellesList = fromCultures.length
                    ? fromCultures
                    : (Array.isArray(planteur?.parcelles) ? planteur.parcelles
                        : (Array.isArray(planteur?.exploitation?.parcelles) ? planteur.exploitation.parcelles : []));

                const boundsPoints = [];
                const paths = [];

                parcellesList.forEach((parcelle) => {
                    let points = parcelle?.points;
                    if (typeof points === 'string') {
                        try { points = JSON.parse(points); } catch (e) { points = null; }
                    }
                    const list = Array.isArray(points) ? points : (points && typeof points === 'object' ? Object.values(points) : []);
                    const latlngs = list.map((pt) => {
                        if (Array.isArray(pt) && pt.length >= 2) {
                            const la = Number(pt[0]);
                            const lo = Number(pt[1]);
                            if (Number.isFinite(la) && Number.isFinite(lo)) {
                                boundsPoints.push([la, lo]);
                                return [la, lo];
                            }
                        }
                        const la = Number(pt?.latitude ?? pt?.lat);
                        const lo = Number(pt?.longitude ?? pt?.lng ?? pt?.lon);
                        if (Number.isFinite(la) && Number.isFinite(lo)) {
                            boundsPoints.push([la, lo]);
                            return [la, lo];
                        }
                        return null;
                    }).filter(Boolean);
                    if (latlngs.length >= 2) paths.push(latlngs);
                });

                const nom = planteur?.nom_prenoms || ('#' + (planteur?.id ?? ''));
                hintEl.textContent = `${nom} | Parcelles: ${parcellesList.length} | Points: ${boundsPoints.length}`;
                hintEl.classList.remove('d-none');

                if (!boundsPoints.length) {
                    hintEl.classList.remove('alert-info');
                    hintEl.classList.add('alert-warning');
                    hintEl.textContent = `Aucune parcelle cartographiée pour ${nom}.`;
                    return;
                }

                mapInstance = L.map(mapDiv);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19,
                }).addTo(mapInstance);

                paths.forEach((latlngs) => {
                    if (latlngs.length >= 3) {
                        L.polygon(latlngs, { color: '#1f6feb', weight: 3, fillOpacity: 0.2 }).addTo(mapInstance);
                    } else {
                        L.polyline(latlngs, { color: '#1f6feb', weight: 3 }).addTo(mapInstance);
                    }
                });

                mapInstance.fitBounds(L.latLngBounds(boundsPoints), { padding: [30, 30] });
                setTimeout(() => mapInstance.invalidateSize(), 200);
            }

            document.querySelectorAll('.btn-map-planteur').forEach(function (button) {
                button.addEventListener('click', async function () {
                    const id = button.getAttribute('data-id');
                    if (!id) return;

                    const original = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                    try {
                        pendingMapPlanteur = null;
                        const modal = new bootstrap.Modal(document.getElementById('parcellesMapModal'));
                        modal.show();
                        document.getElementById('parcellesMapHint').textContent = 'Chargement de la carte...';
                        document.getElementById('parcellesMapHint').classList.remove('d-none', 'alert-warning');
                        document.getElementById('parcellesMapHint').classList.add('alert-info');

                        pendingMapPlanteur = await fetchPlanteurDetails(id);
                        if (document.getElementById('parcellesMapModal').classList.contains('show')) {
                            drawParcelles(pendingMapPlanteur);
                            pendingMapPlanteur = null;
                        }
                    } catch (error) {
                        alert(error?.message || 'Impossible de charger la carte.');
                    } finally {
                        button.disabled = false;
                        button.innerHTML = original;
                    }
                });
            });

            document.getElementById('parcellesMapModal').addEventListener('shown.bs.modal', function () {
                if (pendingMapPlanteur) {
                    drawParcelles(pendingMapPlanteur);
                    pendingMapPlanteur = null;
                } else if (mapInstance) {
                    setTimeout(() => mapInstance.invalidateSize(), 100);
                }
            });
        });
    </script>
@endpush
