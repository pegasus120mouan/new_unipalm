@extends('layout.main')

@section('title', 'Liste des collecteurs')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Liste des collecteurs</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Collecteurs</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <div id="collecteursError" class="alert alert-danger d-none" role="alert"></div>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-funnel"></i> Filtres</span>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-person-plus"></i> Créer un collecteur
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="searchInput" class="form-label">Recherche</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="Nom, contact, login...">
                        </div>
                        <div class="col-md-3">
                            <label for="roleFilter" class="form-label">Rôle</label>
                            <select id="roleFilter" class="form-select">
                                <option value="">Tous les rôles</option>
                                <option value="collecteur">Collecteurs</option>
                                <option value="admin">Administrateurs</option>
                                <option value="superviseur">Superviseurs</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Statut</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="1">Actifs</option>
                                <option value="0">Inactifs</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
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
                <div class="card-header">Liste des utilisateurs</div>
                <div class="card-body">
                    <div id="loader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-3">Chargement des collecteurs...</div>
                    </div>
                    <div class="table-responsive d-none" id="tableWrapper">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="collecteurs-table-header">
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénoms</th>
                                    <th>Contact</th>
                                    <th>Rôle</th>
                                    <th>Zone</th>
                                    <th>Login</th>
                                    <th>Avatar</th>
                                    <th>Actions</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody id="collecteursTbody"></tbody>
                        </table>
                    </div>
                    <div id="noResults" class="text-center py-5 d-none">
                        <i class="bi bi-people fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">Aucun collecteur trouvé</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('plantations.collecteurs.partials.create-modal')
    @include('plantations.collecteurs.partials.edit-modal')
    @include('plantations.collecteurs.partials.photo-modal')
    @include('plantations.collecteurs.partials.delete-modal')
@endsection

@push('scripts')
    <style>
        .collecteurs-table-header { background: #111; }
        .collecteurs-table-header th {
            color: #fff !important;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            white-space: nowrap;
        }
        .avatar-cell {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #e9ecef;
            cursor: pointer;
        }
        .avatar-cell:hover { border-color: #435ebe; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiUrl = @json(route('plantations.collecteurs.api'));
            const photoUrl = @json(route('plantations.collecteurs.photo'));
            const csrfToken = @json(csrf_token());

            const defaultPhoto = "data:image/svg+xml," + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#E9ECEF"/><circle cx="40" cy="32" r="14" fill="#ADB5BD"/><path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/></svg>');

            let allUsers = [];
            let allZones = [];
            let deleteUserId = null;

            const errorEl = document.getElementById('collecteursError');
            const loaderEl = document.getElementById('loader');
            const tableWrapper = document.getElementById('tableWrapper');
            const tbodyEl = document.getElementById('collecteursTbody');
            const noResultsEl = document.getElementById('noResults');

            function escapeHtml(v) {
                return String(v ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
            }

            function showError(message) {
                errorEl.textContent = message;
                errorEl.classList.remove('d-none');
            }

            function hideError() {
                errorEl.classList.add('d-none');
            }

            function getRoleBadge(role) {
                const r = String(role || '').toLowerCase();
                const map = {
                    collecteur: 'bg-success',
                    admin: 'bg-danger',
                    directeur: 'bg-purple',
                    operateur: 'bg-primary',
                    caissiere: 'bg-info',
                };
                const cls = map[r] || 'bg-secondary';
                return `<span class="badge ${cls}">${escapeHtml(role || 'N/A')}</span>`;
            }

            function renderRow(user) {
                const avatarUrl = user.avatar_url || defaultPhoto;
                const statusClass = user.statut_compte ? 'bg-success' : 'bg-danger';
                const statusText = user.statut_compte ? 'Actif' : 'Inactif';
                const zoneName = user.zone_nom || user.nom_zone;
                const zoneBadge = zoneName
                    ? `<span class="badge bg-primary">${escapeHtml(zoneName)}</span>`
                    : `<span class="badge bg-secondary">Non assigné</span>`;

                return `
                    <tr>
                        <td class="fw-semibold">${escapeHtml(user.nom || '')}</td>
                        <td>${escapeHtml(user.prenoms || '')}</td>
                        <td>${escapeHtml(user.contact || '')}</td>
                        <td>${getRoleBadge(user.role)}</td>
                        <td>${zoneBadge}</td>
                        <td><span class="badge bg-primary">${escapeHtml((user.login || '').toUpperCase())}</span></td>
                        <td>
                            <img src="${escapeHtml(avatarUrl)}" alt="Avatar" class="avatar-cell"
                                 data-id="${escapeHtml(user.id)}"
                                 data-name="${escapeHtml((user.nom || '') + ' ' + (user.prenoms || ''))}"
                                 onerror="this.onerror=null;this.src='${defaultPhoto}';">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary edit-btn" data-id="${escapeHtml(user.id)}" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="${escapeHtml(user.id)}" title="Supprimer">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                        <td><span class="badge ${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            }

            function render(users) {
                if (!users.length) {
                    tableWrapper.classList.add('d-none');
                    noResultsEl.classList.remove('d-none');
                    return;
                }
                noResultsEl.classList.add('d-none');
                tableWrapper.classList.remove('d-none');
                tbodyEl.innerHTML = users.map(renderRow).join('');
            }

            function filterUsers() {
                const search = document.getElementById('searchInput').value.toLowerCase().trim();
                const role = document.getElementById('roleFilter').value.toLowerCase();
                const status = document.getElementById('statusFilter').value;

                const filtered = allUsers.filter(user => {
                    const name = (user.nom_complet || user.nom || '').toLowerCase();
                    const contact = (user.contact || '').toLowerCase();
                    const login = (user.login || '').toLowerCase();
                    const userRole = (user.role || '').toLowerCase();
                    const userStatus = user.statut_compte ? '1' : '0';

                    const matchSearch = !search || name.includes(search) || contact.includes(search) || login.includes(search);
                    const matchRole = !role || userRole === role;
                    const matchStatus = status === '' || userStatus === status;

                    return matchSearch && matchRole && matchStatus;
                });

                render(filtered);
            }

            async function loadZones() {
                try {
                    const res = await fetch(`${apiUrl}?action=zones`, { cache: 'no-store' });
                    const json = await res.json();
                    if (json.success && json.data) {
                        allZones = json.data;
                        populateZoneSelects();
                    }
                } catch (e) {
                    console.error('Erreur chargement zones:', e);
                }
            }

            function populateZoneSelects() {
                const options = '<option value="">-- Aucune zone --</option>' +
                    allZones.map(z => `<option value="${z.id}">${escapeHtml(z.nom_zone)}</option>`).join('');
                document.getElementById('userZone').innerHTML = options;
                document.getElementById('editUserZone').innerHTML = options;
            }

            async function loadCollecteurs() {
                loaderEl.classList.remove('d-none');
                tableWrapper.classList.add('d-none');
                noResultsEl.classList.add('d-none');
                hideError();

                try {
                    const res = await fetch(apiUrl, { cache: 'no-store' });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.error || 'Erreur API');
                    allUsers = json.data?.utilisateurs || [];
                    render(allUsers);
                } catch (e) {
                    showError('Erreur : ' + e.message);
                } finally {
                    loaderEl.classList.add('d-none');
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

            document.getElementById('searchInput').addEventListener('input', filterUsers);
            document.getElementById('roleFilter').addEventListener('change', filterUsers);
            document.getElementById('statusFilter').addEventListener('change', filterUsers);
            document.getElementById('refreshBtn').addEventListener('click', loadCollecteurs);

            document.getElementById('createUserForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const errorBox = document.getElementById('createUserError');
                const successBox = document.getElementById('createUserSuccess');
                errorBox.classList.add('d-none');
                successBox.classList.add('d-none');

                const password = document.getElementById('userPassword').value;
                const passwordConfirm = document.getElementById('userPasswordConfirm').value;
                if (password !== passwordConfirm) {
                    errorBox.textContent = 'Les mots de passe ne correspondent pas.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                delete data.password_confirm;

                try {
                    const json = await postJson(data);
                    if (!json.success) throw new Error(json.error || 'Erreur lors de la création');
                    successBox.textContent = 'Utilisateur créé avec succès !';
                    successBox.classList.remove('d-none');
                    this.reset();
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
                        successBox.classList.add('d-none');
                        loadCollecteurs();
                    }, 1200);
                } catch (err) {
                    errorBox.textContent = err.message;
                    errorBox.classList.remove('d-none');
                }
            });

            tbodyEl.addEventListener('click', function (e) {
                const editBtn = e.target.closest('.edit-btn');
                if (editBtn) {
                    const user = allUsers.find(u => String(u.id) === String(editBtn.dataset.id));
                    if (!user) return;
                    document.getElementById('editUserId').value = user.id;
                    document.getElementById('editUserNom').value = user.nom || '';
                    document.getElementById('editUserPrenoms').value = user.prenoms || '';
                    document.getElementById('editUserContact').value = user.contact || '';
                    document.getElementById('editUserLogin').value = user.login || '';
                    document.getElementById('editUserRole').value = user.role || '';
                    document.getElementById('editUserStatut').value = user.statut_compte ? '1' : '0';
                    document.getElementById('editUserZone').value = user.zone_id || '';
                    document.getElementById('editUserPassword').value = '';
                    document.getElementById('editUserPasswordConfirm').value = '';
                    document.getElementById('editUserError').classList.add('d-none');
                    document.getElementById('editUserSuccess').classList.add('d-none');
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                    return;
                }

                const deleteBtn = e.target.closest('.delete-btn');
                if (deleteBtn) {
                    deleteUserId = deleteBtn.dataset.id;
                    const user = allUsers.find(u => String(u.id) === String(deleteUserId));
                    document.getElementById('deleteUserName').textContent = user ? `${user.nom} ${user.prenoms}` : 'cet utilisateur';
                    new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
                    return;
                }

                const avatar = e.target.closest('.avatar-cell');
                if (avatar) {
                    document.getElementById('photoUserId').value = avatar.dataset.id;
                    document.getElementById('photoUserName').textContent = avatar.dataset.name;
                    document.getElementById('currentPhoto').src = avatar.src;
                    document.getElementById('photoPreviewContainer').classList.add('d-none');
                    document.getElementById('newPhoto').value = '';
                    document.getElementById('savePhotoBtn').disabled = true;
                    document.getElementById('photoError').classList.add('d-none');
                    document.getElementById('photoSuccess').classList.add('d-none');
                    new bootstrap.Modal(document.getElementById('changePhotoModal')).show();
                }
            });

            document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
                if (!deleteUserId) return;
                this.disabled = true;
                try {
                    const json = await postJson({ action: 'delete', id: deleteUserId });
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                    loadCollecteurs();
                } catch (err) {
                    showError(err.message);
                } finally {
                    this.disabled = false;
                    deleteUserId = null;
                }
            });

            document.getElementById('editUserForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const errorBox = document.getElementById('editUserError');
                const successBox = document.getElementById('editUserSuccess');
                errorBox.classList.add('d-none');
                successBox.classList.add('d-none');

                const password = document.getElementById('editUserPassword').value;
                const passwordConfirm = document.getElementById('editUserPasswordConfirm').value;
                if (password && password !== passwordConfirm) {
                    errorBox.textContent = 'Les mots de passe ne correspondent pas.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                delete data.password_confirm;
                data.action = 'update';
                if (!data.password) delete data.password;
                data.statut_compte = parseInt(data.statut_compte, 10);

                try {
                    const json = await postJson(data);
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    successBox.textContent = 'Utilisateur modifié avec succès !';
                    successBox.classList.remove('d-none');
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                        successBox.classList.add('d-none');
                        loadCollecteurs();
                    }, 1200);
                } catch (err) {
                    errorBox.textContent = err.message;
                    errorBox.classList.remove('d-none');
                }
            });

            document.getElementById('newPhoto').addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (ev) {
                    document.getElementById('photoPreview').src = ev.target.result;
                    document.getElementById('photoPreviewContainer').classList.remove('d-none');
                    document.getElementById('savePhotoBtn').disabled = false;
                };
                reader.readAsDataURL(file);
            });

            document.getElementById('changePhotoForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const errorBox = document.getElementById('photoError');
                const successBox = document.getElementById('photoSuccess');
                errorBox.classList.add('d-none');
                successBox.classList.add('d-none');

                const formData = new FormData(this);
                try {
                    const res = await fetch(photoUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: formData,
                    });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    successBox.textContent = 'Photo mise à jour avec succès !';
                    successBox.classList.remove('d-none');
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('changePhotoModal')).hide();
                        loadCollecteurs();
                    }, 1200);
                } catch (err) {
                    errorBox.textContent = err.message;
                    errorBox.classList.remove('d-none');
                }
            });

            loadZones();
            loadCollecteurs();
        });
    </script>
@endpush
