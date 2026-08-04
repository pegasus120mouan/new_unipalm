@extends('layout.main')

@section('title', 'Financement des usines')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Financement des usines</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Financement des usines</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <section class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('usines.financements.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-5">
                            <label for="search" class="form-label">Usine</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Rechercher une usine..." value="{{ $search }}">
                        </div>
                        <div class="col-md-6 col-lg-4 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('usines.financements.index') }}" class="btn btn-secondary">
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
                    <span>Liste des usines</span>
                    <span class="text-muted">{{ $usines->total() }} usine(s)</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Usine</th>
                                <th class="text-center">Nombre</th>
                                <th class="text-end">Total financé</th>
                                <th class="text-center" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($usines as $usine)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('usines.financements.show', $usine) }}" class="text-decoration-none">
                                            <i class="bi bi-building text-primary me-1"></i>
                                            {{ $usine->nom_usine }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ number_format((int) $usine->nombre_financements, 0, '', ' ') }}</span>
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format((float) $usine->total_financement, 0, '', ' ') }} FCFA
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('usines.financements.show', $usine) }}" class="btn btn-sm btn-outline-primary">
                                            Voir
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        @if ($search !== '')
                                            Aucune usine ne correspond à votre recherche.
                                        @else
                                            Aucune usine enregistrée.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($usines->hasPages())
                    <div class="card-footer">
                        {{ $usines->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
