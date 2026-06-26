@extends('layout.main')

@section('title', 'Liste des régions')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Gestion des régions</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Régions</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <div id="regionsError" class="alert alert-danger d-none" role="alert"></div>

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold" id="totalRegions">0</div>
                    <div class="small opacity-75">Régions</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold" id="totalSousPrefectures">0</div>
                    <div class="small opacity-75">Sous-préfectures</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold" id="avgSousPrefectures">0</div>
                    <div class="small opacity-75">Moyenne SP / région</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="searchInput" class="form-label">Rechercher une région</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="Nom de la région...">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="refreshBtn" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Actualiser
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">Liste des régions</div>
                <div class="card-body">
                    <div id="loader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-3">Chargement des régions...</div>
                    </div>
                    <div class="table-responsive d-none" id="tableWrapper">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="regions-table-header">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom de la région</th>
                                    <th>Sous-préfectures</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="regionsTbody"></tbody>
                        </table>
                    </div>
                    <div id="noResults" class="text-center py-5 d-none">
                        <i class="bi bi-map fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">Aucune région trouvée</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="regionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-geo-alt me-2"></i><span id="modalRegionName">-</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalRegionId" value="">
                    <label class="form-label fw-semibold">Ajouter une sous-préfecture</label>
                    <div class="input-group mb-3">
                        <input type="text" id="newSpName" class="form-control" placeholder="Nom de la sous-préfecture...">
                        <button class="btn btn-success" type="button" id="addSpBtn"><i class="bi bi-plus"></i> Ajouter</button>
                    </div>
                    <div id="addSpError" class="text-danger small d-none"></div>
                    <div id="addSpSuccess" class="text-success small d-none"></div>
                    <hr>
                    <p class="mb-2"><strong>Sous-préfectures :</strong> <span id="modalSpCount" class="badge bg-primary">0</span></p>
                    <div id="modalLoader" class="text-center py-3 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="modalSpList" class="d-flex flex-wrap gap-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .regions-table-header { background: #111; }
        .regions-table-header th {
            color: #fff !important;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: none;
        }
        .sp-item {
            background: #435ebe;
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-delete-sp {
            background: rgba(255,255,255,0.2);
            border: none;
            color: #fff;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 0.7rem;
            line-height: 1;
        }
        .btn-delete-sp:hover { background: #dc3545; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiUrl = @json(route('plantations.regions.api'));
            const csrfToken = @json(csrf_token());

            let allRegions = [];

            const errorEl = document.getElementById('regionsError');
            const loaderEl = document.getElementById('loader');
            const tableWrapper = document.getElementById('tableWrapper');
            const tbodyEl = document.getElementById('regionsTbody');
            const noResultsEl = document.getElementById('noResults');

            function escapeHtml(v) {
                return String(v ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
            }

            function showError(message) {
                errorEl.textContent = message;
                errorEl.classList.remove('d-none');
            }

            function updateStats() {
                const totalRegions = allRegions.length;
                let totalSp = 0;
                allRegions.forEach(r => { totalSp += r.sous_prefectures_count || 0; });
                const avg = totalRegions > 0 ? (totalSp / totalRegions).toFixed(1) : 0;
                document.getElementById('totalRegions').textContent = totalRegions;
                document.getElementById('totalSousPrefectures').textContent = totalSp;
                document.getElementById('avgSousPrefectures').textContent = avg;
            }

            function render(regions) {
                if (!regions.length) {
                    tableWrapper.classList.add('d-none');
                    noResultsEl.classList.remove('d-none');
                    return;
                }
                noResultsEl.classList.add('d-none');
                tableWrapper.classList.remove('d-none');
                tbodyEl.innerHTML = regions.map(r => `
                    <tr>
                        <td><span class="badge bg-primary">${escapeHtml(r.id)}</span></td>
                        <td class="fw-semibold">${escapeHtml(r.nom)}</td>
                        <td><span class="badge bg-info text-dark">${r.sous_prefectures_count || 0} sous-préfecture(s)</span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary view-btn" data-id="${escapeHtml(r.id)}">
                                <i class="bi bi-eye"></i> Voir
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            function filterRegions() {
                const search = document.getElementById('searchInput').value.toLowerCase().trim();
                const filtered = allRegions.filter(r => !search || (r.nom || '').toLowerCase().includes(search));
                render(filtered);
            }

            async function loadRegions() {
                loaderEl.classList.remove('d-none');
                tableWrapper.classList.add('d-none');
                noResultsEl.classList.add('d-none');
                errorEl.classList.add('d-none');

                try {
                    const res = await fetch(`${apiUrl}?action=list`, { cache: 'no-store' });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    allRegions = json.data || [];
                    updateStats();
                    render(allRegions);
                } catch (e) {
                    showError(e.message);
                } finally {
                    loaderEl.classList.add('d-none');
                }
            }

            function renderSousPrefectures(sousPrefectures) {
                const spListEl = document.getElementById('modalSpList');
                if (sousPrefectures.length) {
                    spListEl.innerHTML = sousPrefectures.map(sp => `
                        <span class="sp-item">
                            ${escapeHtml(sp.nom)}
                            <button type="button" class="btn-delete-sp" data-id="${escapeHtml(sp.id)}" title="Supprimer">&times;</button>
                        </span>
                    `).join('');
                } else {
                    spListEl.innerHTML = '<span class="text-muted fst-italic">Aucune sous-préfecture enregistrée</span>';
                }
                document.getElementById('modalSpCount').textContent = sousPrefectures.length;
            }

            async function showRegionModal(regionId) {
                document.getElementById('modalRegionName').textContent = 'Chargement...';
                document.getElementById('modalRegionId').value = regionId;
                document.getElementById('modalSpList').innerHTML = '';
                document.getElementById('newSpName').value = '';
                document.getElementById('addSpError').classList.add('d-none');
                document.getElementById('addSpSuccess').classList.add('d-none');
                document.getElementById('modalLoader').classList.remove('d-none');
                new bootstrap.Modal(document.getElementById('regionModal')).show();

                try {
                    const res = await fetch(`${apiUrl}?action=get&id=${regionId}`, { cache: 'no-store' });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    document.getElementById('modalRegionName').textContent = json.data.nom;
                    renderSousPrefectures(json.data.sous_prefectures || []);
                } catch (e) {
                    document.getElementById('modalSpList').innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
                } finally {
                    document.getElementById('modalLoader').classList.add('d-none');
                }
            }

            async function postJson(data) {
                const res = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });
                return res.json();
            }

            async function addSousPrefecture() {
                const regionId = document.getElementById('modalRegionId').value;
                const nom = document.getElementById('newSpName').value.trim();
                const addErrorEl = document.getElementById('addSpError');
                const addSuccessEl = document.getElementById('addSpSuccess');
                addErrorEl.classList.add('d-none');
                addSuccessEl.classList.add('d-none');

                if (!nom) {
                    addErrorEl.textContent = 'Veuillez entrer un nom';
                    addErrorEl.classList.remove('d-none');
                    return;
                }

                try {
                    const json = await postJson({ action: 'create_sp', region_id: regionId, nom });
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    addSuccessEl.textContent = 'Sous-préfecture ajoutée avec succès !';
                    addSuccessEl.classList.remove('d-none');
                    document.getElementById('newSpName').value = '';
                    showRegionModal(regionId);
                    loadRegions();
                } catch (e) {
                    addErrorEl.textContent = e.message;
                    addErrorEl.classList.remove('d-none');
                }
            }

            async function deleteSousPrefecture(spId) {
                if (!confirm('Supprimer cette sous-préfecture ?')) return;
                const regionId = document.getElementById('modalRegionId').value;
                try {
                    const json = await postJson({ action: 'delete_sp', id: spId });
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    showRegionModal(regionId);
                    loadRegions();
                } catch (e) {
                    alert('Erreur : ' + e.message);
                }
            }

            document.getElementById('searchInput').addEventListener('input', filterRegions);
            document.getElementById('refreshBtn').addEventListener('click', loadRegions);
            document.getElementById('addSpBtn').addEventListener('click', addSousPrefecture);
            document.getElementById('newSpName').addEventListener('keypress', e => { if (e.key === 'Enter') addSousPrefecture(); });

            document.getElementById('modalSpList').addEventListener('click', e => {
                const btn = e.target.closest('.btn-delete-sp');
                if (btn) deleteSousPrefecture(btn.dataset.id);
            });

            tbodyEl.addEventListener('click', e => {
                const btn = e.target.closest('.view-btn');
                if (btn) showRegionModal(btn.dataset.id);
            });

            loadRegions();
        });
    </script>
@endpush
