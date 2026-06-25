@extends('layout.main')

@section('title', 'Liste des utilisateurs')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Liste des utilisateurs</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
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

    @if ($errors->has('statut'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('statut') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #435ebe;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['total'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Total utilisateurs</div>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #198754;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['actifs'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Comptes actifs</div>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #dc3545;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['inactifs'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Comptes inactifs</div>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUtilisateurModal">
                            <i class="bi bi-person-plus-fill"></i> Enregistrer un utilisateur
                        </button>
                        <a href="#utilisateur-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4" id="utilisateur-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres de recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('utilisateurs.index') }}">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <label for="search_login" class="form-label">Login</label>
                                <input type="text" name="search_login" id="search_login" class="form-control"
                                    placeholder="Rechercher par login..." value="{{ $filters['search_login'] }}">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="search_nom" class="form-label">Nom / Prénoms</label>
                                <input type="text" name="search_nom" id="search_nom" class="form-control"
                                    placeholder="Rechercher par nom..." value="{{ $filters['search_nom'] }}">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="search_role" class="form-label">Rôle</label>
                                <select name="search_role" id="search_role" class="form-select">
                                    <option value="">Tous les rôles</option>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['search_role'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="statut" class="form-label">Statut</label>
                                <select name="statut" id="statut" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="actif" @selected($filters['statut'] === 'actif')>Actif</option>
                                    <option value="inactif" @selected($filters['statut'] === 'inactif')>Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('utilisateurs.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> Réinitialiser
                            </a>
                        </div>
                    </form>

                    @if ($hasFilters)
                        <div class="alert alert-light border mt-3 mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>{{ $utilisateurs->total() }}</strong> utilisateur(s) trouvé(s)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people"></i> Utilisateurs</span>
                    <span class="text-muted">{{ $utilisateurs->total() }} utilisateur(s)</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Contact</th>
                                <th>Login</th>
                                <th>Rôle</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($utilisateurs as $utilisateur)
                                @php
                                    $roleBadge = match ($utilisateur->role) {
                                        'admin' => 'bg-danger',
                                        'directeur' => 'bg-primary',
                                        'validateur' => 'bg-info',
                                        'caissiere' => 'bg-success',
                                        'operateur' => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                                style="width: 36px; height: 36px; font-size: 0.85rem; font-weight: 600;">
                                                {{ strtoupper(substr($utilisateur->nom, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $utilisateur->full_name }}</div>
                                                <small class="text-muted">#{{ $utilisateur->id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($utilisateur->contact)
                                            <span class="badge bg-light text-dark border">{{ $utilisateur->contact }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><code>{{ $utilisateur->login }}</code></td>
                                    <td>
                                        <span class="badge {{ $roleBadge }}">{{ $utilisateur->role_label }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($utilisateur->isActive())
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($utilisateur->id !== auth()->id())
                                            <form method="POST" action="{{ route('utilisateurs.toggle-statut', $utilisateur) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $utilisateur->isActive() ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                    title="{{ $utilisateur->isActive() ? 'Désactiver' : 'Activer' }}">
                                                    <i class="bi bi-{{ $utilisateur->isActive() ? 'toggle-off' : 'toggle-on' }}"></i>
                                                    {{ $utilisateur->isActive() ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Votre compte</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Aucun utilisateur trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($utilisateurs->hasPages())
                    <div class="card-footer">
                        {{ $utilisateurs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="modal fade" id="addUtilisateurModal" tabindex="-1" aria-labelledby="addUtilisateurModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUtilisateurModalLabel">
                        <i class="bi bi-person-plus-fill"></i> Enregistrer un utilisateur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('utilisateurs.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                                    value="{{ old('nom') }}" required>
                                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="prenoms" class="form-label">Prénoms</label>
                                <input type="text" name="prenoms" id="prenoms" class="form-control @error('prenoms') is-invalid @enderror"
                                    value="{{ old('prenoms') }}" required>
                                @error('prenoms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="contact" class="form-label">Contact</label>
                                <input type="text" name="contact" id="contact" class="form-control @error('contact') is-invalid @enderror"
                                    value="{{ old('contact') }}" required>
                                @error('contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="login" class="form-label">Login</label>
                                <input type="text" name="login" id="login" class="form-control @error('login') is-invalid @enderror"
                                    value="{{ old('login') }}" required>
                                @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmation</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label for="role" class="form-label">Rôle</label>
                                <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="">Sélectionner un rôle</option>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($errors->has('nom') || $errors->has('prenoms') || $errors->has('contact') || $errors->has('login') || $errors->has('password') || $errors->has('role'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addUtilisateurModal')).show();
            });
        </script>
    @endif
@endpush
