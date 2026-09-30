@extends('layout.main')

@section('title', 'Liste des plantations')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Liste des plantations</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Plantations</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <style>
        .plantations-list-header th {
            color: #fff !important;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            white-space: nowrap;
            background: #111;
        }
    </style>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-muted small">Plantations (champs)</div>
                    <h3 class="mb-0 fw-bold" id="statPlantations">—</h3>
                    <div class="text-muted small" id="statPlanteurs"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-muted small">Superficie totale</div>
                    <h3 class="mb-0 fw-bold" id="statSuperficie">—</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-muted small">Parcelles tracées (GPS)</div>
                    <h3 class="mb-0 fw-bold" id="statTracees">—</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="filtersForm" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1" for="filterQ">Planteur, N° fiche, téléphone ou N° champ</label>
                    <input type="text" class="form-control" id="filterQ" placeholder="Rechercher...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="filterRegion">Région</label>
                    <select class="form-select" id="filterRegion">
                        <option value="">Toutes les régions</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="filterVillage">Sous-préf. / village</label>
                    <input type="text" class="form-control" id="filterVillage" placeholder="Village...">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
                    <button type="button" class="btn btn-outline-secondary flex-fill" id="resetFilters" title="Réinitialiser">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="plantationsError" class="alert alert-danger d-none" role="alert"></div>
    <div id="plantationsSuccess" class="alert alert-success d-none" role="status"></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Plantations et planteurs associés</span>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small" id="paginationInfoTop"></span>
                <span class="text-muted small" id="loadedAt"></span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshPlantations" title="Relire les données du serveur (nouvelles synchronisations)">
                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="loader" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted mt-3">Chargement des plantations...</div>
            </div>

            <div class="table-responsive d-none" id="tableWrapper">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="plantations-list-header">
                        <tr>
                            <th>N° champ</th>
                            <th>Région</th>
                            <th>Sous-préfecture</th>
                            <th>Village</th>
                            <th>Culture</th>
                            <th class="text-end">Superficie (ha)</th>
                            <th>Parcelle</th>
                            <th>Planteur</th>
                            <th>Téléphone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="plantationsTbody"></tbody>
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

    <div class="modal fade" id="plantationDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Supprimer la plantation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="plantationDeleteError" class="alert alert-danger d-none"></div>
                    <p class="mb-2">Voulez-vous vraiment supprimer la plantation <code id="plantationDeleteCode" class="text-danger"></code> ?</p>
                    <ul class="list-unstyled small mb-2">
                        <li><span class="text-muted">Planteur :</span> <span id="plantationDeletePlanteur"></span></li>
                        <li><span class="text-muted">Champ :</span> <span id="plantationDeleteDetail"></span></li>
                    </ul>
                    <p class="text-muted small mb-0">La culture et sa parcelle tracée seront supprimées définitivement. Le planteur et ses autres plantations sont conservés.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="plantationDeleteConfirm">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiUrl = @json(route('plantations.api'));
            const baseUrl = @json(url('/plantations'));
            const csrfToken = @json(csrf_token());
            const limit = 15;
            let currentPage = 1;
            let regionsLoaded = false;
            let rowsByChampId = {};
            let plantationToDelete = null;
            const deleteModal = new bootstrap.Modal(document.getElementById('plantationDeleteModal'));
            const deleteErrorEl = document.getElementById('plantationDeleteError');
            const successEl = document.getElementById('plantationsSuccess');

            const loaderEl = document.getElementById('loader');
            const errorEl = document.getElementById('plantationsError');
            const tableWrapper = document.getElementById('tableWrapper');
            const tbodyEl = document.getElementById('plantationsTbody');
            const paginationContainer = document.getElementById('paginationContainer');
            const paginationNav = document.getElementById('paginationNav');

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;');
            }

            function fmtHa(value) {
                const number = Number(value);
                if (!Number.isFinite(number) || number <= 0) return '—';
                return number.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function render(rows) {
                rowsByChampId = {};
                rows.forEach(function (row) {
                    if (row.champ_id) rowsByChampId[row.champ_id] = row;
                });
                if (!rows.length) {
                    tbodyEl.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">Aucune plantation trouvée.</td></tr>';
                    return;
                }
                tbodyEl.innerHTML = rows.map(function (row) {
                    const planteurUrl = `${baseUrl}/${encodeURIComponent(row.planteur_id)}`;
                    const champUrl = row.champ_id
                        ? `${baseUrl}/${encodeURIComponent(row.planteur_id)}/champs/${encodeURIComponent(row.champ_id)}`
                        : `${planteurUrl}/champs`;
                    const superficie = row.superficie_ha > 0 ? row.superficie_ha : row.superficie_gps;
                    const parcelle = row.parcelle_tracee
                        ? `<span class="badge bg-success">Tracée</span>${row.superficie_gps > 0 ? ` <small class="text-muted">${fmtHa(row.superficie_gps)} ha GPS</small>` : ''}`
                        : '<span class="badge bg-secondary">Non tracée</span>';
                    return `
                        <tr>
                            <td><a href="${champUrl}" class="text-decoration-none" title="Détail du champ"><code class="text-danger">${escapeHtml(row.numero_champ)}</code></a></td>
                            <td>${escapeHtml(row.region || '—')}</td>
                            <td>${escapeHtml(row.sous_prefecture || '—')}</td>
                            <td>${escapeHtml(row.village || '—')}</td>
                            <td>${escapeHtml(row.type_culture || '—')}</td>
                            <td class="text-end fw-semibold">${fmtHa(superficie)}</td>
                            <td class="text-nowrap">${parcelle}</td>
                            <td>
                                <a href="${planteurUrl}" class="fw-semibold text-decoration-none">${escapeHtml(row.planteur_nom || '—')}</a>
                                <div class="small text-muted">${escapeHtml(row.numero_fiche || '')}</div>
                            </td>
                            <td>${escapeHtml(row.telephone || '—')}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="${champUrl}" title="Détail du champ"><i class="bi bi-eye"></i></a>
                                <a class="btn btn-sm btn-outline-secondary ms-1" href="${planteurUrl}" title="Fiche du planteur"><i class="bi bi-person"></i></a>
                                ${row.champ_id ? `<button type="button" class="btn btn-sm btn-outline-danger ms-1 btn-delete-plantation" data-champ-id="${escapeHtml(row.champ_id)}" title="Supprimer la plantation"><i class="bi bi-trash"></i></button>` : ''}
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function renderStats(data) {
                document.getElementById('statPlantations').textContent = (data.total || 0).toLocaleString('fr-FR');
                document.getElementById('statPlanteurs').textContent = `${(data.nb_planteurs || 0).toLocaleString('fr-FR')} planteur(s)`;
                document.getElementById('statSuperficie').textContent = `${fmtHa(data.superficie_totale)} ha`;
                document.getElementById('statTracees').textContent =
                    `${(data.parcelles_tracees || 0).toLocaleString('fr-FR')} / ${(data.total || 0).toLocaleString('fr-FR')}`;
            }

            function renderRegions(regions) {
                if (regionsLoaded) return;
                regionsLoaded = true;
                const select = document.getElementById('filterRegion');
                (regions || []).forEach(function (region) {
                    const option = document.createElement('option');
                    option.value = region;
                    option.textContent = region;
                    select.appendChild(option);
                });
            }

            function renderPagination(data) {
                const total = data.total || 0;
                const totalPages = data.total_pages || 1;
                currentPage = data.page || 1;
                if (!total) {
                    paginationContainer.classList.add('d-none');
                    paginationContainer.classList.remove('d-flex');
                    document.getElementById('paginationInfoTop').textContent = '';
                    return;
                }
                paginationContainer.classList.remove('d-none');
                paginationContainer.classList.add('d-flex');

                const start = (currentPage - 1) * limit + 1;
                const end = Math.min(currentPage * limit, total);
                const info = `Affichage ${start} - ${end} sur ${total} plantations`;
                document.getElementById('paginationInfo').textContent = info;
                document.getElementById('paginationInfoTop').textContent = info;

                let startPage = Math.max(1, currentPage - 2);
                const endPage = Math.min(totalPages, startPage + 4);
                if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

                let html = `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">Précédent</a></li>`;
                for (let page = startPage; page <= endPage; page++) {
                    html += `<li class="page-item ${page === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${page}">${page}</a></li>`;
                }
                html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">Suivant</a></li>`;
                paginationNav.innerHTML = html;
            }

            const refreshBtn = document.getElementById('refreshPlantations');
            const loadedAtEl = document.getElementById('loadedAt');
            let lastPage = 1;

            async function load(page, refresh = false) {
                lastPage = page;
                loaderEl.classList.remove('d-none');
                errorEl.classList.add('d-none');
                refreshBtn.disabled = true;
                const params = new URLSearchParams({
                    action: 'plantations',
                    page: page,
                    limit: limit,
                    q: document.getElementById('filterQ').value.trim(),
                    region: document.getElementById('filterRegion').value,
                    village: document.getElementById('filterVillage').value.trim(),
                });
                if (refresh) params.set('refresh', '1');
                try {
                    const res = await fetch(`${apiUrl}?${params}`, { cache: 'no-store' });
                    const json = await res.json();
                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Impossible de charger les plantations.');
                    }
                    const data = json.data || {};
                    renderRegions(data.regions);
                    renderStats(data);
                    render(data.plantations || []);
                    renderPagination(data);
                    loadedAtEl.textContent = data.loaded_at
                        ? `· données du ${new Date(data.loaded_at).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'medium' })}`
                        : '';
                    tableWrapper.classList.remove('d-none');
                } catch (error) {
                    errorEl.textContent = error.message || String(error);
                    errorEl.classList.remove('d-none');
                } finally {
                    loaderEl.classList.add('d-none');
                    refreshBtn.disabled = false;
                }
            }

            refreshBtn.addEventListener('click', function () { load(lastPage, true); });

            tbodyEl.addEventListener('click', function (event) {
                const button = event.target.closest('.btn-delete-plantation');
                if (!button) return;
                const row = rowsByChampId[button.dataset.champId];
                if (!row) return;
                plantationToDelete = row;
                deleteErrorEl.classList.add('d-none');
                document.getElementById('plantationDeleteCode').textContent = row.numero_champ || '';
                document.getElementById('plantationDeletePlanteur').textContent =
                    [row.planteur_nom, row.numero_fiche].filter(Boolean).join(' · ') || '—';
                document.getElementById('plantationDeleteDetail').textContent = [
                    row.type_culture,
                    row.superficie_ha > 0 ? `${fmtHa(row.superficie_ha)} ha` : null,
                    [row.village, row.sous_prefecture].filter(Boolean).join(', ') || null,
                ].filter(Boolean).join(' · ') || '—';
                deleteModal.show();
            });

            document.getElementById('plantationDeleteConfirm').addEventListener('click', async function () {
                if (!plantationToDelete) return;
                const button = this;
                const label = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Suppression...';
                try {
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            action: 'delete_champ',
                            champ_id: plantationToDelete.champ_id,
                            planteur_id: plantationToDelete.planteur_id,
                        }),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Suppression impossible.');
                    }
                    deleteModal.hide();
                    successEl.textContent = `Plantation ${plantationToDelete.numero_champ} supprimée.`;
                    successEl.classList.remove('d-none');
                    plantationToDelete = null;
                    await load(lastPage);
                } catch (error) {
                    deleteErrorEl.textContent = error.message || String(error);
                    deleteErrorEl.classList.remove('d-none');
                } finally {
                    button.disabled = false;
                    button.innerHTML = label;
                }
            });

            paginationNav.addEventListener('click', function (event) {
                event.preventDefault();
                const target = event.target.closest('[data-page]');
                if (!target || target.parentElement.classList.contains('disabled')) return;
                load(parseInt(target.dataset.page, 10));
            });

            document.getElementById('filtersForm').addEventListener('submit', function (event) {
                event.preventDefault();
                load(1);
            });
            document.getElementById('filterRegion').addEventListener('change', function () { load(1); });
            document.getElementById('resetFilters').addEventListener('click', function () {
                document.getElementById('filtersForm').reset();
                load(1);
            });

            const initialQuery = new URLSearchParams(window.location.search).get('q');
            if (initialQuery) {
                document.getElementById('filterQ').value = initialQuery;
            }

            load(1);
        });
    </script>
@endpush
