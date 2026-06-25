@extends('layout.main')

@section('title')
    Agent — {{ $agent->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Fiche agent</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('agents.index') }}">Agents</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $agent->numero_agent }}</li>
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

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 56px; height: 56px; font-size: 1.4rem; font-weight: 700;">
                            {{ strtoupper(substr($agent->nom, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $agent->full_name }}</h4>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-primary">{{ $agent->numero_agent }}</span>
                                @if ($agent->groupe)
                                    <span class="text-muted small">
                                        Groupe :
                                        <a href="{{ route('groupes.show', $agent->groupe) }}">{{ $agent->groupe->full_name }}</a>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Retour à la liste
                        </a>
                        <a href="{{ route('comptes-agents.show', $agent) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-wallet2"></i> Compte agent
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Synthèse</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tickets</span>
                        <span class="fw-semibold">{{ number_format($stats['tickets'], 0, '', ' ') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Bordereaux</span>
                        <span class="fw-semibold">{{ number_format($stats['bordereaux'], 0, '', ' ') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Date de création</span>
                        <span class="fw-semibold">{{ $agent->date_ajout?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Ajouté par</span>
                        <span class="fw-semibold">{{ $agent->createur?->full_name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-pencil-square"></i> Modifier les informations
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('agents.update', $agent) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                                    value="{{ old('nom', $agent->nom) }}" required>
                                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" name="prenom" id="prenom" class="form-control @error('prenom') is-invalid @enderror"
                                    value="{{ old('prenom', $agent->prenom) }}" required>
                                @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="contact" class="form-label">Contact</label>
                                <input type="text" name="contact" id="contact" class="form-control @error('contact') is-invalid @enderror"
                                    value="{{ old('contact', $agent->contact) }}" required>
                                @error('contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="id_chef" class="form-label">Groupe / Chef d'équipe</label>
                                <select name="id_chef" id="id_chef" class="form-select @error('id_chef') is-invalid @enderror" required>
                                    @foreach ($groupes as $groupe)
                                        <option value="{{ $groupe->id_chef }}" @selected((int) old('id_chef', $agent->id_chef) === (int) $groupe->id_chef)>
                                            {{ $groupe->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_chef')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="code_pin" class="form-label">
                                    Nouveau code PIN <span class="text-muted fw-normal">(optionnel, 6 chiffres)</span>
                                </label>
                                <input type="text" name="code_pin" id="code_pin" maxlength="6" inputmode="numeric"
                                    class="form-control @error('code_pin') is-invalid @enderror"
                                    value="{{ old('code_pin') }}" placeholder="Laisser vide pour ne pas changer">
                                @error('code_pin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle"></i>
                                    Le numéro agent <strong>{{ $agent->numero_agent }}</strong> n'est pas modifiable.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                            <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
