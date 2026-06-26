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
@endsection

@push('scripts')
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
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiBaseUrl = @json(route('plantations.api'));
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

                    return `
                        <tr>
                            <td>
                                <img src="${escapeHtml(photoSrc)}" alt="Photo" class="planteur-photo"
                                    onerror="this.onerror=null;this.src='${defaultPhoto}';">
                            </td>
                            <td><code>${escapeHtml(planteur.numero_fiche || '—')}</code></td>
                            <td class="fw-semibold">${escapeHtml(planteur.nom_prenoms || '—')}</td>
                            <td>${escapeHtml(planteur.telephone || '—')}</td>
                            <td>${escapeHtml(collecteur)}</td>
                            <td>${escapeHtml(exploitation.region || '—')}</td>
                            <td>${escapeHtml(exploitation.sous_prefecture_village || '—')}</td>
                            <td>${escapeHtml(exploitation.village || '—')}</td>
                            <td>${escapeHtml(fmtDate(planteur.created_at))}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger delete-planteur-btn"
                                    data-id="${escapeHtml(planteur.id)}"
                                    data-name="${escapeHtml(planteur.nom_prenoms || planteur.numero_fiche || planteur.id)}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                document.querySelectorAll('.delete-planteur-btn').forEach(function (button) {
                    button.addEventListener('click', function () {
                        deletePlanteurId = button.dataset.id;
                        document.getElementById('deletePlanteurName').textContent = button.dataset.name || '';
                        new bootstrap.Modal(document.getElementById('deletePlanteurModal')).show();
                    });
                });
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
