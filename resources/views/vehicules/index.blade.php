@extends('layout.main')

@section('title', 'Véhicules')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Véhicules</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Véhicules</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if (($stats['duplicates'] ?? 0) > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>{{ $stats['duplicates'] }} matricule(s) en doublon</strong> détecté(s) (espaces et casse ignorés).
            <a href="{{ route('vehicules.index', array_filter(['duplicates' => 1, 'search' => $search ?: null, 'type' => $type ?: null])) }}" class="alert-link">
                Afficher les doublons
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if ($duplicatesOnly ?? false)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            Filtre actif : véhicules en doublon uniquement.
            @if (($stats['deletable_duplicates'] ?? 0) > 0)
                <strong>{{ $stats['deletable_duplicates'] }}</strong> doublon(s) supprimable(s)
                (un exemplaire conservé par matricule ; tickets supprimés ou réaffectés).
            @endif
            <a href="{{ route('vehicules.index', array_filter(['search' => $search ?: null, 'type' => $type ?: null])) }}" class="alert-link">Voir tous</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVehiculeModal">
                            <i class="bi bi-truck"></i> Enregistrer un véhicule
                        </button>
                        <button type="button" class="btn btn-danger" disabled title="Bientôt disponible">
                            <i class="bi bi-printer-fill"></i> Imprimer la liste
                        </button>
                        <a href="#vehicule-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher un véhicule
                        </a>
                        <button type="button" class="btn btn-warning" disabled title="Bientôt disponible">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i> Exporter la liste
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="vehicule-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('vehicules.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-5 col-lg-4">
                            <label for="search" class="form-label">Matricule</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Rechercher un matricule..." value="{{ $search }}">
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">Tous les types</option>
                                <option value="voiture" @selected($type === 'voiture')>Voiture</option>
                                <option value="moto" @selected($type === 'moto')>Moto</option>
                                <option value="tricycle" @selected($type === 'tricycle')>Tricycle</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-5 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('vehicules.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Liste des véhicules</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if (($stats['deletable_duplicates'] ?? 0) > 0)
                            <button type="button" class="btn btn-sm btn-outline-danger" id="selectAllDeletableBtn">
                                <i class="bi bi-check2-square"></i> Tout sélectionner ({{ $stats['deletable_duplicates'] }})
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled
                                data-bs-toggle="modal" data-bs-target="#bulkDeleteVehiculeModal">
                                <i class="bi bi-trash"></i> Supprimer la sélection
                            </button>
                        @endif
                        <span class="text-muted">{{ $vehicules->total() }} véhicule(s)</span>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <form method="POST" action="{{ route('vehicules.bulk-destroy') }}" id="bulkDeleteVehiculeForm">
                        @csrf
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                @if (($stats['deletable_duplicates'] ?? 0) > 0)
                                    <th style="width: 2.5rem;">
                                        <input type="checkbox" class="form-check-input" id="selectAllPageCheckbox"
                                            title="Sélectionner tous les supprimables de cette page">
                                    </th>
                                @endif
                                <th>Type</th>
                                <th>Matricule</th>
                                <th>Date d'ajout</th>
                                <th class="text-center">Tickets</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vehicules as $vehicule)
                                @php
                                    $isDuplicate = in_array($vehicule->normalizedMatricule(), $duplicateMatricules ?? [], true);
                                    $canBulkDelete = in_array($vehicule->vehicules_id, $deletableDuplicateIds ?? [], true);
                                @endphp
                                <tr @class(['table-warning' => $isDuplicate])>
                                    @if (($stats['deletable_duplicates'] ?? 0) > 0)
                                        <td>
                                            @if ($canBulkDelete)
                                                <input type="checkbox" class="form-check-input vehicule-bulk-checkbox"
                                                    name="vehicules_ids[]" value="{{ $vehicule->vehicules_id }}">
                                            @endif
                                        </td>
                                    @endif
                                    <td class="d-flex align-items-center">
                                        @include('vehicules.partials.type-icon', ['type' => $vehicule->type_vehicule])
                                        {{ $vehicule->type_label }}
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $vehicule->matricule_vehicule }}
                                        @if ($isDuplicate)
                                            <span class="badge bg-warning text-dark ms-1">Doublon</span>
                                        @endif
                                    </td>
                                    <td>{{ $vehicule->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="text-center">{{ $vehicule->tickets_count }}</td>
                                    <td class="text-end">
                                        @if ($canBulkDelete)
                                            <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#deleteVehiculeModal"
                                                data-id="{{ $vehicule->vehicules_id }}"
                                                data-label="{{ $vehicule->matricule_vehicule }}"
                                                data-tickets="{{ $vehicule->tickets_count }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                title="Exemplaire conservé pour ce matricule">
                                                <i class="bi bi-shield-check"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($stats['deletable_duplicates'] ?? 0) > 0 ? 6 : 5 }}" class="text-center text-muted py-4">
                                        @if ($search !== '' || $type !== '')
                                            Aucun véhicule ne correspond à votre recherche.
                                        @else
                                            Aucun véhicule enregistré.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </form>

                    <div class="d-flex justify-content-center">
                        {{ $vehicules->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addVehiculeModal" tabindex="-1" aria-labelledby="addVehiculeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('vehicules.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addVehiculeModalLabel">Enregistrer un véhicule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="matricule_vehicule" class="form-label">Matricule</label>
                            <input type="text" name="matricule_vehicule" id="matricule_vehicule"
                                class="form-control @error('matricule_vehicule') is-invalid @enderror"
                                value="{{ old('matricule_vehicule') }}" placeholder="Ex. AB-123-CD" required autofocus>
                            @error('matricule_vehicule')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Les espaces sont ignorés. Les doublons (ex. AB123CD et AB 123 CD) sont refusés.</div>
                        </div>
                        <div class="mb-0">
                            <label for="type_vehicule" class="form-label">Type de véhicule</label>
                            <select name="type_vehicule" id="type_vehicule"
                                class="form-select @error('type_vehicule') is-invalid @enderror" required>
                                <option value="voiture" @selected(old('type_vehicule', 'voiture') === 'voiture')>Voiture</option>
                                <option value="moto" @selected(old('type_vehicule') === 'moto')>Moto</option>
                                <option value="tricycle" @selected(old('type_vehicule') === 'tricycle')>Tricycle</option>
                            </select>
                            @error('type_vehicule')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkDeleteVehiculeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Supprimer les doublons sélectionnés</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Confirmer la suppression de <strong id="bulkDeleteCount">0</strong> véhicule(s) en doublon ?</p>
                    <p class="mb-0 small text-muted">Les tickets non soldés seront supprimés. Les tickets soldés seront réaffectés à l'exemplaire conservé par matricule.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteVehiculeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Supprimer le véhicule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Confirmer la suppression du véhicule <strong id="deleteVehiculeLabel"></strong> ?</p>
                    <p class="mb-0 small text-muted" id="deleteVehiculeTicketsInfo"></p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" id="deleteVehiculeForm" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('deleteVehiculeForm');
            const bulkForm = document.getElementById('bulkDeleteVehiculeForm');
            const baseUrl = @json(url('/vehicules'));
            const allDeletableIds = @json($deletableDuplicateIds ?? []);
            const checkboxes = () => Array.from(document.querySelectorAll('.vehicule-bulk-checkbox'));
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const selectAllPageCheckbox = document.getElementById('selectAllPageCheckbox');
            const selectAllDeletableBtn = document.getElementById('selectAllDeletableBtn');
            const bulkDeleteCount = document.getElementById('bulkDeleteCount');
            const confirmBulkDeleteBtn = document.getElementById('confirmBulkDeleteBtn');
            let selectAllDeletableActive = false;

            function clearHiddenBulkInputs() {
                bulkForm.querySelectorAll('input[data-bulk-hidden]').forEach(function (el) {
                    el.remove();
                });
                selectAllDeletableActive = false;
            }

            function selectedCount() {
                if (selectAllDeletableActive) {
                    return allDeletableIds.length;
                }

                return checkboxes().filter(function (cb) { return cb.checked; }).length;
            }

            function updateBulkDeleteState() {
                const count = selectedCount();
                if (bulkDeleteBtn) {
                    bulkDeleteBtn.disabled = count === 0;
                }
                if (bulkDeleteCount) {
                    bulkDeleteCount.textContent = String(count);
                }
                if (selectAllPageCheckbox) {
                    const pageBoxes = checkboxes();
                    selectAllPageCheckbox.checked = pageBoxes.length > 0 && pageBoxes.every(function (cb) { return cb.checked; });
                    selectAllPageCheckbox.indeterminate = count > 0 && !selectAllPageCheckbox.checked;
                }
            }

            checkboxes().forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    clearHiddenBulkInputs();
                    updateBulkDeleteState();
                });
            });

            if (selectAllPageCheckbox) {
                selectAllPageCheckbox.addEventListener('change', function () {
                    clearHiddenBulkInputs();
                    checkboxes().forEach(function (cb) {
                        cb.checked = selectAllPageCheckbox.checked;
                    });
                    updateBulkDeleteState();
                });
            }

            if (selectAllDeletableBtn && bulkForm) {
                selectAllDeletableBtn.addEventListener('click', function () {
                    clearHiddenBulkInputs();
                    selectAllDeletableActive = true;

                    checkboxes().forEach(function (cb) {
                        cb.checked = allDeletableIds.includes(parseInt(cb.value, 10));
                    });

                    allDeletableIds.forEach(function (id) {
                        const onPage = checkboxes().some(function (cb) {
                            return parseInt(cb.value, 10) === id;
                        });

                        if (! onPage) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'vehicules_ids[]';
                            input.value = String(id);
                            input.dataset.bulkHidden = '1';
                            bulkForm.appendChild(input);
                        }
                    });

                    updateBulkDeleteState();
                });
            }

            if (confirmBulkDeleteBtn && bulkForm) {
                confirmBulkDeleteBtn.addEventListener('click', function () {
                    if (selectAllDeletableActive) {
                        clearHiddenBulkInputs();
                        allDeletableIds.forEach(function (id) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'vehicules_ids[]';
                            input.value = String(id);
                            bulkForm.appendChild(input);
                        });
                    }
                    bulkForm.submit();
                });
            }

            document.querySelectorAll('[data-bs-target="#deleteVehiculeModal"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteForm.action = baseUrl + '/' + button.dataset.id;
                    document.getElementById('deleteVehiculeLabel').textContent = button.dataset.label || '';
                    const tickets = parseInt(button.dataset.tickets || '0', 10);
                    const info = document.getElementById('deleteVehiculeTicketsInfo');
                    if (info) {
                        info.textContent = tickets > 0
                            ? tickets + ' ticket(s) associé(s) : les tickets non soldés seront supprimés, les soldés réaffectés à l\'exemplaire conservé.'
                            : 'Aucun ticket associé.';
                    }
                });
            });

            updateBulkDeleteState();
        });
    </script>
    @endpush

    @if ($errors->has('matricule_vehicule') || $errors->has('type_vehicule'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addVehiculeModal')).show();
            });
        </script>
    @endif
@endsection
