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
