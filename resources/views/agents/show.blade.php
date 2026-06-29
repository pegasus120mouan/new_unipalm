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

    @if ($errors->has('pont_id'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('pont_id') }}
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
                                @if ($agent->isProfessionnel())
                                    <span class="badge bg-primary">Professionnels</span>
                                @else
                                    <span class="badge bg-info">Particuliers</span>
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
                        <span class="text-muted">Ponts gérés</span>
                        <span class="fw-semibold">{{ number_format($stats['ponts'], 0, '', ' ') }}</span>
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
                                <label class="form-label">Sous-groupe</label>
                                <div class="d-flex flex-wrap gap-3 pt-2">
                                    @foreach ($sousGroupes as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input @error('sous_groupe') is-invalid @enderror"
                                                type="radio" name="sous_groupe" id="edit_sous_groupe_{{ $value }}"
                                                value="{{ $value }}"
                                                @checked(old('sous_groupe', $agent->sous_groupe ?? \App\Models\Agent::SOUS_GROUPE_PARTICULIER) === $value) required>
                                            <label class="form-check-label" for="edit_sous_groupe_{{ $value }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('sous_groupe')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

    <section class="row mb-4" id="agent-ponts">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-signpost-split"></i> Ponts associés</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">{{ $ponts->count() }} pont(s)</span>
                        @if ($availablePonts->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#associatePontModal">
                                <i class="bi bi-link-45deg"></i> Associer un pont
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body table-responsive">
                    @if ($ponts->isNotEmpty())
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Nom du pont</th>
                                    <th>Type</th>
                                    <th>Coopérative</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ponts as $pont)
                                    <tr>
                                        <td><code>{{ $pont->code_pont }}</code></td>
                                        <td class="fw-semibold">{{ $pont->nom_pont }}</td>
                                        <td>{{ $pont->typePont?->libelle ?? '—' }}</td>
                                        <td>{{ $pont->cooperatif ?: '—' }}</td>
                                        <td>
                                            @if ($pont->isActive())
                                                <span class="badge bg-success">Actif</span>
                                            @else
                                                <span class="badge bg-danger">Inactif</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted text-center mb-0 py-3">
                            Aucun pont associé à cet agent.
                            @if ($availablePonts->isNotEmpty())
                                Cliquez sur <strong>Associer un pont</strong> pour en lier un.
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($availablePonts->isNotEmpty())
        <div class="modal fade" id="associatePontModal" tabindex="-1" aria-labelledby="associatePontModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('agents.associate-pont', $agent) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="associatePontModalLabel">
                                <i class="bi bi-link-45deg"></i> Associer un pont à {{ $agent->full_name }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3 position-relative">
                                <label for="pont_search" class="form-label">Rechercher un pont</label>
                                <input type="text" id="pont_search" class="form-control"
                                    placeholder="Code ou nom du pont..." autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label for="pont_id" class="form-label">Pont *</label>
                                <select name="pont_id" id="pont_id" class="form-select @error('pont_id') is-invalid @enderror" required size="8">
                                    <option value="">— Sélectionner un pont —</option>
                                    @foreach ($availablePonts as $pont)
                                        <option value="{{ $pont->id_pont }}"
                                            data-search="{{ strtolower($pont->code_pont.' '.$pont->nom_pont) }}"
                                            @selected(old('pont_id') == $pont->id_pont)>
                                            {{ $pont->code_pont }} — {{ $pont->nom_pont }}
                                            @if ($pont->typePont)
                                                ({{ $pont->typePont->libelle }})
                                            @endif
                                            @if ($pont->agent && (int) $pont->id_agent !== (int) $agent->id_agent)
                                                — gérant actuel : {{ $pont->agent->full_name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('pont_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    Un pont déjà géré par un autre agent sera réassigné à {{ $agent->full_name }}.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-link-45deg"></i> Associer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@if ($availablePonts->isNotEmpty())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('pont_search');
                const select = document.getElementById('pont_id');

                if (!searchInput || !select) {
                    return;
                }

                searchInput.addEventListener('input', function () {
                    const term = searchInput.value.trim().toLowerCase();
                    let firstVisible = null;

                    Array.from(select.options).forEach(function (option, index) {
                        if (index === 0) {
                            option.hidden = false;
                            return;
                        }

                        const match = !term || (option.dataset.search || '').includes(term);
                        option.hidden = !match;

                        if (match && !firstVisible) {
                            firstVisible = option;
                        }
                    });

                    if (firstVisible && term) {
                        select.value = firstVisible.value;
                    }
                });

                @if ($errors->has('pont_id'))
                    new bootstrap.Modal(document.getElementById('associatePontModal')).show();
                @endif
            });
        </script>
    @endpush
@endif
