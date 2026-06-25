@extends('layout.main')

@section('title', 'Recherche avancée')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Recherche avancée</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Recherche avancée</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @include('tickets.partials.advanced-search-form', [
        'searchAction' => route('tickets.search'),
        'resetAction' => route('tickets.search'),
    ])

    @if ($isSearchRequested)
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Résultats de la recherche</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">{{ $tickets->total() }} ticket(s)</span>
                            <button type="button" class="btn btn-sm btn-primary" disabled title="Bientôt disponible">
                                <i class="bi bi-printer"></i> Imprimer
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive">
                        @include('tickets.partials.search-results-table', [
                            'emptyMessage' => 'Aucun ticket ne correspond à vos critères.',
                        ])
                    </div>
                </div>
            </div>
        </section>
    @else
        <div class="alert alert-light border text-center text-muted">
            Saisissez vos critères de recherche et cliquez sur <strong>Rechercher</strong>.
        </div>
    @endif

    <div class="modal fade" id="viewTicketModal" tabindex="-1" aria-labelledby="viewTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewTicketModalLabel">Détails du ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Date de réception :</strong> <span id="view_date_reception">-</span></p>
                            <p class="mb-2"><strong>Date du ticket :</strong> <span id="view_date_ticket">-</span></p>
                            <p class="mb-2"><strong>N° ticket :</strong> <span id="view_numero">-</span></p>
                            <p class="mb-2"><strong>Usine :</strong> <span id="view_usine">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Poids :</strong> <span id="view_poids">-</span></p>
                            <p class="mb-2"><strong>Prix unitaire :</strong> <span id="view_prix">-</span></p>
                            <p class="mb-2"><strong>Agent :</strong> <span id="view_agent">-</span></p>
                            <p class="mb-2"><strong>Véhicule :</strong> <span id="view_vehicule">-</span></p>
                            <p class="mb-0"><strong>Créé par :</strong> <span id="view_createur">-</span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.view-ticket-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('viewTicketModalLabel').textContent =
                        `Détails du ticket #${button.dataset.numero}`;
                    document.getElementById('view_date_reception').textContent = button.dataset.dateReception || '-';
                    document.getElementById('view_date_ticket').textContent = button.dataset.dateTicket || '-';
                    document.getElementById('view_numero').textContent = button.dataset.numero || '-';
                    document.getElementById('view_usine').textContent = button.dataset.usine || '-';
                    document.getElementById('view_poids').textContent = button.dataset.poids || '-';
                    document.getElementById('view_prix').textContent = button.dataset.prix || '-';
                    document.getElementById('view_agent').textContent = button.dataset.agent || '-';
                    document.getElementById('view_vehicule').textContent = button.dataset.vehicule || '-';
                    document.getElementById('view_createur').textContent = button.dataset.createur || '-';
                });
            });
        });
    </script>
@endsection
