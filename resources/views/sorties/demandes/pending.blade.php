@extends('layout.main')

@section('title', 'Demandes en attente')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Demandes en attente</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sorties.demandes.index') }}">Demandes de sortie</a></li>
                <li class="breadcrumb-item active" aria-current="page">En attente</li>
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

    <section class="row" id="demande-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('sorties.pending.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="N° demande ou motif..." value="{{ $search }}">
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
                            <a href="{{ route('sorties.pending.index') }}" class="btn btn-sm btn-secondary">
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
                    <span>Demandes en attente d'approbation</span>
                    <span class="badge bg-warning text-dark">{{ $demandes->total() }} demande(s)</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>N° Demande</th>
                                <th>Montant</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($demandes as $demande)
                                <tr>
                                    <td>{{ $demande->date_demande?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="fw-semibold">{{ $demande->numero_demande }}</td>
                                    <td>{{ number_format((float) $demande->montant, 0, ',', ' ') }} FCFA</td>
                                    <td style="max-width: 280px;">
                                        <span class="text-truncate d-inline-block" style="max-width: 280px;"
                                            title="{{ $demande->motif }}">
                                            {{ $demande->motif }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $demande->statut_badge_class }}">
                                            {{ $demande->statut_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if ($demande->isApprovable())
                                            <div class="d-inline-flex gap-2">
                                                <button type="button" class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#approveDemandeModal"
                                                    data-id="{{ $demande->id_demande }}"
                                                    data-numero="{{ $demande->numero_demande }}"
                                                    data-montant="{{ number_format((float) $demande->montant, 0, ',', ' ') }}">
                                                    <i class="bi bi-check-circle"></i> Approuver
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#rejectDemandeModal"
                                                    data-id="{{ $demande->id_demande }}"
                                                    data-numero="{{ $demande->numero_demande }}"
                                                    data-montant="{{ number_format((float) $demande->montant, 0, ',', ' ') }}">
                                                    <i class="bi bi-x-circle"></i> Refuser
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        @if ($search !== '' || $dateDebut || $dateFin)
                                            Aucune demande en attente ne correspond à vos critères.
                                        @else
                                            Aucune demande en attente pour le moment.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($demandes->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $demandes->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="approveDemandeModal" tabindex="-1" aria-labelledby="approveDemandeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="approveDemandeForm" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveDemandeModalLabel">Approuver la demande</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirmer l'approbation de la demande <strong id="approveDemandeNumero"></strong> ?</p>
                        <p class="mb-0 text-muted">Montant : <strong id="approveDemandeMontant"></strong> FCFA</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Approuver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectDemandeModal" tabindex="-1" aria-labelledby="rejectDemandeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="rejectDemandeForm" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectDemandeModalLabel">Refuser la demande</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirmer le refus de la demande <strong id="rejectDemandeNumero"></strong> ?</p>
                        <p class="text-muted">Montant : <strong id="rejectDemandeMontant"></strong> FCFA</p>
                        <div class="mb-0">
                            <label for="motif_refus" class="form-label">Motif du refus <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('motif_refus') is-invalid @enderror"
                                id="motif_refus" name="motif_refus" rows="3"
                                placeholder="Indiquez la raison du refus..." required>{{ old('motif_refus') }}</textarea>
                            @error('motif_refus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Refuser
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('approveDemandeModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const numero = button.getAttribute('data-numero');
            const montant = button.getAttribute('data-montant');

            document.getElementById('approveDemandeForm').action = @json(url('/sorties/en-attente')) + '/' + id + '/approuver';
            document.getElementById('approveDemandeNumero').textContent = numero;
            document.getElementById('approveDemandeMontant').textContent = montant;
        });

        document.getElementById('rejectDemandeModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const numero = button.getAttribute('data-numero');
            const montant = button.getAttribute('data-montant');

            document.getElementById('rejectDemandeForm').action = @json(url('/sorties/en-attente')) + '/' + id + '/refuser';
            document.getElementById('rejectDemandeNumero').textContent = numero;
            document.getElementById('rejectDemandeMontant').textContent = montant;
            document.getElementById('motif_refus').value = '';
        });
    </script>
@endpush
