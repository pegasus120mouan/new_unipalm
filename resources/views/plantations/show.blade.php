@extends('layout.main')

@section('title', 'Détails du planteur')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Détails du planteur</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('plantations.edit', $planteurId) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Modifier
            </a>
            <a href="{{ route('plantations.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div id="planteurError" class="alert alert-danger d-none" role="alert"></div>

    <div id="planteurLoader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-3">Chargement des informations...</div>
    </div>

    <div id="planteurContent" class="d-none">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card text-center">
                    <div class="card-body">
                        <img id="planteurPhoto" src="" alt="Photo" class="rounded-circle border"
                            style="width:140px;height:140px;object-fit:cover;">
                        <h4 id="planteurNom" class="mt-3 mb-1"></h4>
                        <div id="planteurFiche" class="text-muted"></div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">Contact</div>
                    <div class="card-body small">
                        <div><strong>Téléphone :</strong> <span id="planteurTel"></span></div>
                        <div class="mt-2"><strong>Pièce :</strong> <span id="planteurPiece"></span></div>
                        <div class="mt-2"><strong>Situation :</strong> <span id="planteurSituation"></span></div>
                        <div class="mt-2"><strong>Enfants :</strong> <span id="planteurEnfants"></span></div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">Collecteur</div>
                    <div class="card-body" id="planteurCollecteur"></div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Identité</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Date naissance :</strong> <span id="planteurDateN"></span></div>
                            <div class="col-md-6"><strong>Lieu naissance :</strong> <span id="planteurLieuN"></span></div>
                            <div class="col-md-6"><strong>Date enregistrement :</strong> <span id="planteurDateEnreg"></span></div>
                            <div class="col-md-6"><strong>Créé le :</strong> <span id="planteurCreatedAt"></span></div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Exploitation</span>
                        <button type="button" id="openParcellesMap" class="btn btn-info btn-sm d-none">
                            <i class="bi bi-geo-alt"></i> Cartographie
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Région :</strong> <span id="explRegion"></span></div>
                            <div class="col-md-6"><strong>Sous-préfecture :</strong> <span id="explSousPrefecture"></span></div>
                            <div class="col-md-6"><strong>Village :</strong> <span id="explVillage"></span></div>
                            <div class="col-md-6"><strong>Latitude :</strong> <span id="explLat"></span></div>
                            <div class="col-md-6"><strong>Longitude :</strong> <span id="explLng"></span></div>
                            <div class="col-md-6"><strong>Délégué :</strong> <span id="explDelegue"></span></div>
                        </div>
                        <div id="videoWrap" class="mt-3 d-none">
                            <strong>Vidéo :</strong>
                            <video id="explVideo" controls class="w-100 mt-2" style="max-height:420px;" preload="metadata"></video>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">Cultures</div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Superficie (ha)</th>
                                    <th>Âge</th>
                                    <th>Mode</th>
                                    <th>Production estimée (kg)</th>
                                </tr>
                            </thead>
                            <tbody id="culturesTbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">Informations complémentaires</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Semences :</strong> <span id="infoSemences"></span></div>
                            <div class="col-md-6"><strong>Phytosanitaires :</strong> <span id="infoPhyto"></span></div>
                            <div class="col-md-6"><strong>Travailleurs :</strong> <span id="infoTrav"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="parcellesMapModalDetails" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cartographie des parcelles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="parcellesMapHintDetails" class="alert alert-info d-none"></div>
                    <div id="parcellesMapDetails" style="height:70vh;width:100%;"></div>
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
            const apiUrl = @json(route('plantations.api'));
            const defaultPhoto = "data:image/svg+xml," + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="140" height="140" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#E9ECEF"/><circle cx="40" cy="32" r="14" fill="#ADB5BD"/><path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/></svg>');
            let currentPlanteur = null;
            let mapInstance = null;

            function escapeHtml(v) {
                return String(v ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function fmtDate(v) {
                if (!v) return '—';
                const d = new Date(v);
                if (Number.isNaN(d.getTime())) return v;
                return d.toLocaleDateString('fr-FR');
            }

            function setText(id, value) {
                document.getElementById(id).textContent = value || '—';
            }

            function collectParcelles(planteur) {
                const cultures = Array.isArray(planteur?.cultures) ? planteur.cultures : [];
                const fromCultures = cultures.flatMap((c) => Array.isArray(c?.parcelles) ? c.parcelles : []);
                if (fromCultures.length) return fromCultures;
                if (Array.isArray(planteur?.parcelles)) return planteur.parcelles;
                if (Array.isArray(planteur?.exploitation?.parcelles)) return planteur.exploitation.parcelles;
                return [];
            }

            function drawParcelles(planteur) {
                const mapDiv = document.getElementById('parcellesMapDetails');
                const hintEl = document.getElementById('parcellesMapHintDetails');
                mapDiv.innerHTML = '';
                hintEl.classList.add('d-none');

                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }

                const parcellesList = collectParcelles(planteur);
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
                            const la = Number(pt[0]); const lo = Number(pt[1]);
                            if (Number.isFinite(la) && Number.isFinite(lo)) { boundsPoints.push([la, lo]); return [la, lo]; }
                        }
                        const la = Number(pt?.latitude ?? pt?.lat);
                        const lo = Number(pt?.longitude ?? pt?.lng ?? pt?.lon);
                        if (Number.isFinite(la) && Number.isFinite(lo)) { boundsPoints.push([la, lo]); return [la, lo]; }
                        return null;
                    }).filter(Boolean);
                    if (latlngs.length >= 2) paths.push(latlngs);
                });

                hintEl.textContent = `Parcelles: ${parcellesList.length} | Points: ${boundsPoints.length}`;
                hintEl.classList.remove('d-none');
                if (!boundsPoints.length) return;

                mapInstance = L.map(mapDiv);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19
                }).addTo(mapInstance);

                paths.forEach((latlngs) => {
                    if (latlngs.length >= 3) L.polygon(latlngs, { color: '#1f6feb', weight: 3, fillOpacity: 0.2 }).addTo(mapInstance);
                    else L.polyline(latlngs, { color: '#1f6feb', weight: 3 }).addTo(mapInstance);
                });

                mapInstance.fitBounds(L.latLngBounds(boundsPoints), { padding: [30, 30] });
                setTimeout(() => mapInstance.invalidateSize(), 200);
            }

            function fill(planteur) {
                currentPlanteur = planteur;
                const photo = planteur.photo_url || planteur.photo || defaultPhoto;
                const photoEl = document.getElementById('planteurPhoto');
                photoEl.src = photo;
                photoEl.onerror = function () { this.onerror = null; this.src = defaultPhoto; };

                setText('planteurNom', planteur.nom_prenoms);
                setText('planteurFiche', planteur.numero_fiche);
                setText('planteurTel', planteur.telephone);
                setText('planteurPiece', planteur.piece_identite);
                setText('planteurSituation', planteur.situation_matrimoniale);
                setText('planteurEnfants', planteur.nombre_enfants);
                setText('planteurDateN', fmtDate(planteur.date_naissance));
                setText('planteurLieuN', planteur.lieu_naissance);
                setText('planteurDateEnreg', fmtDate(planteur.date_enregistrement));
                setText('planteurCreatedAt', fmtDate(planteur.created_at));

                const collecteur = planteur.collecteur
                    ? `${planteur.collecteur.nom ?? ''} ${planteur.collecteur.prenoms ?? ''}`.trim()
                    : '—';
                document.getElementById('planteurCollecteur').textContent = collecteur || '—';

                const expl = planteur.exploitation || {};
                setText('explRegion', expl.region);
                setText('explSousPrefecture', expl.sous_prefecture_village);
                setText('explVillage', expl.village);
                setText('explLat', expl.latitude);
                setText('explLng', expl.longitude);
                setText('explDelegue', expl.delegue_nom);

                const videoUrl = expl.video_url || '';
                const videoWrap = document.getElementById('videoWrap');
                if (videoUrl) {
                    document.getElementById('explVideo').src = videoUrl;
                    videoWrap.classList.remove('d-none');
                } else {
                    videoWrap.classList.add('d-none');
                }

                const cultures = Array.isArray(planteur.cultures) ? planteur.cultures : [];
                document.getElementById('culturesTbody').innerHTML = cultures.length
                    ? cultures.map((c) => `<tr>
                        <td>${escapeHtml(c.type_culture || c.autre_culture || '—')}</td>
                        <td>${escapeHtml(c.superficie_ha ?? '—')}</td>
                        <td>${escapeHtml(c.age_culture ?? '—')}</td>
                        <td>${escapeHtml(c.mode_culture ?? '—')}</td>
                        <td>${escapeHtml(c.production_estimee_kg ?? '—')}</td>
                    </tr>`).join('')
                    : '<tr><td colspan="5" class="text-center text-muted">Aucune culture</td></tr>';

                const info = Array.isArray(planteur.informations_complementaires)
                    ? (planteur.informations_complementaires[0] || {})
                    : (planteur.informations_complementaires || {});
                setText('infoSemences', info.type_semences);
                setText('infoPhyto', info.usage_phytosanitaires);
                setText('infoTrav', info.nombre_travailleurs);

                const mapBtn = document.getElementById('openParcellesMap');
                if (collectParcelles(planteur).length) mapBtn.classList.remove('d-none');
                else mapBtn.classList.add('d-none');
            }

            async function load() {
                try {
                    const res = await fetch(`${apiUrl}?action=planteurs&id=${encodeURIComponent(planteurId)}`, { cache: 'no-store' });
                    const json = await res.json();
                    if (!res.ok || !json?.success) throw new Error(json?.error || json?.message || 'Erreur API');
                    const planteur = json.data?.planteurs?.[0] || json.data;
                    if (!planteur?.id) throw new Error('Planteur introuvable.');
                    fill(planteur);
                    document.getElementById('planteurLoader').classList.add('d-none');
                    document.getElementById('planteurContent').classList.remove('d-none');
                } catch (e) {
                    document.getElementById('planteurLoader').classList.add('d-none');
                    const err = document.getElementById('planteurError');
                    err.textContent = e.message || String(e);
                    err.classList.remove('d-none');
                }
            }

            document.getElementById('openParcellesMap').addEventListener('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('parcellesMapModalDetails'));
                modal.show();
            });

            document.getElementById('parcellesMapModalDetails').addEventListener('shown.bs.modal', function () {
                if (currentPlanteur) drawParcelles(currentPlanteur);
            });

            load();
        });
    </script>
@endpush
