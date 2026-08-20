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
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="planteurs-io-toolbar d-flex flex-wrap align-items-center gap-3 gap-md-4">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="planteurs-io-label">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                                Importer
                            </span>
                            <button type="button" class="planteurs-io-btn planteurs-io-btn--csv" id="planteursImportCsvBtn"
                                title="Importer un fichier CSV" data-bs-toggle="modal" data-bs-target="#importPlanteursModal">
                                <i class="bi bi-filetype-csv"></i>
                                <span>CSV</span>
                            </button>
                        </div>

                        <div class="planteurs-io-separator d-none d-md-block"></div>

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="planteurs-io-label">
                                <i class="bi bi-download"></i>
                                Exporter les données
                            </span>
                            <button type="button" class="planteurs-io-btn planteurs-io-btn--pdf" id="planteursExportPdfBtn" title="Exporter en PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                                <span>PDF</span>
                            </button>
                            <button type="button" class="planteurs-io-btn planteurs-io-btn--excel" id="planteursExportExcelBtn" title="Exporter en Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                                <span>EXCEL</span>
                            </button>
                            <button type="button" class="planteurs-io-btn planteurs-io-btn--print" id="planteursPrintBtn" title="Imprimer la liste">
                                <i class="bi bi-printer"></i>
                                <span>IMPRIMER</span>
                            </button>
                        </div>
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

    <div class="modal fade" id="importPlanteursModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Importer des planteurs (CSV)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Colonnes attendues : <code>numero_fiche</code>, <code>nom_prenoms</code>, <code>telephone</code>,
                        <code>region</code>, <code>sous_prefecture_village</code>, <code>village</code>, <code>collecteur</code>.
                    </p>
                    <div class="mb-0">
                        <label for="importPlanteursFile" class="form-label">Fichier CSV</label>
                        <input type="file" id="importPlanteursFile" class="form-control" accept=".csv,text/csv">
                    </div>
                    <div id="importPlanteursFeedback" class="alert d-none mt-3 mb-0" role="alert"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirmImportPlanteursBtn">
                        <i class="bi bi-upload"></i> Importer
                    </button>
                </div>
            </div>
        </div>
    </div>

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

        .planteurs-io-label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #1e3a5f;
            font-weight: 700;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .planteurs-io-label i {
            color: #2f6fed;
            font-size: 1.15rem;
        }

        .planteurs-io-separator {
            width: 1px;
            height: 48px;
            background: #e9ecef;
        }

        .planteurs-io-btn {
            width: 72px;
            height: 72px;
            border: none;
            border-radius: 12px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .planteurs-io-btn i {
            font-size: 1.45rem;
            line-height: 1;
        }

        .planteurs-io-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .planteurs-io-btn:disabled {
            opacity: 0.65;
            transform: none;
            box-shadow: none;
        }

        .planteurs-io-btn--csv {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .planteurs-io-btn--pdf {
            background: #ffe4e6;
            color: #e11d48;
        }

        .planteurs-io-btn--excel {
            background: #dcfce7;
            color: #15803d;
        }

        .planteurs-io-btn--print {
            background: #dbeafe;
            color: #1d4ed8;
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

            function getFilteredRows() {
                const filterNom = (document.getElementById('filterNom').value || '').toLowerCase().trim();
                const filterNumeroFiche = (document.getElementById('filterNumeroFiche').value || '').toLowerCase().trim();
                const filterTel = (document.getElementById('filterTelephone').value || '').toLowerCase().trim();
                const filterCollecteur = (document.getElementById('filterCollecteur').value || '').toLowerCase().trim();

                if (!filterNom && !filterNumeroFiche && !filterTel && !filterCollecteur) {
                    return allRows.slice();
                }

                return allRows.filter(function (planteur) {
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
            }

            function applyFilter() {
                render(getFilteredRows());
            }

            function mapExportRow(planteur) {
                const collecteur = planteur.collecteur
                    ? `${planteur.collecteur.nom ?? ''} ${planteur.collecteur.prenoms ?? ''}`.trim()
                    : '';
                const exploitation = planteur.exploitation || {};
                const cultures = Array.isArray(planteur.cultures) ? planteur.cultures : [];
                const superficie = cultures.reduce(function (sum, culture) {
                    const value = parseFloat(culture?.superficie_ha);
                    return sum + (Number.isFinite(value) ? value : 0);
                }, 0);
                const typesCulture = cultures
                    .map(function (culture) { return culture?.type_culture || culture?.autre_culture || ''; })
                    .filter(Boolean)
                    .join(' | ');

                return {
                    numero_fiche: planteur.numero_fiche || '',
                    nom_prenoms: planteur.nom_prenoms || '',
                    telephone: planteur.telephone || '',
                    piece_identite: planteur.piece_identite || '',
                    date_naissance: fmtDate(planteur.date_naissance),
                    lieu_naissance: planteur.lieu_naissance || '',
                    situation_matrimoniale: planteur.situation_matrimoniale || '',
                    nombre_enfants: planteur.nombre_enfants ?? '',
                    collecteur: collecteur,
                    region: exploitation.region || '',
                    sous_prefecture: exploitation.sous_prefecture_village || '',
                    village: exploitation.village || '',
                    longitude: exploitation.longitude ?? '',
                    latitude: exploitation.latitude ?? '',
                    delegue: exploitation.delegue_nom || '',
                    types_culture: typesCulture,
                    superficie: superficie > 0
                        ? superficie.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
                        : '',
                    date_enregistrement: fmtDate(planteur.date_enregistrement),
                    created_at: fmtDate(planteur.created_at),
                };
            }

            function exportFileStamp() {
                const now = new Date();
                const dd = String(now.getDate()).padStart(2, '0');
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                const yyyy = now.getFullYear();
                return `${dd}-${mm}-${yyyy}`;
            }

            function getActiveFilters() {
                return {
                    nom: (document.getElementById('filterNom').value || '').toLowerCase().trim(),
                    numeroFiche: (document.getElementById('filterNumeroFiche').value || '').toLowerCase().trim(),
                    tel: (document.getElementById('filterTelephone').value || '').toLowerCase().trim(),
                    collecteur: (document.getElementById('filterCollecteur').value || '').toLowerCase().trim(),
                };
            }

            function matchesFilters(planteur, filters) {
                if (!filters.nom && !filters.numeroFiche && !filters.tel && !filters.collecteur) {
                    return true;
                }

                const collecteur = planteur.collecteur
                    ? `${planteur.collecteur.nom ?? ''} ${planteur.collecteur.prenoms ?? ''}`.trim().toLowerCase()
                    : '';
                const nom = (planteur.nom_prenoms || '').toLowerCase();
                const numeroFiche = (planteur.numero_fiche || '').toLowerCase();
                const tel = (planteur.telephone || '').toLowerCase();

                return (!filters.nom || nom.includes(filters.nom))
                    && (!filters.numeroFiche || numeroFiche.includes(filters.numeroFiche))
                    && (!filters.tel || tel.includes(filters.tel))
                    && (!filters.collecteur || collecteur.includes(filters.collecteur));
            }

            async function fetchExportRows() {
                const filters = getActiveFilters();
                const pageSize = 100;
                let page = 1;
                let totalPages = 1;
                const all = [];

                do {
                    const res = await fetch(buildApiUrl({
                        action: 'planteurs',
                        page: page,
                        limit: pageSize,
                    }), { cache: 'no-store' });
                    const json = await res.json();

                    if (!res.ok || !json?.success) {
                        throw new Error(json?.error || json?.message || 'Impossible de charger les données à exporter.');
                    }

                    const batch = json.data?.planteurs || [];
                    all.push(...batch);
                    totalPages = Math.max(1, parseInt(json.data?.total_pages, 10) || 1);
                    page += 1;
                } while (page <= totalPages);

                return all
                    .filter(function (planteur) { return matchesFilters(planteur, filters); })
                    .map(mapExportRow);
            }

            function csvEscape(value) {
                const text = String(value ?? '');
                if (/[",;\n]/.test(text)) {
                    return '"' + text.replaceAll('"', '""') + '"';
                }
                return text;
            }

            const EXPORT_COLUMNS = [
                { key: 'numero_fiche', label: 'N° Fiche' },
                { key: 'nom_prenoms', label: 'Nom & Prénoms' },
                { key: 'telephone', label: 'Téléphone' },
                { key: 'piece_identite', label: "Pièce d'identité" },
                { key: 'date_naissance', label: 'Date de naissance' },
                { key: 'lieu_naissance', label: 'Lieu de naissance' },
                { key: 'situation_matrimoniale', label: 'Situation matrimoniale' },
                { key: 'nombre_enfants', label: "Nombre d'enfants" },
                { key: 'collecteur', label: 'Collecteur' },
                { key: 'region', label: 'Région' },
                { key: 'sous_prefecture', label: 'Sous-préfecture' },
                { key: 'village', label: 'Village' },
                { key: 'longitude', label: 'Longitude' },
                { key: 'latitude', label: 'Latitude' },
                { key: 'delegue', label: 'Délégué' },
                { key: 'types_culture', label: 'Type(s) de culture' },
                { key: 'superficie', label: 'Superficie (ha)' },
                { key: 'date_enregistrement', label: "Date d'enregistrement" },
                { key: 'created_at', label: 'Créé le' },
            ];

            function rowsToCsv(rows) {
                const lines = [EXPORT_COLUMNS.map(function (col) { return csvEscape(col.label); }).join(';')];
                rows.forEach(function (row) {
                    lines.push(EXPORT_COLUMNS.map(function (col) { return csvEscape(row[col.key]); }).join(';'));
                });
                return '\uFEFF' + lines.join('\n');
            }

            function downloadBlob(content, filename, mime) {
                const blob = new Blob([content], { type: mime });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            }

            function buildPrintHtml(rows, title) {
                const headerCells = EXPORT_COLUMNS.map(function (col) {
                    return `<th>${escapeHtml(col.label)}</th>`;
                }).join('');
                const bodyRows = rows.map(function (row) {
                    const cells = EXPORT_COLUMNS.map(function (col) {
                        const align = col.key === 'superficie' ? ' style="text-align:right"' : '';
                        return `<td${align}>${escapeHtml(row[col.key])}</td>`;
                    }).join('');
                    return `<tr>${cells}</tr>`;
                }).join('');

                return `<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>${escapeHtml(title)}</title>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 10px; color: #111; }
                        h1 { font-size: 16px; margin-bottom: 4px; }
                        .meta { color: #666; margin-bottom: 12px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; white-space: nowrap; }
                        th { background: #111; color: #fff; }
                        @media print {
                            body { margin: 0; }
                            @page { size: landscape; margin: 8mm; }
                        }
                    </style></head><body>
                    <h1>${escapeHtml(title)}</h1>
                    <div class="meta">${rows.length} planteur(s) — ${new Date().toLocaleString('fr-FR')}</div>
                    <table>
                        <thead><tr>${headerCells}</tr></thead>
                        <tbody>${bodyRows || `<tr><td colspan="${EXPORT_COLUMNS.length}">Aucune donnée</td></tr>`}</tbody>
                    </table>
                    <script>window.onload = function () { window.focus(); window.print(); };<\/script>
                    </body></html>`;
            }

            async function withExportLoading(button, handler) {
                const original = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                try {
                    await handler();
                } catch (error) {
                    showError(error?.message || String(error));
                } finally {
                    button.disabled = false;
                    button.innerHTML = original;
                }
            }

            function parseCsv(text) {
                const normalized = text.replace(/^\uFEFF/, '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                const lines = normalized.split('\n').filter(function (line) { return line.trim() !== ''; });
                if (lines.length < 2) {
                    throw new Error('Le fichier CSV est vide ou invalide.');
                }

                const delimiter = lines[0].includes(';') ? ';' : ',';
                function splitLine(line) {
                    const cells = [];
                    let current = '';
                    let inQuotes = false;
                    for (let i = 0; i < line.length; i++) {
                        const char = line[i];
                        if (char === '"') {
                            if (inQuotes && line[i + 1] === '"') {
                                current += '"';
                                i++;
                            } else {
                                inQuotes = !inQuotes;
                            }
                        } else if (char === delimiter && !inQuotes) {
                            cells.push(current.trim());
                            current = '';
                        } else {
                            current += char;
                        }
                    }
                    cells.push(current.trim());
                    return cells;
                }

                const headers = splitLine(lines[0]).map(function (h) {
                    return h.toLowerCase().replace(/\s+/g, '_');
                });
                const rows = [];

                for (let i = 1; i < lines.length; i++) {
                    const cells = splitLine(lines[i]);
                    const row = {};
                    headers.forEach(function (header, index) {
                        row[header] = cells[index] ?? '';
                    });
                    if (!row.nom_prenoms && !row.numero_fiche) {
                        continue;
                    }
                    rows.push(row);
                }

                if (!rows.length) {
                    throw new Error('Aucune ligne valide trouvée dans le CSV.');
                }

                return rows;
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
                const targetId = String(id);
                const res = await fetch(buildApiUrl({ action: 'planteurs', id: targetId }), { cache: 'no-store' });
                const json = await res.json();
                if (!res.ok || !json?.success) {
                    throw new Error(json?.error || json?.message || 'Erreur API');
                }

                const list = Array.isArray(json?.data?.planteurs) ? json.data.planteurs : null;
                let planteur = null;

                if (list) {
                    planteur = list.find(function (item) { return String(item?.id) === targetId; }) || null;
                } else if (json?.data && String(json.data.id) === targetId) {
                    planteur = json.data;
                }

                if (!planteur || String(planteur.id) !== targetId) {
                    throw new Error('Planteur introuvable.');
                }
                return planteur;
            }

            function drawParcelles(planteur) {
                const hintEl = document.getElementById('parcellesMapHint');
                const mapDiv = document.getElementById('parcellesMap');

                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }
                mapDiv.innerHTML = '';
                delete mapDiv._leaflet_id;
                hintEl.classList.add('d-none');

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

                const nom = planteur?.nom_prenoms || ('#' + (planteur?.id ?? ''));
                hintEl.textContent = `${nom} | Parcelles: ${parcellesList.length} | Points: ${boundsPoints.length}`;
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

            document.getElementById('planteursExportExcelBtn').addEventListener('click', function () {
                const button = this;
                withExportLoading(button, async function () {
                    const rows = await fetchExportRows();
                    downloadBlob(
                        rowsToCsv(rows),
                        `planteurs ${exportFileStamp()}.csv`,
                        'text/csv;charset=utf-8;'
                    );
                });
            });

            document.getElementById('planteursExportPdfBtn').addEventListener('click', function () {
                const button = this;
                withExportLoading(button, async function () {
                    const rows = await fetchExportRows();
                    const win = window.open('', '_blank');
                    if (!win) {
                        throw new Error('Autorisez les pop-ups pour exporter en PDF.');
                    }
                    win.document.open();
                    win.document.write(buildPrintHtml(rows, `planteurs ${exportFileStamp()}`));
                    win.document.close();
                });
            });

            document.getElementById('planteursPrintBtn').addEventListener('click', function () {
                const button = this;
                withExportLoading(button, async function () {
                    const rows = await fetchExportRows();
                    const win = window.open('', '_blank');
                    if (!win) {
                        throw new Error('Autorisez les pop-ups pour imprimer.');
                    }
                    win.document.open();
                    win.document.write(buildPrintHtml(rows, `planteurs ${exportFileStamp()}`));
                    win.document.close();
                });
            });

            document.getElementById('confirmImportPlanteursBtn').addEventListener('click', async function () {
                const fileInput = document.getElementById('importPlanteursFile');
                const feedback = document.getElementById('importPlanteursFeedback');
                const button = this;
                const file = fileInput.files?.[0];

                feedback.classList.add('d-none');
                feedback.classList.remove('alert-success', 'alert-danger');

                if (!file) {
                    feedback.textContent = 'Veuillez sélectionner un fichier CSV.';
                    feedback.classList.remove('d-none');
                    feedback.classList.add('alert-danger');
                    return;
                }

                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Import…';

                try {
                    const text = await file.text();
                    const rows = parseCsv(text);
                    const res = await fetch(apiBaseUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'import_planteurs',
                            planteurs: rows,
                        }),
                    });
                    const json = await res.json();
                    if (!res.ok || json?.success === false) {
                        throw new Error(json?.error || json?.message || 'Import impossible.');
                    }

                    feedback.textContent = json?.message || `${rows.length} ligne(s) importée(s).`;
                    feedback.classList.remove('d-none');
                    feedback.classList.add('alert-success');
                    fileInput.value = '';
                    load(1);
                } catch (error) {
                    feedback.textContent = error?.message || String(error);
                    feedback.classList.remove('d-none');
                    feedback.classList.add('alert-danger');
                } finally {
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-upload"></i> Importer';
                }
            });

            document.getElementById('importPlanteursModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('importPlanteursFile').value = '';
                const feedback = document.getElementById('importPlanteursFeedback');
                feedback.classList.add('d-none');
                feedback.textContent = '';
            });

            load(1);
        });
    </script>
@endpush
