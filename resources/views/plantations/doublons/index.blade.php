@extends('layout.main')

@section('title', 'Gestion des doublons')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Gestion des planteurs en double</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('plantations.index') }}">Plantations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Doublons</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <style>
        .doublon-group {
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            overflow: hidden;
            border-left: 4px solid #eb3349;
        }
        .doublon-group-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #fff;
            padding: 0.85rem 1.15rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .doublon-group-header h6 { margin: 0; font-weight: 600; }
        .badge-original {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: #fff;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-doublon {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: #fff;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: #fff;
            margin: 0 2px;
        }
        .btn-action.view { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .btn-action.keep { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); opacity: 0.7; }
        .btn-action.delete { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .empty-state { text-align: center; padding: 3rem 1rem; }
        .empty-state i { font-size: 4rem; color: #27ae60; margin-bottom: 1rem; }
    </style>

    <div id="errorAlert" class="alert alert-danger d-none" role="alert"></div>

    <div id="loader" class="text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <div class="text-muted mt-3">Chargement des doublons...</div>
    </div>

    <section class="row mb-4 d-none" id="statsContainer">
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold" id="statTotalDoublons">0</div>
                    <div class="small opacity-75">Planteurs en double</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold" id="statTotalGroupes">0</div>
                    <div class="small opacity-75">Groupes de doublons</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold" id="statASupprimer">0</div>
                    <div class="small opacity-75">À supprimer (estimation)</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row d-none" id="tableContainer">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-list-ul me-1"></i> Liste des doublons détectés</span>
                    <span class="badge bg-secondary" id="badgeGroupes">0 groupe(s)</span>
                </div>
                <div class="card-body" id="doublonsContent"></div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirmation de suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-trash text-danger" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5>Supprimer ce planteur ?</h5>
                    <p class="text-muted mb-0">
                        Êtes-vous sûr de vouloir supprimer <strong id="deleteName" class="text-danger"></strong> ?
                        <br><small>Cette action est irréversible.</small>
                    </p>
                    <input type="hidden" id="deleteId" value="">
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash me-1"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const apiUrl = @json(route('plantations.doublons.api'));
    const showUrlBase = @json(url('/plantations'));
    const csrfToken = @json(csrf_token());

    const loader = document.getElementById('loader');
    const errorAlert = document.getElementById('errorAlert');
    const statsContainer = document.getElementById('statsContainer');
    const tableContainer = document.getElementById('tableContainer');
    const doublonsContent = document.getElementById('doublonsContent');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    function escapeHtml(text) {
        if (text === null || text === undefined || text === '') return '-';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('fr-FR');
    }

    function showError(msg) {
        loader.classList.add('d-none');
        errorAlert.textContent = msg;
        errorAlert.classList.remove('d-none');
    }

    function renderGroup(groupe, index) {
        const first = groupe[0] || {};
        const nom = escapeHtml(first.nom_prenoms);
        const tel = first.telephone ? `(${escapeHtml(first.telephone)})` : '';

        let rowsHtml = '';
        groupe.forEach((p, i) => {
            const collecteur = p.collecteur
                ? `${p.collecteur.nom || ''} ${p.collecteur.prenoms || ''}`.trim() || '-'
                : '-';
            const region = p.exploitation?.region || '-';
            const sousPref = p.exploitation?.sous_prefecture_village || '-';
            const village = p.exploitation?.village || '-';
            const statusBadge = i === 0
                ? '<span class="badge-original"><i class="bi bi-star-fill me-1"></i>Original</span>'
                : '<span class="badge-doublon"><i class="bi bi-files me-1"></i>Doublon</span>';
            const actionBtn = i === 0
                ? '<button type="button" class="btn-action keep" title="Conserver" disabled><i class="bi bi-check-lg"></i></button>'
                : `<button type="button" class="btn-action delete" title="Supprimer ce doublon" data-id="${escapeHtml(p.id)}" data-name="${escapeHtml(p.nom_prenoms)}"><i class="bi bi-trash"></i></button>`;

            rowsHtml += `
                <tr>
                    <td>${statusBadge}</td>
                    <td><strong>${escapeHtml(p.numero_fiche)}</strong></td>
                    <td>${escapeHtml(p.nom_prenoms)}</td>
                    <td>${escapeHtml(p.telephone)}</td>
                    <td>${escapeHtml(region)}</td>
                    <td>${escapeHtml(sousPref)}</td>
                    <td>${escapeHtml(village)}</td>
                    <td>${escapeHtml(collecteur)}</td>
                    <td>${formatDate(p.created_at)}</td>
                    <td class="text-nowrap">
                        <a href="${showUrlBase}/${encodeURIComponent(p.id)}" class="btn-action view" title="Voir les détails">
                            <i class="bi bi-eye"></i>
                        </a>
                        ${actionBtn}
                    </td>
                </tr>
            `;
        });

        return `
            <div class="doublon-group">
                <div class="doublon-group-header">
                    <h6>
                        <i class="bi bi-people me-2"></i>
                        Groupe #${index + 1} — ${nom}
                        <small class="ms-2 opacity-75">${tel}</small>
                    </h6>
                    <span class="badge bg-danger">${groupe.length} entrée(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Statut</th>
                                <th>N° Fiche</th>
                                <th>Nom &amp; Prénoms</th>
                                <th>Téléphone</th>
                                <th>Région</th>
                                <th>Sous-préfecture</th>
                                <th>Village</th>
                                <th>Collecteur</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </div>
            </div>
        `;
    }

    async function loadDoublons() {
        errorAlert.classList.add('d-none');
        loader.classList.remove('d-none');
        statsContainer.classList.add('d-none');
        tableContainer.classList.add('d-none');

        try {
            const response = await fetch(apiUrl, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || result.message || 'Erreur lors du chargement des doublons');
            }

            const data = result.data || {};
            document.getElementById('statTotalDoublons').textContent = data.total_doublons || 0;
            document.getElementById('statTotalGroupes').textContent = data.total_groupes || 0;
            document.getElementById('statASupprimer').textContent = data.a_supprimer || 0;
            document.getElementById('badgeGroupes').textContent = `${data.total_groupes || 0} groupe(s)`;

            if (!data.groupes || data.groupes.length === 0) {
                doublonsContent.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <h4>Aucun doublon détecté</h4>
                        <p class="text-muted mb-0">Tous les planteurs sont uniques dans la base de données.</p>
                    </div>
                `;
            } else {
                doublonsContent.innerHTML = data.groupes.map((groupe, index) => renderGroup(groupe, index)).join('');
            }

            loader.classList.add('d-none');
            statsContainer.classList.remove('d-none');
            tableContainer.classList.remove('d-none');
        } catch (error) {
            showError(error.message || 'Erreur de connexion');
        }
    }

    doublonsContent.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-action.delete');
        if (!btn) return;
        document.getElementById('deleteId').value = btn.dataset.id || '';
        document.getElementById('deleteName').textContent = btn.dataset.name || '';
        deleteModal.show();
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
        const id = document.getElementById('deleteId').value;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Suppression...';

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ action: 'delete_planteur', id: id }),
            });
            const result = await response.json();

            if (result.success) {
                deleteModal.hide();
                await loadDoublons();
            } else {
                alert('Erreur: ' + (result.error || result.message || 'Impossible de supprimer le planteur'));
            }
        } catch (error) {
            alert('Erreur de connexion: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash me-1"></i> Supprimer';
        }
    });

    loadDoublons();
})();
</script>
@endpush
