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
                                    <td colspan="4" class="text-center text-muted py-4">
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
@endsection
