@extends('layout.main')

@section('title', 'Détail du champ')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 id="pageTitle">Détail du champ</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('plantations.champs', $planteurId) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-grid"></i> Tous les champs
            </a>
            <a href="{{ route('plantations.show', $planteurId) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-person"></i> Fiche du planteur
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div id="champError" class="alert alert-danger d-none" role="alert"></div>

    <div id="champLoader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-3">Chargement du champ...</div>
    </div>

    <div id="champContent" class="d-none">
        <div class="card mb-4">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                <img id="planteurPhoto" src="" alt="Photo" class="rounded-circle border"
                    style="width:72px;height:72px;object-fit:cover;">
                <div>
                    <div class="text-muted small mb-1" id="planteurNom"></div>
                    <h4 class="mb-1"><code class="text-danger" id="champCode"></code></h4>
                    <div class="text-muted small" id="planteurFiche"></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">Informations du champ</div>
                    <div class="card-body">
                        <div class="mb-2"><strong>Type de culture :</strong> <span id="champType"></span></div>
                        <div class="mb-2"><strong>Superficie :</strong> <span id="champSuperficie"></span></div>
                        <div class="mb-2"><strong>Âge :</strong> <span id="champAge"></span></div>
                        <div class="mb-2"><strong>Mode :</strong> <span id="champMode"></span></div>
                        <div class="mb-0"><strong>Production estimée :</strong> <span id="champProduction"></span></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Localisation</div>
                    <div class="card-body">
                        <div class="mb-2"><strong>Exploitation :</strong> <span id="explExploitation"></span></div>
                        <div class="mb-2"><strong>Région :</strong> <span id="explRegion"></span></div>
                        <div class="mb-2"><strong>Sous-préfecture :</strong> <span id="explSousPrefecture"></span></div>
                        <div class="mb-2"><strong>Village :</strong> <span id="explVillage"></span></div>
                        <div class="mb-2"><strong>Latitude :</strong> <span id="explLat"></span></div>
                        <div class="mb-0"><strong>Longitude :</strong> <span id="explLng"></span></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Cartographie du champ</div>
                    <div class="card-body">
                        <div id="champMapHint" class="alert alert-info mb-2"></div>
                        <div id="champMap" style="height:55vh;width:100%;background:#f8f9fa;border-radius:8px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const planteurId = @json($planteurId);
            const champId = @json($champId);
            const apiUrl = @json(route('plantations.api'));
            const defaultPhoto = "data:image/svg+xml," + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#E9ECEF"/><circle cx="40" cy="32" r="14" fill="#ADB5BD"/><path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/></svg>');
            let mapInstance = null;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;');
            }

            function setText(id, value) {
                document.getElementById(id).textContent = value || '—';
            }

            function fmtNumber(value, suffix) {
                if (value === null || value === undefined || value === '') return '—';
                const number = Number(value);
                if (!Number.isFinite(number)) return String(value);
                return number.toLocaleString('fr-FR', { maximumFractionDigits: 4 }) + (suffix || '');
            }

            function parcellePoints(parcelle) {
                let points = parcelle?.points;
                if (typeof points === 'string') {
                    try { points = JSON.parse(points); } catch (e) { points = null; }
                }
                const list = Array.isArray(points) ? points : (points && typeof points === 'object' ? Object.values(points) : []);
                return list.map(function (pt) {
                    if (Array.isArray(pt) && pt.length >= 2) {
                        const la = Number(pt[0]); const lo = Number(pt[1]);
                        if (Number.isFinite(la) && Number.isFinite(lo)) return [la, lo];
                    }
                    const la = Number(pt?.latitude ?? pt?.lat);
                    const lo = Number(pt?.longitude ?? pt?.lng ?? pt?.lon);
                    if (Number.isFinite(la) && Number.isFinite(lo)) return [la, lo];
                    return null;
                }).filter(Boolean);
            }

            function drawMap(champ, exploitation) {
                const mapDiv = document.getElementById('champMap');
                const hintEl = document.getElementById('champMapHint');
                mapDiv.innerHTML = '';
                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }

                const parcelles = Array.isArray(champ?.parcelles) ? champ.parcelles : [];
                const boundsPoints = [];
                const paths = [];
                parcelles.forEach(function (parcelle) {
                    const latlngs = parcellePoints(parcelle);
                    if (latlngs.length >= 2) {
                        paths.push(latlngs);
                        latlngs.forEach(function (pt) { boundsPoints.push(pt); });
                    }
                });

                const lat = Number(exploitation?.latitude);
                const lng = Number(exploitation?.longitude);
                const hasMarker = Number.isFinite(lat) && Number.isFinite(lng);

                mapInstance = L.map(mapDiv);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19
                }).addTo(mapInstance);

                paths.forEach(function (latlngs) {
                    if (latlngs.length >= 3) {
                        L.polygon(latlngs, { color: '#1f6feb', weight: 3, fillOpacity: 0.25 }).addTo(mapInstance);
                    } else {
                        L.polyline(latlngs, { color: '#1f6feb', weight: 3 }).addTo(mapInstance);
                    }
                });

                if (hasMarker) {
                    L.marker([lat, lng]).addTo(mapInstance);
                    boundsPoints.push([lat, lng]);
                }

                if (boundsPoints.length) {
                    hintEl.className = 'alert alert-info mb-2';
                    hintEl.textContent = paths.length
                        ? ('Parcelle(s) : ' + parcelles.length + ' — points : ' + boundsPoints.length)
                        : 'Position du champ (coordonnées de l’exploitation).';
                    mapInstance.fitBounds(L.latLngBounds(boundsPoints), { padding: [30, 30], maxZoom: 17 });
                } else {
                    hintEl.className = 'alert alert-warning mb-2';
                    hintEl.textContent = 'Aucune cartographie disponible pour ce champ.';
                    mapInstance.setView([6.8, -5.3], 7);
                }

                setTimeout(function () { mapInstance.invalidateSize(); }, 200);
            }

            async function load() {
                try {
                    const res = await fetch(
                        `${apiUrl}?action=champ&id=${encodeURIComponent(planteurId)}&champ_id=${encodeURIComponent(champId)}`,
                        { cache: 'no-store' }
                    );
                    const json = await res.json();
                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Impossible de charger le champ.');
                    }

                    const planteur = json.data?.planteur || {};
                    const champ = json.data?.champ || {};
                    const exploitations = Array.isArray(planteur.exploitations) ? planteur.exploitations : [];
                    const expl = exploitations.find(function (e) { return e.id && e.id === champ.exploitation_id; })
                        || planteur.exploitation
                        || {};
                    const photo = planteur.photo_url || planteur.photo || defaultPhoto;

                    document.getElementById('planteurPhoto').src = photo;
                    document.getElementById('planteurPhoto').onerror = function () {
                        this.onerror = null;
                        this.src = defaultPhoto;
                    };
                    setText('planteurNom', planteur.nom_prenoms || 'Planteur');
                    document.getElementById('champCode').textContent = champ.numero_champ || '—';
                    document.getElementById('pageTitle').textContent = champ.numero_champ || 'Détail du champ';
                    setText('planteurFiche', planteur.numero_fiche ? 'Fiche ' + planteur.numero_fiche : '');
                    setText('champType', champ.type_culture);
                    setText('champSuperficie', fmtNumber(champ.superficie_ha, ' ha'));
                    setText('champAge', champ.age_culture);
                    setText('champMode', champ.mode_culture);
                    setText('champProduction', fmtNumber(champ.production_estimee_kg, ' kg'));
                    setText('explExploitation', champ.exploitation_label);
                    setText('explRegion', expl.region);
                    setText('explSousPrefecture', expl.sous_prefecture_village);
                    setText('explVillage', expl.village);
                    setText('explLat', expl.latitude);
                    setText('explLng', expl.longitude);

                    document.getElementById('champLoader').classList.add('d-none');
                    document.getElementById('champContent').classList.remove('d-none');
                    setTimeout(function () { drawMap(champ, expl); }, 50);
                } catch (error) {
                    document.getElementById('champLoader').classList.add('d-none');
                    const errorEl = document.getElementById('champError');
                    errorEl.textContent = error.message || String(error);
                    errorEl.classList.remove('d-none');
                }
            }

            load();
        });
    </script>
@endpush
