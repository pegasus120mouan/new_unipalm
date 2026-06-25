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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des véhicules</span>
                    <span class="text-muted">{{ $vehicules->total() }} véhicule(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Matricule</th>
                                <th>Date d'ajout</th>
                                <th class="text-center">Tickets</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vehicules as $vehicule)
                                <tr>
                                    <td class="d-flex align-items-center">
                                        @include('vehicules.partials.type-icon', ['type' => $vehicule->type_vehicule])
                                        {{ $vehicule->type_label }}
                                    </td>
                                    <td class="fw-semibold">{{ $vehicule->matricule_vehicule }}</td>
                                    <td>{{ $vehicule->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="text-center">{{ $vehicule->tickets_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
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

    @if ($errors->has('matricule_vehicule') || $errors->has('type_vehicule'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addVehiculeModal')).show();
            });
        </script>
    @endif
@endsection
