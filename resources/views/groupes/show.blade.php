@extends('layout.main')

@section('title')
    Groupes — {{ $groupe->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Agents — {{ $groupe->full_name }}</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('groupes.index') }}">Groupes</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $groupe->full_name }}</li>
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
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row mb-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-shield-lock me-1"></i> Accès chef d'équipe
                </div>
                <div class="card-body">
                    <dl class="row mb-3 small">
                        <dt class="col-sm-4">Token</dt>
                        <dd class="col-sm-8">
                            @if ($groupe->token)
                                <code>{{ $groupe->token }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>
                        <dt class="col-sm-4">Statut</dt>
                        <dd class="col-sm-8">
                            @if ($groupe->hasCredentials())
                                <span class="badge bg-success">Login actif</span>
                            @else
                                <span class="badge bg-warning text-dark">À configurer</span>
                            @endif
                        </dd>
                    </dl>

                    <form method="POST" action="{{ route('groupes.credentials.update', $groupe) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="login" class="form-label">Login <span class="text-danger">*</span></label>
                            <input type="text" name="login" id="login"
                                class="form-control @error('login') is-invalid @enderror"
                                value="{{ old('login', $groupe->login) }}" required autocomplete="username">
                            @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Mot de passe
                                @if (! $groupe->hasCredentials())
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                @if (! $groupe->hasCredentials()) required @endif
                                autocomplete="new-password" minlength="6">
                            @if ($groupe->hasCredentials())
                                <small class="text-muted">Laisser vide pour conserver le mot de passe actuel.</small>
                            @endif
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control" autocomplete="new-password" minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save"></i> Enregistrer les identifiants
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('groupes.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour aux groupes
            </a>
            <div class="btn-group btn-group-sm" role="group" aria-label="Filtrer par sous-groupe">
                <a href="{{ route('groupes.show', $groupe) }}"
                    class="btn {{ $sousGroupe === '' ? 'btn-dark' : 'btn-outline-dark' }}">
                    Tous ({{ $counts['total'] }})
                </a>
                <a href="{{ route('groupes.show', ['groupe' => $groupe, 'sous_groupe' => \App\Models\Agent::SOUS_GROUPE_PARTICULIER]) }}"
                    class="btn {{ $sousGroupe === \App\Models\Agent::SOUS_GROUPE_PARTICULIER ? 'btn-info' : 'btn-outline-info' }}">
                    Particuliers ({{ $counts['particuliers'] }})
                </a>
                <a href="{{ route('groupes.show', ['groupe' => $groupe, 'sous_groupe' => \App\Models\Agent::SOUS_GROUPE_PROFESSIONNEL]) }}"
                    class="btn {{ $sousGroupe === \App\Models\Agent::SOUS_GROUPE_PROFESSIONNEL ? 'btn-primary' : 'btn-outline-primary' }}">
                    Professionnels ({{ $counts['professionnels'] }})
                </a>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des agents</span>
                    <span class="text-muted">{{ $agents->total() }} agent(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>N° Agent</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Sous-groupe</th>
                                <th>Contact</th>
                                <th>Date de création</th>
                                <th>Ajouté par</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $agent)
                                <tr>
                                    <td>
                                        @if ($agent->numero_agent)
                                            <a href="{{ route('agents.show', $agent) }}" class="badge bg-primary text-decoration-none">
                                                {{ $agent->numero_agent }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $agent->nom }}</td>
                                    <td>{{ $agent->prenom }}</td>
                                    <td>
                                        @if ($agent->isProfessionnel())
                                            <span class="badge bg-primary">Professionnels</span>
                                        @else
                                            <span class="badge bg-info">Particuliers</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($agent->contact)
                                            <span class="badge bg-info">{{ $agent->contact }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $agent->date_ajout?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $agent->createur?->full_name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Aucun agent associé à ce sous-groupe.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $agents->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
