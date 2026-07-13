@extends('layout.main')

@section('title')
    Groupes — {{ $groupe->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Groupe — {{ $groupe->full_name }}</h3>
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
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="btn-group btn-group-sm" role="group" aria-label="Sections du groupe">
                <a href="{{ route('groupes.show', array_filter([
                        'groupe' => $groupe,
                        'section' => 'agents',
                        'sous_groupe' => $sousGroupe ?: null,
                    ])) }}"
                    class="btn {{ $activeSection === 'agents' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-people"></i> Agents
                </a>
                <a href="{{ route('groupes.show', ['groupe' => $groupe, 'section' => 'acces']) }}"
                    class="btn {{ $activeSection === 'acces' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-shield-lock"></i> Accès
                </a>
                <a href="{{ route('groupes.show', ['groupe' => $groupe, 'section' => 'solde']) }}"
                    class="btn {{ $activeSection === 'solde' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-wallet2"></i> Solde
                </a>
            </div>
            <a href="{{ route('groupes.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour aux groupes
            </a>
        </div>
    </section>

    @if ($activeSection === 'acces')
        <section class="row mb-3">
            <div class="col-lg-6">
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
    @elseif ($activeSection === 'solde')
        <section class="row mb-3">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body py-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 48px; height: 48px;">
                                    <i class="bi bi-wallet2 fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Solde actuel du chef de groupe</h6>
                                    <small class="text-muted text-uppercase">{{ $groupe->full_name }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fs-3 fw-bold mb-0 {{ $soldeChef['reste_a_payer'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($soldeChef['reste_a_payer'], 0, '', ' ') }} FCFA
                                </div>
                                <small class="text-muted">Reste à payer — diminue à chaque validation / paiement</small>
                                <div class="d-flex flex-wrap justify-content-end gap-3 mt-2 small">
                                    <span>
                                        Particuliers :
                                        <strong class="{{ ($soldeChef['reste_particuliers'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($soldeChef['reste_particuliers'] ?? 0, 0, '', ' ') }} FCFA
                                        </strong>
                                    </span>
                                    <span>
                                        Professionnels :
                                        <strong class="{{ ($soldeChef['reste_professionnels'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($soldeChef['reste_professionnels'] ?? 0, 0, '', ' ') }} FCFA
                                        </strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small text-uppercase">Particuliers</div>
                                    <div class="fs-5 fw-bold {{ ($soldeChef['reste_particuliers'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($soldeChef['reste_particuliers'] ?? 0, 0, '', ' ') }} FCFA
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small text-uppercase">Professionnels</div>
                                    <div class="fs-5 fw-bold {{ ($soldeChef['reste_professionnels'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($soldeChef['reste_professionnels'] ?? 0, 0, '', ' ') }} FCFA
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-3">
                                <div class="text-muted small">Total montant</div>
                                <div class="fw-semibold">{{ number_format($soldeChef['total_montant'], 0, '', ' ') }} FCFA</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="text-muted small">Montant payé</div>
                                <div class="fw-semibold text-success">{{ number_format($soldeChef['montant_paye'], 0, '', ' ') }} FCFA</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="text-muted small">Reste à payer</div>
                                <div class="fw-semibold {{ $soldeChef['reste_a_payer'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($soldeChef['reste_a_payer'], 0, '', ' ') }} FCFA
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="text-muted small">Financement disponible</div>
                                <div class="fw-semibold text-primary">{{ number_format($soldeChef['solde_financement'], 0, '', ' ') }} FCFA</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('comptes-groupes.show', $groupe) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-box-arrow-up-right"></i> Ouvrir le compte groupe (paiements)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-funnel me-1"></i> Rechercher un agent
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('groupes.show', $groupe) }}" class="row g-2 align-items-end">
                            <input type="hidden" name="section" value="agents">

                            <div class="col-md-4 col-lg-3">
                                <label for="search" class="form-label small text-muted text-uppercase mb-1">Nom / prénom</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm"
                                    value="{{ $filters['search'] ?? '' }}"
                                    placeholder="Ex: Aka, Georges…">
                            </div>

                            <div class="col-md-4 col-lg-3">
                                <label for="numero" class="form-label small text-muted text-uppercase mb-1">N° agent / contact</label>
                                <input type="text" name="numero" id="numero" class="form-control form-control-sm"
                                    value="{{ $filters['numero'] ?? '' }}"
                                    placeholder="Ex: AGT-26-PGF… ou 07…">
                            </div>

                            <div class="col-md-4 col-lg-2">
                                <label for="sous_groupe" class="form-label small text-muted text-uppercase mb-1">Sous-groupe</label>
                                <select name="sous_groupe" id="sous_groupe" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="{{ \App\Models\Agent::SOUS_GROUPE_PARTICULIER }}"
                                        @selected(($filters['sous_groupe'] ?? '') === \App\Models\Agent::SOUS_GROUPE_PARTICULIER)>
                                        Particuliers
                                    </option>
                                    <option value="{{ \App\Models\Agent::SOUS_GROUPE_PROFESSIONNEL }}"
                                        @selected(($filters['sous_groupe'] ?? '') === \App\Models\Agent::SOUS_GROUPE_PROFESSIONNEL)>
                                        Professionnels
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-12 col-lg-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search"></i> Rechercher
                                </button>
                                <a href="{{ route('groupes.show', ['groupe' => $groupe, 'section' => 'agents']) }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="row mb-3">
            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group btn-group-sm" role="group" aria-label="Filtrer par sous-groupe">
                    <a href="{{ route('groupes.show', array_filter([
                            'groupe' => $groupe,
                            'section' => 'agents',
                            'search' => ($filters['search'] ?? '') ?: null,
                            'numero' => ($filters['numero'] ?? '') ?: null,
                        ])) }}"
                        class="btn {{ ($sousGroupe ?? '') === '' ? 'btn-dark' : 'btn-outline-dark' }}">
                        Tous ({{ $counts['total'] }})
                    </a>
                    <a href="{{ route('groupes.show', array_filter([
                            'groupe' => $groupe,
                            'section' => 'agents',
                            'sous_groupe' => \App\Models\Agent::SOUS_GROUPE_PARTICULIER,
                            'search' => ($filters['search'] ?? '') ?: null,
                            'numero' => ($filters['numero'] ?? '') ?: null,
                        ])) }}"
                        class="btn {{ ($sousGroupe ?? '') === \App\Models\Agent::SOUS_GROUPE_PARTICULIER ? 'btn-info' : 'btn-outline-info' }}">
                        Particuliers ({{ $counts['particuliers'] }})
                    </a>
                    <a href="{{ route('groupes.show', array_filter([
                            'groupe' => $groupe,
                            'section' => 'agents',
                            'sous_groupe' => \App\Models\Agent::SOUS_GROUPE_PROFESSIONNEL,
                            'search' => ($filters['search'] ?? '') ?: null,
                            'numero' => ($filters['numero'] ?? '') ?: null,
                        ])) }}"
                        class="btn {{ ($sousGroupe ?? '') === \App\Models\Agent::SOUS_GROUPE_PROFESSIONNEL ? 'btn-primary' : 'btn-outline-primary' }}">
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
                                            Aucun agent trouvé pour ces critères.
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
    @endif
@endsection
