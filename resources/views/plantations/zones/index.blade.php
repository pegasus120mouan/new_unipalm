@extends('layout.main')

@section('title', 'Liste des zones')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Gestion des zones</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Zones</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <div id="zonesError" class="alert alert-danger d-none" role="alert"></div>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-layers"></i> Zones de collecte</span>
                    <button type="button" class="btn btn-light btn-sm" id="openAddZoneBtn">
                        <i class="bi bi-plus-circle"></i> Nouvelle zone
                    </button>
                </div>
                <div class="card-body">
                    <div id="loader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-3">Chargement des zones...</div>
                    </div>
                    <div class="table-responsive d-none" id="tableWrapper">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="zones-table-header">
                                <tr>
                                    <th>#</th>
                                    <th>Nom de la zone</th>
                                    <th>Collecteurs assignés</th>
                                    <th>Date de création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="zonesTableBody"></tbody>
                        </table>
                    </div>
                    <div id="zonesEmpty" class="text-center py-5 d-none">
                        <i class="bi bi-geo-alt fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">Aucune zone enregistrée</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="zoneModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="zoneModalTitle"><i class="bi bi-plus-circle me-2"></i>Nouvelle zone</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <form id="zoneForm">
                        <input type="hidden" id="zoneId" name="id">
                        <label for="nomZone" class="form-label">Nom de la zone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nomZone" name="nom_zone" required placeholder="Ex: Zone Nord">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="saveZoneBtn"><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteZoneModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirmer la suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p>Supprimer la zone <strong id="deleteZoneName"></strong> ?</p>
                    <p class="text-muted small mb-0">Les collecteurs assignés seront désassignés.</p>
                    <input type="hidden" id="deleteZoneId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteZoneBtn"><i class="bi bi-trash"></i> Supprimer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .zones-table-header { background: #111; }
        .zones-table-header th {
            color: #fff !important;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: none;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiUrl = @json(route('plantations.zones.api'));
            const csrfToken = @json(csrf_token());

            let zones = [];

            const errorEl = document.getElementById('zonesError');
            const loaderEl = document.getElementById('loader');
            const tableWrapper = document.getElementById('tableWrapper');
            const zonesEmpty = document.getElementById('zonesEmpty');
            const tbody = document.getElementById('zonesTableBody');

            function escapeHtml(v) {
                return String(v ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
            }

            function formatDate(dateStr) {
                if (!dateStr) return '-';
                return new Date(dateStr).toLocaleDateString('fr-FR');
            }

            function showError(message) {
                errorEl.textContent = message;
                errorEl.classList.remove('d-none');
            }

            function renderZonesTable(list) {
                tbody.innerHTML = list.map((z, i) => `
                    <tr>
                        <td>${i + 1}</td>
                        <td class="fw-semibold">${escapeHtml(z.nom_zone)}</td>
                        <td><span class="badge bg-info text-dark">${z.collecteurs_count || 0} collecteur(s)</span></td>
                        <td>${formatDate(z.created_at)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary edit-zone-btn" data-id="${z.id}" data-nom="${escapeHtml(z.nom_zone)}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-zone-btn" data-id="${z.id}" data-nom="${escapeHtml(z.nom_zone)}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            async function loadZones() {
                loaderEl.classList.remove('d-none');
                tableWrapper.classList.add('d-none');
                zonesEmpty.classList.add('d-none');
                errorEl.classList.add('d-none');

                try {
                    const res = await fetch(`${apiUrl}?action=list`, { cache: 'no-store' });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    zones = json.data || [];
                    if (zones.length) {
                        renderZonesTable(zones);
                        tableWrapper.classList.remove('d-none');
                    } else {
                        zonesEmpty.classList.remove('d-none');
                    }
                } catch (e) {
                    showError(e.message);
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

            function openAddZoneModal() {
                document.getElementById('zoneModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Nouvelle zone';
                document.getElementById('zoneId').value = '';
                document.getElementById('nomZone').value = '';
                new bootstrap.Modal(document.getElementById('zoneModal')).show();
            }

            function openEditZoneModal(id, nom) {
                document.getElementById('zoneModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Modifier la zone';
                document.getElementById('zoneId').value = id;
                document.getElementById('nomZone').value = nom;
                new bootstrap.Modal(document.getElementById('zoneModal')).show();
            }

            document.getElementById('openAddZoneBtn').addEventListener('click', openAddZoneModal);

            document.getElementById('saveZoneBtn').addEventListener('click', async function () {
                const id = document.getElementById('zoneId').value;
                const nom = document.getElementById('nomZone').value.trim();
                if (!nom) {
                    alert('Veuillez saisir un nom de zone');
                    return;
                }
                try {
                    const json = await postJson({
                        action: id ? 'update' : 'create',
                        id: id || undefined,
                        nom_zone: nom,
                    });
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    bootstrap.Modal.getInstance(document.getElementById('zoneModal')).hide();
                    loadZones();
                } catch (e) {
                    alert('Erreur : ' + e.message);
                }
            });

            tbody.addEventListener('click', function (e) {
                const editBtn = e.target.closest('.edit-zone-btn');
                if (editBtn) {
                    openEditZoneModal(editBtn.dataset.id, editBtn.dataset.nom);
                    return;
                }
                const deleteBtn = e.target.closest('.delete-zone-btn');
                if (deleteBtn) {
                    document.getElementById('deleteZoneId').value = deleteBtn.dataset.id;
                    document.getElementById('deleteZoneName').textContent = deleteBtn.dataset.nom;
                    new bootstrap.Modal(document.getElementById('deleteZoneModal')).show();
                }
            });

            document.getElementById('confirmDeleteZoneBtn').addEventListener('click', async function () {
                const id = document.getElementById('deleteZoneId').value;
                try {
                    const json = await postJson({ action: 'delete', id });
                    if (!json.success) throw new Error(json.error || 'Erreur');
                    bootstrap.Modal.getInstance(document.getElementById('deleteZoneModal')).hide();
                    loadZones();
                } catch (e) {
                    alert('Erreur : ' + e.message);
                }
            });

            loadZones();
        });
    </script>
@endpush
