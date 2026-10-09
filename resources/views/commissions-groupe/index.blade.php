@extends('layout.main')

@section('title', 'Commission groupe')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Commission groupe</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Commission groupe</li>
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

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3 d-flex flex-wrap gap-3 align-items-center">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommissionModal">
                        <i class="bi bi-plus-circle"></i> Enregistrer une commission
                    </button>
                    <span class="text-muted small">
                        Commission d'un groupe = poids des tickets de ses agents × montant de la commission
                        couvrant la date du ticket.
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres de recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('commissions-groupe.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="filter_date_debut" class="form-label">Du</label>
                            <input type="date" name="date_debut" id="filter_date_debut" class="form-control"
                                value="{{ $filters['date_debut'] ?? '' }}">
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="filter_date_fin" class="form-label">Au</label>
                            <input type="date" name="date_fin" id="filter_date_fin" class="form-control"
                                value="{{ $filters['date_fin'] ?? '' }}">
                        </div>
                        <div class="col-md-4 col-lg-6 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('commissions-groupe.index') }}" class="btn btn-secondary">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des commissions groupe</span>
                    <span class="text-muted">{{ $commissions->total() }} enregistrement(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th class="text-end">Montant (FCFA / kg)</th>
                                <th>Créée par</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($commissions as $commission)
                                @php
                                    $montant = (float) $commission->montant;
                                    $montantBrut = fmod($montant, 1.0) == 0.0
                                        ? (string) (int) $montant
                                        : rtrim(rtrim(number_format($montant, 2, '.', ''), '0'), '.');
                                    $enCours = $commission->date_debut->lte(today()) && $commission->date_fin->gte(today());
                                @endphp
                                <tr>
                                    <td>{{ $commission->date_debut->format('d/m/Y') }}</td>
                                    <td>
                                        {{ $commission->date_fin->format('d/m/Y') }}
                                        @if ($enCours)
                                            <span class="badge bg-success ms-1">En cours</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ fmod($montant, 1.0) == 0.0 ? number_format($montant, 0, '.', ' ') : number_format($montant, 2, '.', ' ') }}
                                    </td>
                                    <td>{{ $commission->createur?->full_name ?? '-' }}</td>
                                    <td class="text-center text-nowrap">
                                        <button type="button"
                                            class="btn btn-warning btn-sm btn-edit-commission"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCommissionModal"
                                            data-id="{{ $commission->id }}"
                                            data-montant="{{ $montantBrut }}"
                                            data-debut="{{ $commission->date_debut->format('Y-m-d') }}"
                                            data-fin="{{ $commission->date_fin->format('Y-m-d') }}"
                                            title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('commissions-groupe.destroy', $commission) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer"
                                                onclick="return confirm(@json('Supprimer la commission du '.$commission->date_debut->format('d/m/Y').' au '.$commission->date_fin->format('d/m/Y').' ?'));">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucune commission groupe enregistrée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $commissions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $editCommissionId = session('edit_commission_id') ?? old('edit_commission_id');
        $isEditError = $editCommissionId || old('_method') === 'PUT';
    @endphp

    @foreach ([
        ['id' => 'addCommissionModal', 'prefix' => 'add', 'title' => 'Enregistrer une commission groupe', 'action' => route('commissions-groupe.store'), 'method' => null, 'button' => 'Enregistrer', 'buttonClass' => 'btn-primary', 'showErrors' => ! $isEditError],
        ['id' => 'editCommissionModal', 'prefix' => 'edit', 'title' => 'Modifier la commission groupe', 'action' => '', 'method' => 'PUT', 'button' => 'Enregistrer les modifications', 'buttonClass' => 'btn-warning', 'showErrors' => $isEditError],
    ] as $modal)
        <div class="modal fade" id="{{ $modal['id'] }}" tabindex="-1" aria-labelledby="{{ $modal['id'] }}Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ $modal['action'] }}" id="{{ $modal['prefix'] }}CommissionForm">
                        @csrf
                        @if ($modal['method'])
                            @method($modal['method'])
                            <input type="hidden" name="edit_commission_id" id="edit_commission_id" value="{{ $editCommissionId }}">
                        @endif
                        <div class="modal-header">
                            <h5 class="modal-title" id="{{ $modal['id'] }}Label">{{ $modal['title'] }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info small mb-3">
                                Un seul montant par période : les dates ne peuvent pas chevaucher une commission existante.
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="{{ $modal['prefix'] }}_date_debut" class="form-label">Date début</label>
                                    <input type="date" name="date_debut" id="{{ $modal['prefix'] }}_date_debut"
                                        class="form-control {{ $modal['showErrors'] && $errors->has('date_debut') ? 'is-invalid' : '' }}"
                                        value="{{ $modal['showErrors'] ? old('date_debut') : '' }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="{{ $modal['prefix'] }}_date_fin" class="form-label">Date fin</label>
                                    <input type="date" name="date_fin" id="{{ $modal['prefix'] }}_date_fin"
                                        class="form-control {{ $modal['showErrors'] && $errors->has('date_fin') ? 'is-invalid' : '' }}"
                                        value="{{ $modal['showErrors'] ? old('date_fin') : '' }}" required>
                                </div>
                                @if ($modal['showErrors'] && ($errors->has('date_debut') || $errors->has('date_fin')))
                                    <div class="col-12">
                                        <div class="text-danger small">{{ $errors->first('date_debut') ?: $errors->first('date_fin') }}</div>
                                    </div>
                                @endif
                                <div class="col-12">
                                    <label for="{{ $modal['prefix'] }}_montant" class="form-label">Montant (FCFA par kg)</label>
                                    <input type="number" name="montant" id="{{ $modal['prefix'] }}_montant" step="0.01" min="0.01"
                                        class="form-control {{ $modal['showErrors'] && $errors->has('montant') ? 'is-invalid' : '' }}"
                                        value="{{ $modal['showErrors'] ? old('montant') : '' }}" required>
                                    @if ($modal['showErrors'] && $errors->has('montant'))
                                        <div class="invalid-feedback">{{ $errors->first('montant') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn {{ $modal['buttonClass'] }}">{{ $modal['button'] }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.getElementById('editCommissionForm');
            const updateUrlBase = @json(url('/commissions-groupe'));

            ['add', 'edit'].forEach(function (prefix) {
                const debut = document.getElementById(prefix + '_date_debut');
                const fin = document.getElementById(prefix + '_date_fin');
                const sync = function () { fin.min = debut.value || ''; };
                debut.addEventListener('change', sync);
                sync();
            });

            document.querySelectorAll('.btn-edit-commission').forEach(function (button) {
                button.addEventListener('click', function () {
                    editForm.action = updateUrlBase + '/' + button.dataset.id;
                    document.getElementById('edit_commission_id').value = button.dataset.id;
                    document.getElementById('edit_montant').value = button.dataset.montant || '';
                    document.getElementById('edit_date_debut').value = button.dataset.debut || '';
                    document.getElementById('edit_date_fin').value = button.dataset.fin || '';
                    document.getElementById('edit_date_fin').min = button.dataset.debut || '';
                });
            });

            @if ($errors->any() && $isEditError)
                editForm.action = updateUrlBase + '/' + @json($editCommissionId);
                new bootstrap.Modal(document.getElementById('editCommissionModal')).show();
            @elseif ($errors->any())
                new bootstrap.Modal(document.getElementById('addCommissionModal')).show();
            @endif
        });
    </script>
    @endpush
@endsection
