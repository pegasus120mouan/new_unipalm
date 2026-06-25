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
                                <th>Nombre d'agents</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groupes as $groupe)
                                <tr>
                                    <td>
                                        <a href="{{ route('groupes.show', $groupe) }}">{{ $groupe->full_name }}</a>
                                    </td>
                                    <td>{{ $groupe->agents_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">
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
