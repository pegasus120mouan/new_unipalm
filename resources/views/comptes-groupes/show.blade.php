@extends('layout.main')

@section('title')
    Compte groupe — {{ $groupe->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Compte groupe</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('comptes-groupes.index') }}">Comptes groupes</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $groupe->full_name }}</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @inject('compteAgentService', 'App\Services\CompteAgentService')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-check-circle fs-4"></i>
                <div>
                    <strong>{{ session('success') }}</strong>
                    @if (session('paiement_details'))
                        @php $details = session('paiement_details'); @endphp
                        <div class="small mt-2">
                            <div><strong>N° Reçu :</strong> {{ $details['numero_recu'] }}</div>
                            <div><strong>Tickets soldés :</strong> {{ $details['tickets_soldes'] }}
                                | <strong>Partiels :</strong> {{ $details['tickets_partiels'] }}</div>
                            <div><strong>Reste à payer :</strong> {{ number_format($details['reste_a_payer'], 0, '', ' ') }} FCFA</div>
                            <div><strong>Nouveau solde caisse :</strong> {{ number_format($details['nouveau_solde'], 0, '', ' ') }} FCFA</div>
                        </div>
                    @endif
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if ($errors->has('paiement') && ! $errors->has('montant_paiement'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('paiement') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: 700;">
                            {{ strtoupper(substr($groupe->nom, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="mb-1 text-uppercase">
                                {{ $groupe->full_name }}
                                <span class="badge bg-light text-dark border ms-1 align-middle">Chef #{{ $groupe->id_chef }}</span>
                            </h4>
                            <div class="text-muted small">
                                Agents : <strong>{{ $counts['agents'] }} agent(s)</strong> sous sa responsabilité
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('comptes-groupes.index', array_filter([
                        'date_debut' => $filters['date_debut'] ?: null,
                        'date_fin' => $filters['date_fin'] ?: null,
                    ])) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4 align-items-stretch">
        <div class="col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Synthèse</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tickets</span>
                        <span class="fw-semibold">{{ number_format($counts['tickets'], 0, '', ' ') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Bordereaux</span>
                        <span class="fw-semibold">{{ number_format($counts['bordereaux'], 0, '', ' ') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Agents</span>
                        <span class="fw-semibold">{{ $counts['agents'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8 col-lg-9">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Synthèse financière</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 px-3 py-3 h-100 border-start border-4 border-secondary">
                                <div class="text-muted small mb-1">Total montant</div>
                                <div class="fw-bold">{{ number_format($stats['montant_total'], 0, '', ' ') }} FCFA</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 px-3 py-3 h-100 border-start border-4 border-success">
                                <div class="text-muted small mb-1">Montant payé</div>
                                <div class="fw-bold text-success">{{ number_format($stats['montant_paye'], 0, '', ' ') }} FCFA</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 px-3 py-3 h-100 border-start border-4 border-danger">
                                <div class="text-muted small mb-1">Reste à payer</div>
                                <div class="fw-bold text-danger">{{ number_format($stats['montant_du'], 0, '', ' ') }} FCFA</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12 d-flex flex-wrap gap-2">
            <a href="#tickets-section" class="btn btn-primary btn-sm">
                <i class="bi bi-ticket-perforated"></i> Tickets
            </a>
            @if ($stats['montant_du'] > 0)
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#groupePaymentModal">
                    <i class="bi bi-cash-coin"></i> Effectuer un paiement
                </button>
            @endif
        </div>
    </section>

    <section class="row" id="tickets-section">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-ticket-perforated"></i> Tickets du chef d'équipe
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('comptes-groupes.show', $groupe) }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4 col-lg-3">
                                <label for="statut" class="form-label small text-uppercase text-muted">Statut du ticket</label>
                                <select name="statut" id="statut" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="paye" @selected($filters['statut'] === 'paye')>Payé</option>
                                    <option value="non_paye" @selected($filters['statut'] === 'non_paye')>Non payé</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label for="date_debut" class="form-label small text-uppercase text-muted">Date début</label>
                                <input type="date" name="date_debut" id="date_debut" class="form-control form-control-sm"
                                    value="{{ $filters['date_debut'] }}">
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label for="date_fin" class="form-label small text-uppercase text-muted">Date fin</label>
                                <input type="date" name="date_fin" id="date_fin" class="form-control form-control-sm"
                                    value="{{ $filters['date_fin'] }}">
                            </div>
                            <div class="col-md-12 col-lg-5 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search"></i> Filtrer
                                </button>
                                <a href="{{ route('comptes-groupes.show', $groupe) }}" class="btn btn-outline-secondary btn-sm">
                                    Réinitialiser
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date ticket</th>
                                    <th>N° Ticket</th>
                                    <th>Agent</th>
                                    <th>Usine</th>
                                    <th class="text-end">Poids</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-end">Payé</th>
                                    <th class="text-end">Reste à payer</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tickets as $ticket)
                                    @php
                                        $montantTicket = $ticket->montant_paie !== null
                                            ? (float) $ticket->montant_paie
                                            : (($ticket->prix_unitaire && $ticket->poids)
                                                ? (float) $ticket->prix_unitaire * (float) $ticket->poids
                                                : null);
                                        $montantPaye = (float) ($ticket->montant_payer ?? 0);
                                        $montantReste = $ticket->montant_reste !== null
                                            ? (float) $ticket->montant_reste
                                            : ($montantTicket !== null ? max($montantTicket - $montantPaye, 0) : null);
                                        $statusKey = $compteAgentService->ticketPaymentStatusKey(
                                            $ticket->montant_payer,
                                            $ticket->montant_reste,
                                            $ticket->date_paie,
                                        );
                                        $statusLabel = $compteAgentService->ticketPaymentStatusLabel($statusKey);
                                        $statusClass = match ($statusKey) {
                                            'solde' => 'bg-success',
                                            'en_cours' => 'bg-warning text-dark',
                                            default => 'bg-warning text-dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="fw-semibold">{{ $ticket->numero_ticket ?? '—' }}</td>
                                        <td>
                                            @if ($ticket->agent)
                                                <a href="{{ route('comptes-agents.show', $ticket->agent->id_agent) }}" class="text-decoration-none">
                                                    {{ $ticket->agent->full_name }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $ticket->usine?->nom_usine ?? '—' }}</td>
                                        <td class="text-end">{{ $ticket->poids ? number_format($ticket->poids, 0, '', ' ') : '—' }}</td>
                                        <td class="text-end">
                                            {{ $montantTicket !== null ? number_format($montantTicket, 0, '', ' ').' FCFA' : '—' }}
                                        </td>
                                        <td class="text-end text-success">{{ number_format($montantPaye, 0, '', ' ') }} FCFA</td>
                                        <td class="text-end text-danger">
                                            {{ $montantReste !== null ? number_format($montantReste, 0, '', ' ').' FCFA' : '—' }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="bi bi-ticket-perforated fs-1 d-block mb-2 opacity-25"></i>
                                            Aucun ticket trouvé pour ce chef d'équipe.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($tickets->hasPages())
                        <div class="mt-3">
                            {{ $tickets->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($stats['montant_du'] > 0)
        @include('comptes-groupes.partials.payment-modal')
    @endif
@endsection
