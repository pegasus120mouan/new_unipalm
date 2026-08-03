@extends('layout.main')

@section('title', 'Groupes')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Groupes</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Groupes</li>
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

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupeModal">
                        <i class="bi bi-person-badge"></i> Enregistrer un chef d'équipe
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des groupes</span>
                    <span class="text-muted">{{ $groupes->count() }} groupe(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Groupe</th>
                                <th>Token</th>
                                <th>Login</th>
                                <th class="text-center">Particuliers</th>
                                <th class="text-center">Professionnels</th>
                                <th class="text-center">Total agents</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groupes as $groupe)
                                <tr>
                                    <td>
                                        <a href="{{ route('groupes.show', $groupe) }}">{{ $groupe->full_name }}</a>
                                    </td>
                                    <td>
                                        @if ($groupe->token)
                                            <code class="small">{{ $groupe->token }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($groupe->login)
                                            <span class="badge bg-success">{{ $groupe->login }}</span>
                                        @else
                                            <span class="badge bg-secondary">Non défini</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('groupes.show', ['groupe' => $groupe, 'sous_groupe' => \App\Models\Agent::SOUS_GROUPE_PARTICULIER]) }}"
                                            class="badge bg-info text-decoration-none">
                                            {{ $groupe->particuliers_count }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('groupes.show', ['groupe' => $groupe, 'sous_groupe' => \App\Models\Agent::SOUS_GROUPE_PROFESSIONNEL]) }}"
                                            class="badge bg-primary text-decoration-none">
                                            {{ $groupe->professionnels_count }}
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $groupe->agents_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Aucun groupe trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addGroupeModal" tabindex="-1" aria-labelledby="addGroupeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('groupes.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addGroupeModalLabel">
                            <i class="bi bi-person-badge"></i> Enregistrer un chef d'équipe
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom"
                                class="form-control @error('nom') is-invalid @enderror"
                                value="{{ old('nom') }}" placeholder="Nom du chef d'équipe" required autofocus>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="prenoms" class="form-label">Prénoms <span class="text-danger">*</span></label>
                            <input type="text" name="prenoms" id="prenoms"
                                class="form-control @error('prenoms') is-invalid @enderror"
                                value="{{ old('prenoms') }}" placeholder="Prénoms du chef d'équipe" required>
                            @error('prenoms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="token" class="form-label">Token</label>
                            <input type="text" name="token" id="token"
                                class="form-control @error('token') is-invalid @enderror"
                                value="{{ old('token') }}" placeholder="Laisser vide pour générer automatiquement">
                            @error('token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Si vide, un token unique sera créé automatiquement.</div>
                        </div>

                        <hr class="my-3">
                        <p class="text-muted small mb-3">Identifiants d'accès (optionnels — configurables plus tard dans Accès).</p>

                        <div class="mb-3">
                            <label for="login" class="form-label">Login</label>
                            <input type="text" name="login" id="login"
                                class="form-control @error('login') is-invalid @enderror"
                                value="{{ old('login') }}" placeholder="Ex. koanda" autocomplete="username">
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="new-password" minlength="6">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control" autocomplete="new-password" minlength="6">
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

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addGroupeModal')).show();
            });
        </script>
    @endif
@endsection
