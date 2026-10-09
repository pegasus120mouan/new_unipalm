@extends('layout.main')

@section('title', 'Primes BISMAR')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Primes BISMAR</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Primes BISMAR</li>
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
                <div class="card-body py-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrimeModal">
                        <i class="bi bi-plus-circle"></i> Enregistrer une prime BISMAR
                    </button>
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
                    <form method="GET" action="{{ route('primes-bismar.index') }}" class="row g-3 align-items-end">
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
                            <a href="{{ route('primes-bismar.index') }}" class="btn btn-secondary">
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
                    <span>Liste des primes BISMAR</span>
                    <span class="text-muted">{{ $primes->total() }} enregistrement(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th class="text-end">Prix (FCFA)</th>
                                <th>Créée par</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($primes as $prime)
                                @php
                                    $prix = (float) $prime->prix;
                                    $prixBrut = fmod($prix, 1.0) == 0.0
                                        ? (string) (int) $prix
                                        : rtrim(rtrim(number_format($prix, 2, '.', ''), '0'), '.');
                                    $enCours = $prime->date_debut->lte(today()) && $prime->date_fin->gte(today());
                                @endphp
                                <tr>
                                    <td>{{ $prime->date_debut->format('d/m/Y') }}</td>
                                    <td>
                                        {{ $prime->date_fin->format('d/m/Y') }}
                                        @if ($enCours)
                                            <span class="badge bg-success ms-1">En cours</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ fmod($prix, 1.0) == 0.0 ? number_format($prix, 0, '.', ' ') : number_format($prix, 2, '.', ' ') }}
                                    </td>
                                    <td>{{ $prime->createur?->full_name ?? '-' }}</td>
                                    <td class="text-center text-nowrap">
                                        <button type="button"
                                            class="btn btn-warning btn-sm btn-edit-prime"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPrimeModal"
                                            data-id="{{ $prime->id }}"
                                            data-prix="{{ $prixBrut }}"
                                            data-debut="{{ $prime->date_debut->format('Y-m-d') }}"
                                            data-fin="{{ $prime->date_fin->format('Y-m-d') }}"
                                            title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('primes-bismar.destroy', $prime) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer"
                                                onclick="return confirm(@json('Supprimer la prime BISMAR du '.$prime->date_debut->format('d/m/Y').' au '.$prime->date_fin->format('d/m/Y').' ?'));">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucune prime BISMAR enregistrée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $primes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $editPrimeId = session('edit_prime_id') ?? old('edit_prime_id');
        $isEditError = $editPrimeId || old('_method') === 'PUT';
    @endphp

    @foreach ([
        ['id' => 'addPrimeModal', 'prefix' => 'add', 'title' => 'Enregistrer une prime BISMAR', 'action' => route('primes-bismar.store'), 'method' => null, 'button' => 'Enregistrer', 'buttonClass' => 'btn-primary', 'showErrors' => ! $isEditError],
        ['id' => 'editPrimeModal', 'prefix' => 'edit', 'title' => 'Modifier la prime BISMAR', 'action' => '', 'method' => 'PUT', 'button' => 'Enregistrer les modifications', 'buttonClass' => 'btn-warning', 'showErrors' => $isEditError],
    ] as $modal)
        <div class="modal fade" id="{{ $modal['id'] }}" tabindex="-1" aria-labelledby="{{ $modal['id'] }}Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ $modal['action'] }}" id="{{ $modal['prefix'] }}PrimeForm">
                        @csrf
                        @if ($modal['method'])
                            @method($modal['method'])
                            <input type="hidden" name="edit_prime_id" id="edit_prime_id" value="{{ $editPrimeId }}">
                        @endif
                        <div class="modal-header">
                            <h5 class="modal-title" id="{{ $modal['id'] }}Label">{{ $modal['title'] }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info small mb-3">
                                Une seule prime par période : les dates ne peuvent pas chevaucher une prime existante.
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
                                    <label for="{{ $modal['prefix'] }}_prix" class="form-label">Prix (FCFA)</label>
                                    <input type="number" name="prix" id="{{ $modal['prefix'] }}_prix" step="0.01" min="0.01"
                                        class="form-control {{ $modal['showErrors'] && $errors->has('prix') ? 'is-invalid' : '' }}"
                                        value="{{ $modal['showErrors'] ? old('prix') : '' }}" required>
                                    @if ($modal['showErrors'] && $errors->has('prix'))
                                        <div class="invalid-feedback">{{ $errors->first('prix') }}</div>
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
            const editForm = document.getElementById('editPrimeForm');
            const updateUrlBase = @json(url('/primes-bismar'));

            ['add', 'edit'].forEach(function (prefix) {
                const debut = document.getElementById(prefix + '_date_debut');
                const fin = document.getElementById(prefix + '_date_fin');
                const sync = function () { fin.min = debut.value || ''; };
                debut.addEventListener('change', sync);
                sync();
            });

            document.querySelectorAll('.btn-edit-prime').forEach(function (button) {
                button.addEventListener('click', function () {
                    editForm.action = updateUrlBase + '/' + button.dataset.id;
                    document.getElementById('edit_prime_id').value = button.dataset.id;
                    document.getElementById('edit_prix').value = button.dataset.prix || '';
                    document.getElementById('edit_date_debut').value = button.dataset.debut || '';
                    document.getElementById('edit_date_fin').value = button.dataset.fin || '';
                    document.getElementById('edit_date_fin').min = button.dataset.debut || '';
                });
            });

            @if ($errors->any() && $isEditError)
                editForm.action = updateUrlBase + '/' + @json($editPrimeId);
                new bootstrap.Modal(document.getElementById('editPrimeModal')).show();
            @elseif ($errors->any())
                new bootstrap.Modal(document.getElementById('addPrimeModal')).show();
            @endif
        });
    </script>
    @endpush
@endsection
