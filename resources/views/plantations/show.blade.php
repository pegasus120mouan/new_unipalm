@extends('layout.main')

@section('title', 'Détails du planteur')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Détails du planteur</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('plantations.champs', $planteurId) }}" class="btn btn-info btn-sm">
                <i class="bi bi-grid"></i> Champs
            </a>
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

                <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                    <h5 class="mb-0">Exploitations <span class="badge bg-secondary" id="exploitationsBadge">0</span></h5>
                    <button type="button" id="openParcellesMap" class="btn btn-info btn-sm d-none">
                        <i class="bi bi-geo-alt"></i> Cartographie (toutes)
                    </button>
                </div>

                <div id="exploitationsContainer"></div>
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
            const champUrlTemplate = @json(url('/plantations'));
            const defaultPhoto = "data:image/svg+xml," + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="140" height="140" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#E9ECEF"/><circle cx="40" cy="32" r="14" fill="#ADB5BD"/><path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/></svg>');
            let currentParcelles = [];
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

            /** Parcelles tracées d'une exploitation, avec le code du champ auquel chacune appartient */
            function parcellesOf(expl, champCodes) {
                const cultures = (Array.isArray(expl?.cultures) ? expl.cultures : [])
                    .slice().sort(function (a, b) { return (a?.id || 0) - (b?.id || 0); });
                const list = cultures.flatMap((c) => (Array.isArray(c?.parcelles) ? c.parcelles : [])
                    .map((p) => Object.assign({}, p, { _champCode: champCodes.get(c.id) || '' })));
                const source = list.length ? list : (Array.isArray(expl?.parcelles) ? expl.parcelles : []);
                return source.filter((p) => parcelleLatLngs(p).length >= 2);
            }

            function exploitationsOf(planteur) {
                if (Array.isArray(planteur?.exploitations) && planteur.exploitations.length) return planteur.exploitations;
                if (!planteur?.exploitation && !(planteur?.cultures || []).length) return [];
                return [Object.assign({}, planteur.exploitation || {}, {
                    numero: 1,
                    cultures: planteur.cultures || [],
                    informations: planteur.informations || planteur.informations_complementaires || null,
                })];
            }

            function openMap(parcelles) {
                currentParcelles = parcelles;
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('parcellesMapModalDetails'));
                modal.show();
            }

            function parcelleLatLngs(parcelle) {
                let points = parcelle?.points;
                if (typeof points === 'string') {
                    try { points = JSON.parse(points); } catch (e) { points = null; }
                }
                const list = Array.isArray(points) ? points : (points && typeof points === 'object' ? Object.values(points) : []);
                return list.map((pt) => {
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

            const PARCELLE_COLORS = ['#1f6feb', '#2E7D32', '#E65100', '#8E24AA', '#C62828', '#00838F'];

            /** Dessine les parcelles dans mapDiv ; retourne la carte Leaflet (ou null s'il n'y a aucun point) */
            function renderParcellesMap(mapDiv, parcellesList) {
                const boundsPoints = [];
                const map = L.map(mapDiv);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19
                }).addTo(map);

                parcellesList.forEach((parcelle, index) => {
                    const latlngs = parcelleLatLngs(parcelle);
                    if (latlngs.length < 2) return;
                    boundsPoints.push(...latlngs);
                    const color = PARCELLE_COLORS[index % PARCELLE_COLORS.length];
                    const shape = latlngs.length >= 3
                        ? L.polygon(latlngs, { color, weight: 3, fillOpacity: 0.2 })
                        : L.polyline(latlngs, { color, weight: 3 });
                    const label = [parcelle._champCode, parcelle.nom].filter(Boolean).join(' — ');
                    const sup = Number(parcelle.superficie_calculee);
                    shape.bindTooltip(`${escapeHtml(label || 'Parcelle')}${Number.isFinite(sup) && sup > 0 ? ` • ${sup.toFixed(2)} ha` : ''}`);
                    shape.addTo(map);
                });

                if (!boundsPoints.length) {
                    map.remove();
                    return null;
                }
                map.fitBounds(L.latLngBounds(boundsPoints), { padding: [30, 30] });
                setTimeout(() => map.invalidateSize(), 200);
                return map;
            }

            function drawParcelles(parcellesList) {
                const mapDiv = document.getElementById('parcellesMapDetails');
                const hintEl = document.getElementById('parcellesMapHintDetails');
                mapDiv.innerHTML = '';

                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }

                const nbPoints = parcellesList.reduce((sum, p) => sum + parcelleLatLngs(p).length, 0);
                hintEl.textContent = `Parcelles: ${parcellesList.length} | Points: ${nbPoints}`;
                hintEl.classList.remove('d-none');
                if (!nbPoints) return;

                mapInstance = renderParcellesMap(mapDiv, parcellesList);
            }

            const inlineMaps = [];
            let pendingInlineMaps = [];

            /** Cartes affichées directement dans chaque exploitation (après affichage du contenu) */
            function drawInlineMaps() {
                inlineMaps.splice(0).forEach((m) => m.remove());
                pendingInlineMaps.forEach(({ elementId, parcelles }) => {
                    const el = document.getElementById(elementId);
                    if (!el) return;
                    const map = renderParcellesMap(el, parcelles);
                    if (map) inlineMaps.push(map);
                    else el.classList.add('d-none');
                });
            }

            function infoRow(label, value) {
                const v = value === null || value === undefined || value === '' ? '—' : value;
                return `<div class="col-md-6"><strong>${escapeHtml(label)} :</strong> ${escapeHtml(v)}</div>`;
            }

            function renderExploitation(expl, champCodes) {
                const cultures = (Array.isArray(expl.cultures) ? expl.cultures : [])
                    .slice().sort(function (a, b) { return (a?.id || 0) - (b?.id || 0); });
                const superficie = cultures.reduce((sum, c) => sum + (Number(c.superficie_ha) || 0), 0);
                const info = Array.isArray(expl.informations) ? (expl.informations[0] || {}) : (expl.informations || {});
                const phyto = info.usage_phytosanitaires === true || info.usage_phytosanitaires === 1 || info.usage_phytosanitaires === '1'
                    ? 'Oui'
                    : (info.usage_phytosanitaires === false || info.usage_phytosanitaires === 0 || info.usage_phytosanitaires === '0' ? 'Non' : '—');
                const parcelles = parcellesOf(expl, champCodes);
                const hasParcelles = parcelles.length > 0;
                const mapId = `exploitationMap${escapeHtml(expl.numero)}`;
                if (hasParcelles) pendingInlineMaps.push({ elementId: mapId, parcelles });

                const rows = cultures.length
                    ? cultures.map((c) => {
                        const code = champCodes.get(c.id) || c.numero_champ || c.code_champ || '—';
                        const champHref = c.id ? `${champUrlTemplate}/${planteurId}/champs/${encodeURIComponent(c.id)}` : '';
                        const codeHtml = champHref
                            ? `<a href="${champHref}" class="text-decoration-none" title="Voir le détail du champ"><code class="text-danger">${escapeHtml(code)}</code></a>`
                            : `<code class="text-danger">${escapeHtml(code)}</code>`;
                        const champParcelles = (Array.isArray(c.parcelles) ? c.parcelles : []).filter((p) => parcelleLatLngs(p).length >= 3);
                        const supGps = champParcelles.reduce((sum, p) => sum + (Number(p.superficie_calculee) || 0), 0);
                        const parcelleCell = champParcelles.length
                            ? `<span class="badge bg-success">Tracée</span>${supGps > 0 ? ` <small class="text-muted">${supGps.toFixed(2)} ha GPS</small>` : ''}`
                            : '<span class="badge bg-secondary">Non tracée</span>';
                        return `<tr>
                            <td>${codeHtml}</td>
                            <td>${escapeHtml(c.type_culture === 'Autre' && c.autre_culture ? c.autre_culture : (c.type_culture || c.autre_culture || '—'))}</td>
                            <td>${escapeHtml(c.superficie_ha ?? '—')}</td>
                            <td>${escapeHtml(c.age_culture ?? '—')}</td>
                            <td>${escapeHtml(c.mode_culture ?? '—')}</td>
                            <td>${escapeHtml(c.production_estimee_kg ?? '—')}</td>
                            <td>${parcelleCell}</td>
                        </tr>`;
                    }).join('')
                    : '<tr><td colspan="7" class="text-center text-muted">Aucun champ</td></tr>';

                const parcellesRows = parcelles.map((p, index) => {
                    const sup = Number(p.superficie_calculee);
                    const color = PARCELLE_COLORS[index % PARCELLE_COLORS.length];
                    return `<tr>
                        <td><span class="d-inline-block rounded-circle me-2" style="width:10px;height:10px;background:${color}"></span><code class="text-danger">${escapeHtml(p._champCode || '—')}</code></td>
                        <td>${escapeHtml(p.nom || '—')}</td>
                        <td>${parcelleLatLngs(p).length}</td>
                        <td>${Number.isFinite(sup) && sup > 0 ? sup.toFixed(2) : '—'}</td>
                        <td>${escapeHtml(fmtDate(p.created_at))}</td>
                    </tr>`;
                }).join('');

                const parcellesSection = hasParcelles
                    ? `<div id="${mapId}" class="rounded border mb-3" style="height:320px;width:100%;"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Champ</th>
                                        <th>Nom de la parcelle</th>
                                        <th>Points GPS</th>
                                        <th>Superficie GPS (ha)</th>
                                        <th>Tracée le</th>
                                    </tr>
                                </thead>
                                <tbody>${parcellesRows}</tbody>
                            </table>
                        </div>`
                    : `<div class="alert alert-light border mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Aucune parcelle tracée pour cette exploitation. Le tracé GPS se fait depuis l'application mobile, sur chaque culture.
                        </div>`;

                const lieu = [expl.village, expl.sous_prefecture_village, expl.region].filter(Boolean).join(', ');

                return `<div class="card mb-3 exploitation-card" data-numero="${escapeHtml(expl.numero)}">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>
                            <strong>Exploitation ${escapeHtml(expl.numero)}</strong>
                            ${lieu ? `<span class="text-muted"> — ${escapeHtml(lieu)}</span>` : ''}
                        </span>
                        <span class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark">${cultures.length} champ(s) • ${superficie.toFixed(2)} ha</span>
                            ${hasParcelles ? `<button type="button" class="btn btn-info btn-sm js-map-exploitation" data-numero="${escapeHtml(expl.numero)}"><i class="bi bi-geo-alt"></i> Cartographie</button>` : ''}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            ${infoRow('Région', expl.region)}
                            ${infoRow('Sous-préfecture', expl.sous_prefecture_village)}
                            ${infoRow('Village', expl.village)}
                            ${infoRow('Délégué', expl.delegue_nom)}
                            ${infoRow('Latitude', expl.latitude)}
                            ${infoRow('Longitude', expl.longitude)}
                        </div>
                        ${expl.video_url ? `<div class="mt-3"><strong>Vidéo :</strong>
                            <video controls class="w-100 mt-2" style="max-height:420px;" preload="metadata" src="${escapeHtml(expl.video_url)}"></video>
                        </div>` : ''}

                        <h6 class="mt-4">Champs</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>N° champ</th>
                                        <th>Type</th>
                                        <th>Superficie (ha)</th>
                                        <th>Âge</th>
                                        <th>Mode</th>
                                        <th>Production estimée (kg)</th>
                                        <th>Parcelle</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>

                        <h6 class="mt-4">Parcelles <span class="badge bg-secondary">${parcelles.length}</span></h6>
                        ${parcellesSection}

                        <h6 class="mt-4">Informations complémentaires</h6>
                        <div class="row g-2">
                            ${infoRow('Semences', info.type_semences)}
                            ${infoRow('Phytosanitaires', phyto)}
                            ${infoRow('Travailleurs', info.nombre_travailleurs)}
                        </div>
                    </div>
                </div>`;
            }

            function fill(planteur) {
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

                const exploitations = exploitationsOf(planteur);

                // Codes des champs numérotés sur l'ensemble des exploitations (même logique que la page Champs)
                const fiche = planteur.numero_fiche || '';
                const champCodes = new Map();
                exploitations
                    .flatMap((e) => Array.isArray(e.cultures) ? e.cultures : [])
                    .slice()
                    .sort(function (a, b) { return (a?.id || 0) - (b?.id || 0); })
                    .forEach((c, index) => {
                        const seq = String(index + 1).padStart(2, '0');
                        champCodes.set(c.id, c.numero_champ || c.code_champ || (fiche ? `${fiche}-C${seq}` : `CHAMP-${planteur.id || 0}-C${seq}`));
                    });

                document.getElementById('exploitationsBadge').textContent = exploitations.length;
                pendingInlineMaps = [];
                const container = document.getElementById('exploitationsContainer');
                container.innerHTML = exploitations.length
                    ? exploitations.map((e) => renderExploitation(e, champCodes)).join('')
                    : '<div class="card"><div class="card-body text-center text-muted">Aucune exploitation enregistrée</div></div>';

                container.querySelectorAll('.js-map-exploitation').forEach((btn) => {
                    btn.addEventListener('click', function () {
                        const expl = exploitations.find((e) => String(e.numero) === this.dataset.numero);
                        openMap(parcellesOf(expl, champCodes));
                    });
                });

                const allParcelles = exploitations.flatMap((e) => parcellesOf(e, champCodes));
                const mapBtn = document.getElementById('openParcellesMap');
                mapBtn.onclick = () => openMap(allParcelles);
                if (allParcelles.length) mapBtn.classList.remove('d-none');
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
                    drawInlineMaps();
                } catch (e) {
                    document.getElementById('planteurLoader').classList.add('d-none');
                    const err = document.getElementById('planteurError');
                    err.textContent = e.message || String(e);
                    err.classList.remove('d-none');
                }
            }

            document.getElementById('parcellesMapModalDetails').addEventListener('shown.bs.modal', function () {
                drawParcelles(currentParcelles);
            });

            load();
        });
    </script>
@endpush
