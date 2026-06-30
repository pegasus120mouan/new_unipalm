@extends('layout.main')

@section('title', 'Liste des demandes')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Liste des demandes</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Demandes de sortie</li>
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
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Total</h6>
                    <h4 class="mb-0">{{ number_format($stats['total'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">En attente</h6>
                    <h4 class="mb-0 text-warning">{{ number_format($stats['en_attente'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Approuvées</h6>
                    <h4 class="mb-0 text-success">{{ number_format($stats['approuve'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">Payées</h6>
                    <h4 class="mb-0 text-info">{{ number_format($stats['paye'], 0, ',', ' ') }}</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDemandeModal">
                            <i class="bi bi-plus-circle"></i> Enregistrer une demande
                        </button>
                        <button type="button" class="btn btn-danger" disabled title="Bientôt disponible">
                            <i class="bi bi-printer-fill"></i> Imprimer la liste
                        </button>
                        <a href="#demande-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher une demande
                        </a>
                        <button type="button" class="btn btn-dark" disabled title="Bientôt disponible">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i> Exporter la liste
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row" id="demande-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('sorties.demandes.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="N° demande ou motif..." value="{{ $search }}">
                        </div>
                        <div class="col-md-2">
                            <label for="statut" class="form-label">Statut</label>
                            <select name="statut" id="statut" class="form-select">
                                <option value="all" @selected($statut === 'all' || $statut === '')>Tous</option>
                                <option value="en_attente" @selected($statut === 'en_attente')>En attente</option>
                                <option value="approuve" @selected($statut === 'approuve')>Approuvé</option>
                                <option value="rejete" @selected($statut === 'rejete')>Rejeté</option>
                                <option value="paye" @selected($statut === 'paye')>Payé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $dateFin }}">
                        </div>
                        <div class="col-md-3 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                            <a href="{{ route('sorties.demandes.index') }}" class="btn btn-secondary">
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
                    <span>Demandes de sortie</span>
                    <span class="text-muted">{{ $demandes->total() }} demande(s)</span>
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
                                <th>Date approbation</th>
                                <th>Approuvé par</th>
                                <th>Date paiement</th>
                                <th>Payé par</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($demandes as $demande)
                                <tr>
                                    <td>{{ $demande->date_demande?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="fw-semibold">{{ $demande->numero_demande }}</td>
                                    <td>{{ number_format((float) $demande->montant, 0, ',', ' ') }} FCFA</td>
                                    <td style="max-width: 220px;">
                                        <span class="text-truncate d-inline-block" style="max-width: 220px;"
                                            title="{{ $demande->motif }}">
                                            {{ $demande->motif }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $demande->statut_badge_class }}">
                                            {{ $demande->statut_label }}
                                        </span>
                                        @if ($demande->statut === 'rejete' && $demande->motif_refus)
                                            <div class="small text-muted mt-1" title="{{ $demande->motif_refus }}">
                                                Refus : {{ Str::limit($demande->motif_refus, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($demande->date_approbation)
                                            {{ $demande->date_approbation->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">En attente</span>
                                        @endif
                                    </td>
                                    <td>{{ $demande->approbateur?->full_name ?? '-' }}</td>
                                    <td>
                                        @if ($demande->date_paiement)
                                            {{ $demande->date_paiement->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">En attente</span>
                                        @endif
                                    </td>
                                    <td>{{ $demande->payeur?->full_name ?? '-' }}</td>
                                    <td class="text-end">
                                        @if ($demande->isEditable())
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#editDemandeModal"
                                                    data-demande="{{ json_encode([
                                                        'id' => $demande->id_demande,
                                                        'numero' => $demande->numero_demande,
                                                        'montant' => (float) $demande->montant,
                                                        'motif' => $demande->motif,
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteDemandeModal"
                                                    data-id="{{ $demande->id_demande }}"
                                                    data-numero="{{ $demande->numero_demande }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        @if ($search !== '' || $statut !== 'all' || $dateDebut || $dateFin)
                                            Aucune demande ne correspond à vos critères.
                                        @else
                                            Aucune demande enregistrée pour le moment.
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

    <div class="modal fade" id="addDemandeModal" tabindex="-1" aria-labelledby="addDemandeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('sorties.demandes.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDemandeModalLabel">Enregistrer une demande</h5>
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
                            <label for="add_motif" class="form-label">Motif de la sortie</label>
                            <textarea class="form-control" id="add_motif" name="motif" rows="3"
                                placeholder="Décrivez le motif de votre demande de sortie" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editDemandeModal" tabindex="-1" aria-labelledby="editDemandeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editDemandeForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editDemandeModalLabel">Modifier la demande</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3" id="editDemandeNumero"></p>
                        <div class="mb-3">
                            <label for="edit_montant_display" class="form-label">Montant</label>
                            <div class="input-group">
                                <input type="text" id="edit_montant_display" class="form-control"
                                    data-amount-input inputmode="numeric" autocomplete="off"
                                    placeholder="Ex : 125 000" required>
                                <input type="hidden" name="montant" id="edit_montant" data-amount-target>
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_motif" class="form-label">Motif</label>
                            <textarea class="form-control" id="edit_motif" name="motif" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteDemandeModal" tabindex="-1" aria-labelledby="deleteDemandeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteDemandeForm" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteDemandeModalLabel">Supprimer la demande</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirmer la suppression de la demande <strong id="deleteDemandeNumero"></strong> ?</p>
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
        document.getElementById('editDemandeModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const demande = JSON.parse(button.getAttribute('data-demande'));
            const montantDigits = String(Math.round(demande.montant));

            document.getElementById('editDemandeForm').action = @json(url('/sorties/demandes')) + '/' + demande.id;
            document.getElementById('editDemandeNumero').textContent = 'N° ' + demande.numero;
            document.getElementById('edit_montant_display').value = window.UnipalmAmount.format(montantDigits);
            document.getElementById('edit_montant').value = montantDigits;
            document.getElementById('edit_motif').value = demande.motif;
        });

        document.getElementById('deleteDemandeModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const numero = button.getAttribute('data-numero');

            document.getElementById('deleteDemandeForm').action = @json(url('/sorties/demandes')) + '/' + id;
            document.getElementById('deleteDemandeNumero').textContent = numero;
        });
    </script>
@endpush
