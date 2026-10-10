@extends('layout.main')

@section('title', 'Vérifie de Tickets')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Vérifie de Tickets</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Vérifie de Tickets</li>
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

    <section class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-patch-check"></i> Vérification avant bordereau</span>
                    @if ($actif)
                        <span class="badge bg-success">Activé</span>
                    @else
                        <span class="badge bg-secondary">Désactivé</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Choisissez si un ticket doit être vérifié par le vérificateur avant de pouvoir entrer dans un bordereau.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('tickets.verification-regle.update') }}">
                                @csrf
                                <input type="hidden" name="exiger_verification" value="1">
                                <button type="submit"
                                    class="btn w-100 h-100 text-start p-3 {{ $actif ? 'btn-success' : 'btn-outline-success' }}">
                                    <span class="d-block fw-bold mb-1">Oui</span>
                                    <span class="small">
                                        Tous les tickets doivent avoir le statut vérifié avant d'être dans un bordereau.
                                    </span>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('tickets.verification-regle.update') }}">
                                @csrf
                                <input type="hidden" name="exiger_verification" value="0">
                                <button type="submit"
                                    class="btn w-100 h-100 text-start p-3 {{ $actif ? 'btn-outline-secondary' : 'btn-secondary' }}">
                                    <span class="d-block fw-bold mb-1">Non</span>
                                    <span class="small">
                                        Un bordereau peut être généré même si le ticket n'est pas vérifié.
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
