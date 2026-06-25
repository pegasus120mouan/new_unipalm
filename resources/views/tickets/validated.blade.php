@extends('layout.main')

@section('title', 'Tickets validés')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Tickets validés</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tickets validés</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @include('tickets.partials.search-filters', [
        'searchAction' => route('tickets.validated'),
        'resetAction' => route('tickets.validated'),
    ])

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span>Tickets validés</span>
                        <span class="text-muted ms-2">(prix unitaire et validation renseignés)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">{{ $tickets->total() }} ticket(s)</span>
                        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Tous les tickets
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    @include('tickets.partials.table', [
                        'emptyMessage' => 'Aucun ticket validé trouvé.',
                    ])
                </div>
            </div>
        </div>
    </section>
@endsection
