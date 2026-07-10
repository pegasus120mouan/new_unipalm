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
                    @if (session('last_recu_id'))
                        <div class="small mt-1">
                            <a href="{{ route('recus.tickets.pdf', session('last_recu_id')) }}" target="_blank" class="alert-link">
                                <i class="bi bi-file-earmark-pdf"></i> Voir le reçu
                            </a>
                        </div>
                    @endif
                </div>
            </div>
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

    <section class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 48px; height: 48px;">
                                <i class="bi bi-wallet2 fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Solde actuel</h6>
                                <small class="text-muted text-uppercase">{{ $groupe->full_name }}</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fs-3 fw-bold mb-0 {{ $soldeChef['reste_a_payer'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($soldeChef['reste_a_payer'], 0, '', ' ') }} FCFA
                            </div>
                            <small class="text-muted">Reste à payer (tickets) — diminue à chaque paiement bordereau</small>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted small">Total montant</div>
                            <div class="fw-semibold">{{ number_format($soldeChef['total_montant'], 0, '', ' ') }} FCFA</div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted small">Montant payé</div>
                            <div class="fw-semibold text-success">{{ number_format($soldeChef['montant_paye'], 0, '', ' ') }} FCFA</div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted small">Reste à payer</div>
                            <div class="fw-semibold {{ $soldeChef['reste_a_payer'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($soldeChef['reste_a_payer'], 0, '', ' ') }} FCFA
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted small">Financement disponible</div>
                            <div class="fw-semibold text-primary">{{ number_format($soldeChef['solde_financement'], 0, '', ' ') }} FCFA</div>
                        </div>
                    </div>
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
                    <h6 class="text-uppercase text-muted mb-3">Synthèse financière (bordereaux)</h6>
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

    <section class="row" id="bordereaux-section">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-file-earmark-text"></i> Bordereaux des agents</span>
                    <span class="badge bg-secondary">{{ number_format($counts['bordereaux'], 0, '', ' ') }} bordereau(x)</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('comptes-groupes.show', $groupe) }}" class="mb-3">
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
                            <div class="col-md-4 col-lg-2">
                                <label for="bordereau_date_debut" class="form-label small text-uppercase text-muted">Date début</label>
                                <input type="date" name="date_debut" id="bordereau_date_debut" class="form-control form-control-sm"
                                    value="{{ $filters['date_debut'] }}">
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label for="bordereau_date_fin" class="form-label small text-uppercase text-muted">Date fin</label>
                                <input type="date" name="date_fin" id="bordereau_date_fin" class="form-control form-control-sm"
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
                                    <th>N° Bordereau</th>
                                    <th>Agent</th>
                                    <th>Généré le</th>
                                    <th>Période</th>
                                    <th class="text-center">Fiches</th>
                                    <th class="text-end">Poids</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-end">Montant payé</th>
                                    <th class="text-end">Reste à payer</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bordereaux as $bordereau)
                                    @php
                                        $statusKey = $bordereau->paymentStatusKey();
                                        $statusLabel = $compteAgentService->ticketPaymentStatusLabel($statusKey);
                                        $statusClass = match ($statusKey) {
                                            'solde' => 'bg-success',
                                            'en_cours' => 'bg-warning text-dark',
                                            default => 'bg-secondary',
                                        };
                                        $reste = $bordereau->reste_a_payer;
                                        $pdfUrl = $gestCamionsUrl
                                            ? $gestCamionsUrl.'/gestion-financiere/agent-financier/'.$bordereau->id_agent.'/bordereaux/'.$bordereau->id.'/pdf'
                                            : null;
                                        $agentUrl = $gestCamionsUrl
                                            ? $gestCamionsUrl.'/gestion-financiere/agent-financier/'.$bordereau->id_agent
                                            : route('comptes-agents.show', ['agent' => $bordereau->id_agent, 'section' => 'bordereaux']);
                                        $financementStats = $financementByAgent[(int) $bordereau->id_agent] ?? ['solde_financement' => 0];
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $bordereau->numero }}</td>
                                        <td>
                                            <a href="{{ $agentUrl }}" class="text-decoration-none" @if($gestCamionsUrl) target="_blank" @endif>
                                                {{ $bordereau->agent_nom ?: ('Agent #'.$bordereau->id_agent) }}
                                            </a>
                                            @if ($bordereau->agent_numero)
                                                <div class="small text-muted">{{ $bordereau->agent_numero }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $bordereau->date_generation?->format('d/m/Y') ?? '—' }}</td>
                                        <td>
                                            {{ $bordereau->date_debut?->format('d/m/Y') ?? '—' }}
                                            →
                                            {{ $bordereau->date_fin?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $bordereau->nombre_fiches }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format((float) $bordereau->poids_total, 0, '', ' ') }} kg</td>
                                        <td class="text-end text-danger">{{ number_format((float) $bordereau->montant_total, 0, '', ' ') }} FCFA</td>
                                        <td class="text-end text-success">{{ number_format((float) ($bordereau->montant_paye ?? 0), 0, '', ' ') }} FCFA</td>
                                        <td class="text-end text-danger">{{ number_format($reste, 0, '', ' ') }} FCFA</td>
                                        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @if ($reste > 0)
                                                    <button type="button" class="btn btn-success btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#payerBordereauGc{{ $bordereau->id }}"
                                                        title="Payer">
                                                        <i class="bi bi-cash-coin"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-outline-success btn-sm" disabled title="Soldé">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                @endif
                                                @if ($pdfUrl)
                                                    <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-outline-primary btn-sm" title="PDF">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ $agentUrl }}" class="btn btn-outline-secondary btn-sm" title="Voir l'agent" @if($gestCamionsUrl) target="_blank" @endif>
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">
                                            <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-25"></i>
                                            Aucun bordereau généré pour les agents de ce chef.
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
                </div>
            </div>
        </div>
    </section>

    @foreach ($bordereaux as $bordereau)
        @if ($bordereau->reste_a_payer > 0)
            @include('comptes-groupes.partials.gest-camions-bordereau-payment-modal', [
                'bordereau' => $bordereau,
                'groupe' => $groupe,
                'financementStats' => $financementByAgent[(int) $bordereau->id_agent] ?? ['solde_financement' => 0],
                'soldeCaisse' => $soldeCaisse,
                'montantUtilisable' => $montantUtilisable,
            ])
        @endif
    @endforeach
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

            var autoOpen = document.querySelector('.modal[data-auto-open="1"]');
            if (autoOpen && window.bootstrap) {
                new bootstrap.Modal(autoOpen).show();
            }
        });
    </script>
@endpush
