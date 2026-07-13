@extends('layout.main')

@section('title', 'Liste des plantations')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Liste des planteurs</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Plantations</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <div id="planteursError" class="alert alert-danger d-none" role="alert"></div>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-funnel"></i> Filtres de recherche
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="filterNom" class="form-label">Nom / Prénom</label>
                            <input type="text" id="filterNom" class="form-control" placeholder="Rechercher...">
                        </div>
                        <div class="col-md-3">
                            <label for="filterNumeroFiche" class="form-label">N° fiche</label>
                            <input type="text" id="filterNumeroFiche" class="form-control" placeholder="Rechercher...">
                        </div>
                        <div class="col-md-3">
                            <label for="filterTelephone" class="form-label">Téléphone</label>
                            <input type="text" id="filterTelephone" class="form-control" placeholder="Rechercher...">
                        </div>
                        <div class="col-md-3">
                            <label for="filterCollecteur" class="form-label">Collecteur</label>
                            <input type="text" id="filterCollecteur" class="form-control" placeholder="Rechercher...">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" id="planteursRefresh" class="btn btn-primary">
                            <i class="bi bi-search"></i> Rechercher
                        </button>
                        <button type="button" id="planteursReset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card border-0 text-white" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h3 class="mb-1 fw-bold" id="superficieTotale">0,00 ha</h3>
                        <div class="small opacity-75">Superficie totale des plantations</div>
                    </div>
                    <span class="badge bg-white bg-opacity-25 text-white px-3 py-2">
                        <i class="bi bi-people"></i> <span id="nombrePlanteurs">0</span> planteurs
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des planteurs</span>
                    <span class="text-muted small" id="paginationInfoTop"></span>
                </div>
                <div class="card-body">
                    <div id="loader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-3">Chargement des planteurs...</div>
                    </div>

                    <div class="table-responsive d-none" id="tableWrapper">
                        <table class="table table-striped table-hover align-middle" id="planteursTable">
                            <thead class="plantations-table-header">
                                <tr>
                                    <th>Photo</th>
                                    <th>N° fiche</th>
                                    <th>Nom & prénoms</th>
                                    <th>Téléphone</th>
                                    <th>Collecteur</th>
                                    <th>Région</th>
                                    <th>Sous-préfecture</th>
                                    <th>Village</th>
                                    <th>Créé le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="planteursTbody"></tbody>
                        </table>
                    </div>

                    <div id="paginationContainer" class="d-none justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <div class="text-muted small" id="paginationInfo"></div>
                        <nav>
                            <ul class="pagination mb-0" id="paginationNav"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="deletePlanteurModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Supprimer le planteur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Confirmer la suppression de <strong id="deletePlanteurName"></strong> ?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePlanteurBtn">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

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
        .plantations-table-header {
            background: #111;
        }

        .plantations-table-header th {
            color: #fff !important;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            white-space: nowrap;
        }

        .planteur-photo {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #e9ecef;
        }

        .action-buttons {
            display: inline-flex;
            gap: 0.35rem;
            align-items: center;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }

        .action-btn.view,
        .action-btn.map,
        .action-btn.edit {
            background: #4dabf7;
        }

        .action-btn.delete {
            background: #fa5252;
        }

        .action-btn:hover {
            opacity: 0.9;
            color: #fff;
        }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiBaseUrl = @json(route('plantations.api'));
            const showUrlTemplate = @json(url('/plantations'));
            const csrfToken = @json(csrf_token());
            const errorEl = document.getElementById('planteursError');
            const loaderEl = document.getElementById('loader');
            const tableWrapper = document.getElementById('tableWrapper');
            const tbodyEl = document.getElementById('planteursTbody');
            const paginationContainer = document.getElementById('paginationContainer');
            const paginationInfo = document.getElementById('paginationInfo');
            const paginationInfoTop = document.getElementById('paginationInfoTop');
            const paginationNav = document.getElementById('paginationNav');
            const defaultPhoto = "data:image/svg+xml," + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#E9ECEF"/><circle cx="40" cy="32" r="14" fill="#ADB5BD"/><path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/></svg>');

            let allRows = [];
            let currentPage = 1;
            let totalPages = 1;
            let totalCount = 0;
            let deletePlanteurId = null;
            let pendingMapPlanteur = null;
            let mapInstance = null;
            const limit = 15;

            function buildApiUrl(params) {
                const qs = new URLSearchParams(params || {}).toString();
                return qs ? `${apiBaseUrl}?${qs}` : apiBaseUrl;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function fmtDate(value) {
                if (!value) return '—';
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return value;
                return date.toLocaleDateString('fr-FR');
            }

            function getPhotoValue(planteur) {
                return planteur?.photo_url || planteur?.image_url || planteur?.photo || planteur?.photo_planteur || '';
            }

            function showError(message) {
                errorEl.textContent = message;
                errorEl.classList.remove('d-none');
            }

            function hideError() {
                errorEl.classList.add('d-none');
                errorEl.textContent = '';
            }

            function render(rows) {
                if (rows.length === 0) {
                    tbodyEl.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">Aucun planteur trouvé.</td></tr>';
                    return;
                }

                tbodyEl.innerHTML = rows.map(function (planteur) {
                    const collecteur = planteur.collecteur
                        ? `${planteur.collecteur.nom ?? ''} ${planteur.collecteur.prenoms ?? ''}`.trim()
                        : '—';
                    const exploitation = planteur.exploitation || {};
                    const photoSrc = getPhotoValue(planteur) || defaultPhoto;
                    const id = escapeHtml(planteur.id);
                    const name = escapeHtml(planteur.nom_prenoms || planteur.numero_fiche || planteur.id);

                    return `
                        <tr>
                            <td>
                                <img src="${escapeHtml(photoSrc)}" alt="Photo" class="planteur-photo"
                                    onerror="this.onerror=null;this.src='${defaultPhoto}';">
                            </td>
                            <td><code class="text-danger">${escapeHtml(planteur.numero_fiche || '—')}</code></td>
                            <td class="fw-semibold">${escapeHtml(planteur.nom_prenoms || '—')}</td>
                            <td>${escapeHtml(planteur.telephone || '—')}</td>
                            <td>${escapeHtml(collecteur)}</td>
                            <td>${escapeHtml(exploitation.region || '—')}</td>
                            <td>${escapeHtml(exploitation.sous_prefecture_village || '—')}</td>
                            <td>${escapeHtml(exploitation.village || '—')}</td>
                            <td>${escapeHtml(fmtDate(planteur.created_at))}</td>
                            <td class="text-end">
                                <div class="action-buttons">
                                    <a class="action-btn view" href="${showUrlTemplate}/${id}" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="action-btn map" data-action="map" data-id="${id}" title="Voir sur la carte">
                                        <i class="bi bi-geo-alt"></i>
                                    </button>
                                    <a class="action-btn edit" href="${showUrlTemplate}/${id}/edit" title="Modifier">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="action-btn delete" data-action="delete"
                                        data-id="${id}" data-name="${name}" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function applyFilter() {
                const filterNom = (document.getElementById('filterNom').value || '').toLowerCase().trim();
                const filterNumeroFiche = (document.getElementById('filterNumeroFiche').value || '').toLowerCase().trim();
                const filterTel = (document.getElementById('filterTelephone').value || '').toLowerCase().trim();
                const filterCollecteur = (document.getElementById('filterCollecteur').value || '').toLowerCase().trim();

                if (!filterNom && !filterNumeroFiche && !filterTel && !filterCollecteur) {
                    render(allRows);
                    return;
                }

                const filtered = allRows.filter(function (planteur) {
                    const collecteur = planteur.collecteur
                        ? `${planteur.collecteur.nom ?? ''} ${planteur.collecteur.prenoms ?? ''}`.trim().toLowerCase()
                        : '';
                    const nom = (planteur.nom_prenoms || '').toLowerCase();
                    const numeroFiche = (planteur.numero_fiche || '').toLowerCase();
                    const tel = (planteur.telephone || '').toLowerCase();

                    let match = true;
                    if (filterNom && !nom.includes(filterNom)) match = false;
                    if (filterNumeroFiche && !numeroFiche.includes(filterNumeroFiche)) match = false;
                    if (filterTel && !tel.includes(filterTel)) match = false;
                    if (filterCollecteur && !collecteur.includes(filterCollecteur)) match = false;

                    return match;
                });

                render(filtered);
            }

            function updateSuperficieTotale(data) {
                document.getElementById('nombrePlanteurs').textContent = Number(data?.total || 0).toLocaleString('fr-FR');

                if (data?.superficie_totale !== undefined) {
                    const superficie = parseFloat(data.superficie_totale) || 0;
                    document.getElementById('superficieTotale').textContent =
                        superficie.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ha';
                    return;
                }

                loadSuperficieTotale();
            }

            async function loadSuperficieTotale() {
                const superficieEl = document.getElementById('superficieTotale');
                superficieEl.textContent = 'Chargement...';

                try {
                    const res = await fetch(buildApiUrl({ action: 'stats' }), { cache: 'no-store' });
                    const json = await res.json();

                    if (res.ok && json?.success && json.data?.superficie_totale !== undefined) {
                        const superficie = parseFloat(json.data.superficie_totale) || 0;
                        superficieEl.textContent =
                            superficie.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ha';
                    } else {
                        superficieEl.textContent = 'N/A';
                    }
                } catch (error) {
                    superficieEl.textContent = 'N/A';
                }
            }

            function renderPagination() {
                if (totalCount === 0) {
                    paginationContainer.classList.add('d-none');
                    paginationContainer.classList.remove('d-flex');
                    paginationInfoTop.textContent = '';
                    return;
                }

                paginationContainer.classList.remove('d-none');
                paginationContainer.classList.add('d-flex');

                const start = (currentPage - 1) * limit + 1;
                const end = Math.min(currentPage * limit, totalCount);
                const info = `Affichage ${start} - ${end} sur ${totalCount} planteurs`;
                paginationInfo.textContent = info;
                paginationInfoTop.textContent = info;

                let html = '';
                html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">Précédent</a>
                </li>`;

                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                if (endPage - startPage < 4) {
                    startPage = Math.max(1, endPage - 4);
                }

                for (let page = startPage; page <= endPage; page++) {
                    html += `<li class="page-item ${page === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${page}">${page}</a>
                    </li>`;
                }

                html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">Suivant</a>
                </li>`;

                paginationNav.innerHTML = html;
            }

            paginationNav.addEventListener('click', function (event) {
                event.preventDefault();
                const target = event.target.closest('[data-page]');
                if (!target) return;

                const page = parseInt(target.getAttribute('data-page'), 10);
                if (page < 1 || page > totalPages || page === currentPage) return;

                load(page);
            });

            async function fetchPlanteurDetails(id) {
                const res = await fetch(buildApiUrl({ action: 'planteurs', id: String(id) }), { cache: 'no-store' });
                const json = await res.json();
                if (!res.ok || !json?.success) {
                    throw new Error(json?.error || json?.message || 'Erreur API');
                }
                const planteur = json?.data?.planteurs?.[0] || json?.data;
                if (!planteur || !planteur.id) {
                    throw new Error('Planteur introuvable.');
                }
                return planteur;
            }

            function drawParcelles(planteur) {
                const hintEl = document.getElementById('parcellesMapHint');
                const mapDiv = document.getElementById('parcellesMap');
                mapDiv.innerHTML = '';
                hintEl.classList.add('d-none');

                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }

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

                hintEl.textContent = `ID: ${planteur?.id ?? ''} | Parcelles: ${parcellesList.length} | Points: ${boundsPoints.length}`;
                hintEl.classList.remove('d-none');
                if (!boundsPoints.length) return;

                mapInstance = L.map(mapDiv);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19
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

            async function load(page = 1) {
                hideError();
                loaderEl.classList.remove('d-none');
                tableWrapper.classList.add('d-none');
                paginationContainer.classList.add('d-none');
                paginationContainer.classList.remove('d-flex');
                tbodyEl.innerHTML = '';

                try {
                    const res = await fetch(buildApiUrl({ action: 'planteurs', page: page, limit: limit }), { cache: 'no-store' });
                    const json = await res.json();

                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Erreur API');
                    }

                    allRows = json.data?.planteurs || [];
                    totalCount = json.data?.total || 0;
                    totalPages = json.data?.total_pages || 1;
                    currentPage = json.data?.page || page;

                    updateSuperficieTotale(json.data);
                    render(allRows);
                    renderPagination();
                    tableWrapper.classList.remove('d-none');
                } catch (error) {
                    showError(error?.message || String(error));
                } finally {
                    loaderEl.classList.add('d-none');
                }
            }

            tbodyEl.addEventListener('click', async function (e) {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;

                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');

                if (action === 'delete') {
                    deletePlanteurId = id;
                    document.getElementById('deletePlanteurName').textContent = btn.dataset.name || '';
                    new bootstrap.Modal(document.getElementById('deletePlanteurModal')).show();
                    return;
                }

                if (action === 'map') {
                    try {
                        pendingMapPlanteur = null;
                        new bootstrap.Modal(document.getElementById('parcellesMapModal')).show();
                        document.getElementById('parcellesMapHint').textContent = 'Chargement de la carte...';
                        document.getElementById('parcellesMapHint').classList.remove('d-none');
                        pendingMapPlanteur = await fetchPlanteurDetails(id);
                        if (document.getElementById('parcellesMapModal').classList.contains('show')) {
                            drawParcelles(pendingMapPlanteur);
                            pendingMapPlanteur = null;
                        }
                    } catch (error) {
                        showError(error?.message || String(error));
                    }
                }
            });

            document.getElementById('parcellesMapModal').addEventListener('shown.bs.modal', function () {
                if (pendingMapPlanteur) {
                    drawParcelles(pendingMapPlanteur);
                    pendingMapPlanteur = null;
                } else if (mapInstance) {
                    setTimeout(() => mapInstance.invalidateSize(), 100);
                }
            });

            document.getElementById('planteursRefresh').addEventListener('click', applyFilter);
            document.getElementById('planteursReset').addEventListener('click', function () {
                document.getElementById('filterNom').value = '';
                document.getElementById('filterNumeroFiche').value = '';
                document.getElementById('filterTelephone').value = '';
                document.getElementById('filterCollecteur').value = '';
                render(allRows);
            });

            document.getElementById('confirmDeletePlanteurBtn').addEventListener('click', async function () {
                if (!deletePlanteurId) return;

                const button = this;
                button.disabled = true;

                try {
                    const res = await fetch(apiBaseUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_planteur',
                            id: deletePlanteurId,
                        }),
                    });

                    const json = await res.json();
                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Suppression impossible');
                    }

                    bootstrap.Modal.getInstance(document.getElementById('deletePlanteurModal'))?.hide();
                    load(currentPage);
                } catch (error) {
                    showError(error?.message || String(error));
                } finally {
                    button.disabled = false;
                }
            });

            load(1);
        });
    </script>
@endpush
