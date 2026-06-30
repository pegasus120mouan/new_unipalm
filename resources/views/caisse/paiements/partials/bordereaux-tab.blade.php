@php
    $redirectTo = route('caisse.paiements.index', array_merge(request()->query(), ['tab' => 'bordereaux']));
@endphp

<section class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-funnel"></i> Filtres
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('caisse.paiements.index') }}" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="bordereaux">
                    <div class="col-md-3">
                        <label for="search_numero" class="form-label">N° Ticket/Bordereau</label>
                        <input type="text" name="search_numero" id="search_numero" class="form-control"
                            value="{{ $filters['search_numero'] ?? '' }}" placeholder="BORD-...">
                    </div>
                    <div class="col-md-3">
                        <label for="search_agent" class="form-label">Chargé de Mission</label>
                        <select name="search_agent" id="search_agent" class="form-select">
                            <option value="">Tous les agents</option>
                            @foreach ($agents as $agentOption)
                                <option value="{{ $agentOption->id_agent }}" @selected(($filters['search_agent'] ?? '') == $agentOption->id_agent)>
                                    {{ $agentOption->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control"
                            value="{{ $filters['date_debut'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control"
                            value="{{ $filters['date_fin'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label for="statut" class="form-label">Statut</label>
                        <select name="statut" id="statut" class="form-select">
                            <option value="">Tous</option>
                            <option value="non_paye" @selected(($filters['statut'] ?? '') === 'non_paye')>Non payés</option>
                            <option value="en_cours" @selected(($filters['statut'] ?? '') === 'en_cours')>En cours</option>
                            <option value="solde" @selected(($filters['statut'] ?? '') === 'solde')>Soldés</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Rechercher
                        </button>
                        <a href="{{ route('caisse.paiements.index', ['tab' => 'bordereaux']) }}" class="btn btn-secondary">
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
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Bordereaux validés</span>
                <span class="text-muted">{{ $bordereaux->total() }} bordereau(x)</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>N° Ticket/Bordereau</th>
                            <th>Usine</th>
                            <th>Chargé de Mission</th>
                            <th>Véhicule</th>
                            <th class="text-end">Poids</th>
                            <th class="text-end">Montant total</th>
                            <th class="text-end">Montant payé</th>
                            <th class="text-end">Reste à payer</th>
                            <th>Dernier paiement</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bordereaux as $bordereau)
                            @php
                                $reste = (float) ($bordereau->montant_reste ?? max((float) $bordereau->montant_total - (float) $bordereau->montant_payer, 0));
                            @endphp
                            <tr>
                                <td>{{ $bordereau->date_debut?->format('Y-m-d') ?? $bordereau->created_at?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('bordereaux.pdf', $bordereau->numero_bordereau) }}" target="_blank">
                                        {{ $bordereau->numero_bordereau }}
                                    </a>
                                </td>
                                <td>—</td>
                                <td>{{ $bordereau->agent?->full_name ?? '—' }}</td>
                                <td>—</td>
                                <td class="text-end">{{ number_format((float) $bordereau->poids_total, 0, '', ' ') }}</td>
                                <td class="text-end">{{ number_format((float) $bordereau->montant_total, 0, '', ' ') }} FCFA</td>
                                <td class="text-end">{{ number_format((float) ($bordereau->montant_payer ?? 0), 0, '', ' ') }} FCFA</td>
                                <td class="text-end">{{ number_format($reste, 0, '', ' ') }} FCFA</td>
                                <td>{{ $bordereau->date_paie?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-center">
                                    @if ($reste <= 0)
                                        <button type="button" class="btn btn-success btn-sm" disabled>
                                            <i class="bi bi-check-circle"></i> Bordereau soldé
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payerBordereau{{ $bordereau->id_bordereau }}">
                                            <i class="bi bi-cash-coin"></i> Effectuer un paiement
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">Aucun bordereau validé trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($bordereaux->hasPages())
                <div class="card-footer">
                    {{ $bordereaux->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

@foreach ($bordereaux as $bordereau)
    @php
        $modalReste = (float) ($bordereau->montant_reste ?? max((float) $bordereau->montant_total - (float) $bordereau->montant_payer, 0));
        $agent = $bordereau->agent;
        $financementStats = $financementByAgent[$bordereau->id_agent] ?? ['solde_financement' => 0];
    @endphp
    @if ($modalReste > 0 && $agent)
        @include('comptes-agents.partials.bordereau-payment-modal', [
            'bordereau' => $bordereau,
            'agent' => $agent,
            'financementStats' => $financementStats,
            'soldeCaisse' => $soldeCaisse,
            'montantUtilisable' => $montantUtilisable,
            'redirectTo' => $redirectTo,
        ])
    @endif
@endforeach
