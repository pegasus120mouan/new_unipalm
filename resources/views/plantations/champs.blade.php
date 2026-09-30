@extends('layout.main')

@section('title', 'Champs du planteur')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Champs du planteur</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('plantations.show', $planteurId) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye"></i> Fiche du planteur
            </a>
            <a href="{{ route('plantations.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .champs-table-header th {
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

    <div id="champsError" class="alert alert-danger d-none" role="alert"></div>

    <div id="champsLoader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-3">Chargement des champs...</div>
    </div>

    <div id="champsContent" class="d-none">
        <div class="card mb-4">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                <img id="planteurPhoto" src="" alt="Photo" class="rounded-circle border"
                    style="width:72px;height:72px;object-fit:cover;">
                <div>
                    <h4 id="planteurNom" class="mb-1"></h4>
                    <div class="text-muted small">
                        <span id="planteurFiche"></span>
                        <span id="planteurTel" class="ms-2"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Champs associés à cette fiche</span>
                <span class="badge bg-secondary" id="champsBadge">0 champ(s)</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="champs-table-header">
                        <tr>
                            <th>N° champ</th>
                            <th>Région</th>
                            <th>Sous-préfecture</th>
                            <th>Village</th>
                            <th>Superficie (ha)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="champsTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="champEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="champEditForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier le champ <code id="champEditCode" class="text-danger"></code></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="champEditError" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <label class="form-label" for="champTypeCulture">Type de culture <span class="text-danger">*</span></label>
                        <select class="form-select" id="champTypeCulture" required>
                            <option value="Palmier a huile">Palmier à huile</option>
                            <option value="Hevea">Hévéa</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="champAutreCultureGroup">
                        <label class="form-label" for="champAutreCulture">Précisez la culture</label>
                        <input type="text" class="form-control" id="champAutreCulture" maxlength="100">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="champSuperficie">Superficie (ha)</label>
                            <input type="number" class="form-control" id="champSuperficie" min="0" step="0.01">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="champAge">Âge (années)</label>
                            <input type="number" class="form-control" id="champAge" min="0" step="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="champMode">Mode</label>
                            <select class="form-select" id="champMode">
                                <option value="">—</option>
                                <option value="Pur">Pur</option>
                                <option value="Associe">Associé</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="champProduction">Production estimée (kg)</label>
                            <input type="number" class="form-control" id="champProduction" min="0" step="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="champEditSubmit">
                        <i class="bi bi-check-lg"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="champDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Supprimer le champ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="champDeleteError" class="alert alert-danger d-none"></div>
                    <p class="mb-1">Voulez-vous vraiment supprimer le champ <code id="champDeleteCode" class="text-danger"></code> ?</p>
                    <p class="text-muted small mb-0">La culture et sa parcelle tracée seront supprimées définitivement.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="champDeleteConfirm">
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
            const planteurId = @json($planteurId);
            const apiUrl = @json(route('plantations.api'));
            const csrfToken = @json(csrf_token());
            const editModal = new bootstrap.Modal(document.getElementById('champEditModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('champDeleteModal'));
            let champsById = {};
            let currentChamp = null;
            const champUrlTemplate = @json(url('/plantations'));
            const defaultPhoto = "data:image/svg+xml," + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#E9ECEF"/><circle cx="40" cy="32" r="14" fill="#ADB5BD"/><path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/></svg>');

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;');
            }

            function fmtNumber(value) {
                if (value === null || value === undefined || value === '') return '—';
                const number = Number(value);
                if (!Number.isFinite(number)) return escapeHtml(value);
                return number.toLocaleString('fr-FR', { maximumFractionDigits: 2 });
            }

            async function load() {
                try {
                    const res = await fetch(`${apiUrl}?action=champs&id=${encodeURIComponent(planteurId)}`, { cache: 'no-store' });
                    const json = await res.json();
                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Impossible de charger les champs.');
                    }

                    const planteur = json.data?.planteur || {};
                    const champs = Array.isArray(json.data?.champs) ? json.data.champs : [];
                    champsById = {};
                    champs.forEach(function (champ) { champsById[String(champ.id)] = champ; });
                    const photo = planteur.photo_url || planteur.photo || defaultPhoto;

                    document.getElementById('planteurPhoto').src = photo;
                    document.getElementById('planteurPhoto').onerror = function () {
                        this.onerror = null;
                        this.src = defaultPhoto;
                    };
                    document.getElementById('planteurNom').textContent = planteur.nom_prenoms || 'Planteur';
                    document.getElementById('planteurFiche').textContent = planteur.numero_fiche
                        ? 'Fiche ' + planteur.numero_fiche
                        : 'Fiche unique';
                    document.getElementById('planteurTel').textContent = planteur.telephone
                        ? '• Tél. ' + planteur.telephone
                        : '';
                    const nbExploitations = new Set(champs.map((c) => c.exploitation_numero).filter(Boolean)).size;
                    document.getElementById('champsBadge').textContent = champs.length + ' champ(s)'
                        + (nbExploitations > 1 ? ' • ' + nbExploitations + ' exploitations' : '');

                    document.getElementById('champsTbody').innerHTML = champs.length
                        ? champs.map(function (champ) {
                            const code = escapeHtml(champ.numero_champ || '—');
                            const champId = encodeURIComponent(champ.id || '');
                            const champLink = champ.id
                                ? `<a href="${champUrlTemplate}/${planteurId}/champs/${champId}" class="text-decoration-none" title="Voir le détail du champ"><code class="text-danger">${code}</code></a>`
                                : `<code class="text-danger">${code}</code>`;
                            return `
                                <tr>
                                    <td>${champLink}</td>
                                    <td>${escapeHtml(champ.region || '—')}</td>
                                    <td>${escapeHtml(champ.sous_prefecture || '—')}</td>
                                    <td>${escapeHtml(champ.village || '—')}</td>
                                    <td class="fw-semibold">${fmtNumber(champ.superficie_ha)}</td>
                                    <td class="text-end text-nowrap">
                                        ${champ.editable ? `
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${escapeHtml(champ.id)}" title="Modifier le champ">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-1" data-action="delete" data-id="${escapeHtml(champ.id)}" title="Supprimer le champ">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        ` : '<span class="text-muted small">—</span>'}
                                    </td>
                                </tr>
                            `;
                        }).join('')
                        : '<tr><td colspan="6" class="text-center text-muted py-4">Aucun champ associé à ce planteur.</td></tr>';

                    document.getElementById('champsLoader').classList.add('d-none');
                    document.getElementById('champsContent').classList.remove('d-none');
                } catch (error) {
                    document.getElementById('champsLoader').classList.add('d-none');
                    const errorEl = document.getElementById('champsError');
                    errorEl.textContent = error.message || String(error);
                    errorEl.classList.remove('d-none');
                }
            }

            async function postChamp(payload) {
                const res = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(Object.assign({ planteur_id: planteurId }, payload)),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json?.success) {
                    throw new Error(json?.error || json?.message || 'Opération impossible.');
                }
                return json;
            }

            function showAlert(el, message) {
                el.textContent = message;
                el.classList.toggle('d-none', !message);
            }

            function setLoading(button, loading) {
                button.disabled = loading;
                if (loading) {
                    button.dataset.label = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                } else if (button.dataset.label) {
                    button.innerHTML = button.dataset.label;
                }
            }

            const typeSelect = document.getElementById('champTypeCulture');
            const autreGroup = document.getElementById('champAutreCultureGroup');
            function toggleAutre() {
                autreGroup.classList.toggle('d-none', typeSelect.value !== 'Autre');
            }
            typeSelect.addEventListener('change', toggleAutre);

            function valueOrEmpty(value) {
                return value === null || value === undefined ? '' : value;
            }

            function openEdit(champ) {
                currentChamp = champ;
                showAlert(document.getElementById('champEditError'), '');
                document.getElementById('champEditCode').textContent = champ.numero_champ || '';
                const knownTypes = ['Palmier a huile', 'Hevea', 'Autre'];
                const type = knownTypes.includes(champ.type_culture_code)
                    ? champ.type_culture_code
                    : (champ.type_culture_code ? 'Autre' : 'Palmier a huile');
                typeSelect.value = type;
                document.getElementById('champAutreCulture').value = type === 'Autre'
                    ? (champ.autre_culture || (knownTypes.includes(champ.type_culture_code) ? '' : champ.type_culture_code || ''))
                    : '';
                toggleAutre();
                document.getElementById('champSuperficie').value = valueOrEmpty(champ.superficie_ha);
                document.getElementById('champAge').value = valueOrEmpty(champ.age_culture);
                document.getElementById('champMode').value = ['Pur', 'Associe'].includes(champ.mode_culture) ? champ.mode_culture : '';
                document.getElementById('champProduction').value = valueOrEmpty(champ.production_estimee_kg);
                editModal.show();
            }

            function openDelete(champ) {
                currentChamp = champ;
                showAlert(document.getElementById('champDeleteError'), '');
                document.getElementById('champDeleteCode').textContent = champ.numero_champ || '';
                deleteModal.show();
            }

            document.getElementById('champsTbody').addEventListener('click', function (e) {
                const button = e.target.closest('button[data-action]');
                if (!button) return;
                const champ = champsById[button.dataset.id];
                if (!champ) return;
                if (button.dataset.action === 'edit') openEdit(champ);
                if (button.dataset.action === 'delete') openDelete(champ);
            });

            document.getElementById('champEditForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                if (!currentChamp) return;
                const errorEl = document.getElementById('champEditError');
                const submit = document.getElementById('champEditSubmit');
                const type = typeSelect.value;
                const autre = document.getElementById('champAutreCulture').value.trim();
                if (type === 'Autre' && !autre) {
                    showAlert(errorEl, 'Précisez le nom de la culture.');
                    return;
                }
                showAlert(errorEl, '');
                setLoading(submit, true);
                try {
                    await postChamp({
                        action: 'update_champ',
                        champ_id: currentChamp.id,
                        type_culture: type,
                        autre_culture: type === 'Autre' ? autre : '',
                        superficie_ha: document.getElementById('champSuperficie').value,
                        age_culture: document.getElementById('champAge').value,
                        mode_culture: document.getElementById('champMode').value,
                        production_estimee_kg: document.getElementById('champProduction').value,
                    });
                    editModal.hide();
                    await load();
                } catch (error) {
                    showAlert(errorEl, error.message || String(error));
                } finally {
                    setLoading(submit, false);
                }
            });

            document.getElementById('champDeleteConfirm').addEventListener('click', async function () {
                if (!currentChamp) return;
                const button = this;
                setLoading(button, true);
                try {
                    await postChamp({ action: 'delete_champ', champ_id: currentChamp.id });
                    deleteModal.hide();
                    await load();
                } catch (error) {
                    showAlert(document.getElementById('champDeleteError'), error.message || String(error));
                } finally {
                    setLoading(button, false);
                }
            });

            load();
        });
    </script>
@endpush
