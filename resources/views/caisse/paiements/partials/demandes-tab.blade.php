@php
    $redirectTo = route('caisse.paiements.index', array_merge(request()->query(), ['tab' => 'demandes']));
@endphp

<section class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-funnel"></i> Filtres
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('caisse.paiements.index') }}" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="demandes">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Recherche</label>
                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ $filters['search'] ?? '' }}" placeholder="N° demande ou motif...">
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
                            <option value="non_solde" @selected(($filters['statut'] ?? '') === 'non_solde')>Non soldées</option>
                            <option value="solde" @selected(($filters['statut'] ?? '') === 'solde')>Soldées</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
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
                <span>Demandes approuvées</span>
                <span class="text-muted">{{ $demandes->total() }} demande(s)</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>N° de la demande</th>
                            <th>Motifs</th>
                            <th>Approbateur</th>
                            <th class="text-end">Montant total</th>
                            <th class="text-end">Montant payé</th>
                            <th class="text-end">Reste à payer</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demandes as $demande)
                            @php
                                $montantPaye = (float) ($demande->montant_payer ?? 0);
                                $reste = (float) ($demande->montant_reste ?? max((float) $demande->montant - $montantPaye, 0));
                            @endphp
                            <tr>
                                <td>{{ $demande->date_approbation?->format('Y-m-d') ?? $demande->date_demande?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $demande->numero_demande }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($demande->motif, 60) }}</td>
                                <td>{{ $demande->approbateur?->full_name ?? '—' }}</td>
                                <td class="text-end">{{ number_format((float) $demande->montant, 0, '', ' ') }} FCFA</td>
                                <td class="text-end">{{ number_format($montantPaye, 0, '', ' ') }} FCFA</td>
                                <td class="text-end">{{ number_format($reste, 0, '', ' ') }} FCFA</td>
                                <td class="text-center">
                                    @if ($reste <= 0)
                                        <button type="button" class="btn btn-success btn-sm" disabled>
                                            <i class="bi bi-check-circle"></i> Demande soldée
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payerDemande{{ $demande->id_demande }}">
                                            <i class="bi bi-cash-coin"></i> Effectuer un paiement
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Aucune demande à payer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($demandes->hasPages())
                <div class="card-footer">
                    {{ $demandes->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

@foreach ($demandes as $demande)
    @php
        $modalReste = (float) ($demande->montant_reste ?? max((float) $demande->montant - (float) ($demande->montant_payer ?? 0), 0));
    @endphp
    @if ($modalReste > 0)
        @include('caisse.paiements.partials.demande-payment-modal', [
            'demande' => $demande,
            'montantUtilisable' => $montantUtilisable,
            'soldeCaisse' => $soldeCaisse,
            'redirectTo' => $redirectTo,
        ])
    @endif
@endforeach
