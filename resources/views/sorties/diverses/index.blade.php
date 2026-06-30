@extends('layout.main')

@section('title', 'Sorties diverses')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Sorties diverses</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sorties diverses</li>
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
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row">
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Solde caisse</h6>
                    <h4 class="mb-0 text-primary">{{ number_format($stats['solde_caisse'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Nombre de sorties</h6>
                    <h4 class="mb-0">{{ number_format($stats['total_sorties'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Total sorties diverses</h6>
                    <h4 class="mb-0 text-danger">{{ number_format($stats['total_montant'], 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addSortieModal">
                            <i class="bi bi-dash-circle"></i> Nouvelle sortie diverse
                        </button>
                        <a href="#sortie-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher
                        </a>
                        <button type="button" class="btn btn-secondary" disabled title="Bientôt disponible">
                            <i class="bi bi-printer-fill"></i> Imprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="sortie-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('sorties.diverses.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="N° sortie ou motifs..." value="{{ $search }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $dateFin }}">
                        </div>
                        <div class="col-md-2 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                        </div>
                    </form>
                    @if ($search !== '' || $dateDebut || $dateFin)
                        <div class="mt-2">
                            <a href="{{ route('sorties.diverses.index') }}" class="btn btn-sm btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Historique des sorties diverses</span>
                    <span class="text-muted">{{ $sorties->total() }} sortie(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>N° Sortie</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Motifs</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sorties as $sortie)
                                <tr>
                                    <td class="fw-semibold">{{ $sortie->numero_sorties }}</td>
                                    <td>{{ $sortie->date_sortie?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="text-danger fw-semibold">
                                        {{ number_format((float) $sortie->montant, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td style="max-width: 320px;">
                                        <span class="text-truncate d-inline-block" style="max-width: 320px;"
                                            title="{{ $sortie->motifs }}">
                                            {{ $sortie->motifs }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#deleteSortieModal"
                                            data-id="{{ $sortie->id_sorties }}"
                                            data-numero="{{ $sortie->numero_sorties }}"
                                            data-montant="{{ number_format((float) $sortie->montant, 0, ',', ' ') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        @if ($search !== '' || $dateDebut || $dateFin)
                                            Aucune sortie diverse ne correspond à vos critères.
                                        @else
                                            Aucune sortie diverse enregistrée pour le moment.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($sorties->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $sorties->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addSortieModal" tabindex="-1" aria-labelledby="addSortieModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('sorties.diverses.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSortieModalLabel">Nouvelle sortie diverse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_montant_display" class="form-label">Montant</label>
                            <div class="input-group">
                                <input type="text" id="add_montant_display" class="form-control"
                                    data-amount-input inputmode="numeric" autocomplete="off"
                                    placeholder="Ex : 125 000" required>
                                <input type="hidden" name="montant" id="add_montant" data-amount-target>
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="add_motifs" class="form-label">Motifs de la sortie</label>
                            <textarea class="form-control" id="add_motifs" name="motifs" rows="3"
                                placeholder="Décrivez les motifs de cette sortie..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteSortieModal" tabindex="-1" aria-labelledby="deleteSortieModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteSortieForm" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteSortieModalLabel">Supprimer la sortie</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirmer la suppression de la sortie <strong id="deleteSortieNumero"></strong> ?</p>
                        <p class="mb-0 text-muted">Montant : <strong id="deleteSortieMontant"></strong> FCFA</p>
                        <p class="small text-warning mt-2 mb-0">Le montant sera recrédité sur le solde caisse.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    <script>
        document.getElementById('deleteSortieModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const numero = button.getAttribute('data-numero');
            const montant = button.getAttribute('data-montant');

            document.getElementById('deleteSortieForm').action = @json(url('/sorties/diverses')) + '/' + id;
            document.getElementById('deleteSortieNumero').textContent = numero;
            document.getElementById('deleteSortieMontant').textContent = montant;
        });
    </script>
@endpush
