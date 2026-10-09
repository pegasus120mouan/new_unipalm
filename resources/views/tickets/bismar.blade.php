@extends('layout.main')

@section('title', 'Tickets BISMAR')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Tickets BISMAR</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tickets BISMAR</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @include('tickets.partials.search-filters', [
        'searchAction' => route('tickets.bismar'),
        'resetAction' => route('tickets.bismar'),
        'showNumeroTicketFilter' => true,
        'showCodeTransfertFilter' => true,
    ])

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span>Tickets BISMAR</span>
                        <span class="text-muted ms-2">(code transfert renseigné)</span>
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
                        'showCodeTransfert' => true,
                        'emptyMessage' => 'Aucun ticket BISMAR trouvé.',
                    ])
                </div>
            </div>
        </div>
    </section>
@endsection
