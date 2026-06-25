@extends('layout.main')

@section('title')
    Compte agent — {{ $agent->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Compte agent</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('comptes-agents.index') }}">Comptes des agents</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $agent->full_name }}</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @inject('compteAgentService', 'App\Services\CompteAgentService')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if ($errors->has('paiement'))
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
                            style="width: 52px; height: 52px; font-size: 1.4rem; font-weight: 700;">
                            {{ strtoupper(substr($agent->nom, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="mb-1 text-uppercase">
                                {{ $agent->full_name }}
                                <span class="badge bg-light text-dark border ms-1 align-middle">Agent #{{ $agent->id_agent }}</span>
                            </h4>
                            <div class="text-muted small">
                                Chef d'équipe :
                                <strong>{{ $agent->groupe?->full_name ?? '—' }}</strong>
                            </div>
                            <div class="text-muted small">
                                Contact :
                                <strong>{{ $agent->contact ?: '—' }}</strong>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('comptes-agents.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4 align-items-stretch">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-uppercase text-muted mb-0">Synthèse</h6>
                            <span class="badge bg-light text-dark border">Agent</span>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Tickets</span>
                                <span class="fw-semibold">{{ $counts['tickets'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Bordereaux</span>
                                <span class="fw-semibold">{{ $counts['bordereaux'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary btn-sm compte-agent-section-btn {{ $activeSection === 'tickets' ? 'active' : '' }}" data-section="tickets">
                                <i class="bi bi-ticket-perforated"></i> Tickets
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm compte-agent-section-btn {{ $activeSection === 'bordereaux' ? 'active' : '' }}" data-section="bordereaux">
                                <i class="bi bi-file-earmark-text"></i> Bordereaux
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#transactionsHistoryModal">
                                <i class="bi bi-clock-history"></i> Historique des transactions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Synthèse financière</h6>
                    <div class="row g-3">
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Total montant</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="bi bi-cash"></i></span>
                                    <div>
                                        <div class="text-muted small">Total</div>
                                        <div class="fw-bold text-success">
                                            {{ number_format($financialStats['total_montant'], 0, '', ' ') }} FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Montant payé</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="bi bi-cash"></i></span>
                                    <div>
                                        <div class="text-muted small">Montant payé</div>
                                        <div class="fw-bold text-success">
                                            {{ number_format($financialStats['montant_paye'], 0, '', ' ') }} FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Reste à payer</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="bi bi-cash"></i></span>
                                    <div>
                                        <div class="text-muted small">Reste à payer</div>
                                        <div class="fw-bold text-success">
                                            {{ number_format($financialStats['reste_a_payer'], 0, '', ' ') }} FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Financement</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="bi bi-wallet2"></i></span>
                                    <div>
                                        <div class="text-muted small">Solde financement</div>
                                        <div class="fw-bold text-success">
                                            @php $soldeFin = (float) $financementStats['solde_financement']; @endphp
                                            {{ $soldeFin > 0 ? '- ' : '' }}{{ number_format(abs($soldeFin), 0, '', ' ') }} FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Prêts</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="bi bi-piggy-bank"></i></span>
                                    <div>
                                        <div class="text-muted small">Solde prêt</div>
                                        <div class="fw-bold text-success">
                                            {{ number_format($pretStats['solde_restant'], 0, '', ' ') }} FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php $soldePositive = $financialStats['solde_global'] >= 0; @endphp
                        <div class="col-md-4 col-12">
                            <div class="border rounded-3 px-3 py-3 h-100 {{ $soldePositive ? 'bg-success text-white' : 'bg-danger text-white' }}">
                                <div class="small mb-1 opacity-75">Solde</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span><i class="bi bi-{{ $soldePositive ? 'arrow-up' : 'arrow-down' }}"></i></span>
                                    <div>
                                        <div class="small opacity-75">Reste à payer - Financement</div>
                                        <div class="fw-bold">
                                            {{ number_format($financialStats['solde_global'], 0, '', ' ') }} FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="tickets-section" class="compte-agent-section {{ $activeSection !== 'tickets' ? 'd-none' : '' }}">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-ticket-perforated"></i> Tickets de l'agent
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('comptes-agents.show', $agent) }}" class="mb-3">
                    <input type="hidden" name="section" value="tickets">
                    <input type="hidden" name="statut_bordereau" value="{{ $filters['statut_bordereau'] }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="statut_ticket" class="form-label small text-uppercase text-muted">Statut du ticket</label>
                            <select name="statut_ticket" id="statut_ticket" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <option value="solde" @selected($filters['statut_ticket'] === 'solde')>Soldé</option>
                                <option value="en_cours" @selected($filters['statut_ticket'] === 'en_cours')>En cours de paiement</option>
                                <option value="non_paye" @selected($filters['statut_ticket'] === 'non_paye')>Non payé</option>
                            </select>
                        </div>
                        <div class="col-md-8 col-lg-9 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                            <a href="{{ route('comptes-agents.show', $agent) }}" class="btn btn-outline-secondary btn-sm">
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
                                <th>Usine</th>
                                <th class="text-end">Poids</th>
                                <th class="text-end">Montant</th>
                                <th class="text-end">Payé</th>
                                <th class="text-end">Reste à payer</th>
                                <th>Bordereau</th>
                                <th>Statut</th>
                                <th>Actions</th>
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
                                @endphp
                                <tr>
                                    <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $ticket->numero_ticket ?? '—' }}</td>
                                    <td>{{ $ticket->usine?->nom_usine ?? '—' }}</td>
                                    <td class="text-end">{{ $ticket->poids ? number_format($ticket->poids, 0, '', ' ') : '—' }}</td>
                                    <td class="text-end">
                                        {{ $montantTicket !== null ? number_format($montantTicket, 0, '', ' ').' FCFA' : '—' }}
                                    </td>
                                    <td class="text-end">{{ number_format($montantPaye, 0, '', ' ') }} FCFA</td>
                                    <td class="text-end">
                                        {{ $montantReste !== null ? number_format($montantReste, 0, '', ' ').' FCFA' : '—' }}
                                    </td>
                                    <td>
                                        @if ($ticket->numero_bordereau)
                                            <a href="{{ route('bordereaux.pdf', $ticket->numero_bordereau) }}" target="_blank">
                                                {{ $ticket->numero_bordereau }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $statusLabel }}</td>
                                    <td>
                                        @if ($ticket->numero_bordereau)
                                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                                                <i class="bi bi-credit-card"></i> Effectuer un paiement
                                            </button>
                                        @elseif ($montantReste !== null && $montantReste <= 0)
                                            <button type="button" class="btn btn-success btn-sm" disabled>
                                                <i class="bi bi-check-circle"></i> Ticket soldé
                                            </button>
                                        @elseif (! $ticket->hasPrixUnitaire())
                                            <button type="button" class="btn btn-warning btn-sm" disabled>
                                                <i class="bi bi-exclamation-circle"></i> Prix unitaire non défini
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-primary btn-sm" disabled title="Bientôt disponible">
                                                <i class="bi bi-credit-card"></i> Effectuer un paiement
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        Aucun ticket trouvé pour cet agent.
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
    </section>

    <section id="bordereaux-section" class="compte-agent-section {{ $activeSection !== 'bordereaux' ? 'd-none' : '' }}">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-text"></i> Bordereaux de l'agent
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3">
                    <i class="bi bi-info-circle"></i>
                    <strong>Important :</strong> Un bordereau doit être approuvé par un superviseur avant de pouvoir effectuer des paiements.
                </div>

                <form method="GET" action="{{ route('comptes-agents.show', $agent) }}" class="mb-3">
                    <input type="hidden" name="section" value="bordereaux">
                    <input type="hidden" name="statut_ticket" value="{{ $filters['statut_ticket'] }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="statut_bordereau" class="form-label small text-uppercase text-muted">Statut du bordereau</label>
                            <select name="statut_bordereau" id="statut_bordereau" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <option value="solde" @selected($filters['statut_bordereau'] === 'solde')>Soldé</option>
                                <option value="en_cours" @selected($filters['statut_bordereau'] === 'en_cours')>En cours de paiement</option>
                                <option value="non_paye" @selected($filters['statut_bordereau'] === 'non_paye')>Non payé</option>
                            </select>
                        </div>
                        <div class="col-md-8 col-lg-9 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                            <a href="{{ route('comptes-agents.show', ['agent' => $agent, 'section' => 'bordereaux']) }}" class="btn btn-outline-secondary btn-sm">
                                Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° Bordereau</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th class="text-center">Nombre de tickets</th>
                                <th class="text-end">Montant total</th>
                                <th class="text-end">Payé</th>
                                <th class="text-end">Reste à payer</th>
                                <th>Approbation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bordereaux as $bordereau)
                                @php
                                    $bStatusKey = $compteAgentService->bordereauPaymentStatusKey(
                                        $bordereau->montant_total,
                                        $bordereau->montant_payer,
                                        $bordereau->montant_reste,
                                        $bordereau->statut_bordereau,
                                    );
                                    $bStatusLabel = $compteAgentService->ticketPaymentStatusLabel($bStatusKey);
                                    $bReste = (float) ($bordereau->montant_reste ?? max((float) $bordereau->montant_total - (float) $bordereau->montant_payer, 0));
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('bordereaux.pdf', $bordereau->numero_bordereau) }}" target="_blank">
                                            {{ $bordereau->numero_bordereau }}
                                        </a>
                                    </td>
                                    <td>{{ $bordereau->date_debut?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $bordereau->date_fin?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $bordereau->tickets_count }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $bordereau->montant_total, 0, '', ' ') }} FCFA</td>
                                    <td class="text-end">{{ number_format((float) $bordereau->montant_payer, 0, '', ' ') }} FCFA</td>
                                    <td class="text-end">{{ number_format($bReste, 0, '', ' ') }} FCFA</td>
                                    <td>
                                        @if ($bordereau->isValidated())
                                            <span class="badge bg-success">Approuvé</span>
                                        @else
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        @endif
                                    </td>
                                    <td>{{ $bStatusLabel }}</td>
                                    <td>
                                        @if (! $bordereau->isValidated())
                                            <button type="button" class="btn btn-warning btn-sm" disabled>
                                                <i class="bi bi-hourglass-split"></i> En attente d'approbation
                                            </button>
                                        @elseif ($bReste <= 0)
                                            <button type="button" class="btn btn-success btn-sm" disabled>
                                                <i class="bi bi-check-circle"></i> Bordereau soldé
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#payerBordereau{{ $bordereau->id_bordereau }}">
                                                <i class="bi bi-credit-card"></i> Effectuer un paiement
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        Aucun bordereau trouvé pour cet agent.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($bordereaux->hasPages())
                    <div class="mt-3">
                        {{ $bordereaux->links() }}
                    </div>
                @endif

                @foreach ($bordereaux as $bordereau)
                    @php
                        $modalReste = (float) ($bordereau->montant_reste ?? max((float) $bordereau->montant_total - (float) $bordereau->montant_payer, 0));
                    @endphp
                    @if ($modalReste > 0)
                        @include('comptes-agents.partials.bordereau-payment-modal', [
                            'bordereau' => $bordereau,
                            'agent' => $agent,
                            'financementStats' => $financementStats,
                            'soldeCaisse' => $soldeCaisse,
                        ])
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    @include('comptes-agents.partials.transactions-history-modal', ['agent' => $agent])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function updateBordereauPaymentMax(select) {
                var modalId = select.dataset.modalId;
                var reste = parseFloat(select.dataset.reste) || 0;
                var financement = parseFloat(select.dataset.financement) || 0;
                var caisse = parseFloat(select.dataset.caisse) || 0;
                var source = select.value;
                var max = reste;

                if (source === 'financement') {
                    max = Math.min(reste, Math.max(0, financement));
                } else if (source === 'transactions') {
                    max = Math.min(reste, Math.max(0, caisse));
                }

                var maxLabel = document.getElementById(modalId + '_max_label');
                if (maxLabel) {
                    maxLabel.textContent = new Intl.NumberFormat('fr-FR').format(max);
                }

                var chequeField = document.getElementById(modalId + '_cheque_field');
                if (chequeField) {
                    chequeField.classList.toggle('d-none', source !== 'cheque');
                }
            }

            document.querySelectorAll('.bordereau-payment-source').forEach(function (select) {
                updateBordereauPaymentMax(select);
                select.addEventListener('change', function () {
                    updateBordereauPaymentMax(select);
                });
            });

            var buttons = document.querySelectorAll('.compte-agent-section-btn');
            var sections = document.querySelectorAll('.compte-agent-section');

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var target = button.getAttribute('data-section');

                    buttons.forEach(function (btn) {
                        btn.classList.remove('btn-primary', 'active');
                        btn.classList.add('btn-outline-primary');
                    });
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary', 'active');

                    sections.forEach(function (section) {
                        section.classList.add('d-none');
                    });

                    var el = document.getElementById(target + '-section');
                    if (el) {
                        el.classList.remove('d-none');
                    }
                });
            });

            @if (old('payment_bordereau_id'))
                var errorModal = document.getElementById('payerBordereau{{ old('payment_bordereau_id') }}');
                if (errorModal) {
                    var modal = bootstrap.Modal.getInstance(errorModal) || new bootstrap.Modal(errorModal);
                    modal.show();
                }
            @endif
        });
    </script>
@endpush
