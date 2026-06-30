@php
    $modalId = $modalId ?? 'utilisableCaisseModal';
    $soldeCaisse = $soldeCaisse ?? ($stats['solde_caisse'] ?? 0);
    $montantUtilisable = $montantUtilisable ?? ($stats['montant_utilisable'] ?? $soldeCaisse);
    $montantReserve = max(0, (float) $soldeCaisse - (float) $montantUtilisable);
@endphp

<div class="card border-0 text-white shadow-sm h-100 mb-0" style="background-color: #0d6efd; cursor: pointer;"
    role="button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" title="Cliquer pour voir le détail">
    <div class="card-body text-center py-3 px-4 d-flex flex-column justify-content-center">
        <div class="small opacity-75 text-uppercase mb-1">Montant actuel de la caisse</div>
        <div class="fw-bold {{ ($badgeClass ?? null) ?: 'fs-4' }} mb-0">
            {{ number_format((float) $soldeCaisse, 0, ',', ' ') }} FCFA
        </div>
        @if ($montantReserve > 0)
            <div class="small opacity-75 mt-1">
                dont {{ number_format((float) $montantUtilisable, 0, ',', ' ') }} FCFA utilisable
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">Détail du solde caisse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="p-3 rounded border bg-light">
                            <div class="text-muted small">Solde actuel (total en caisse)</div>
                            <div class="fw-bold fs-5 text-primary">{{ number_format((float) $soldeCaisse, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border bg-light h-100">
                            <div class="text-muted small">Montant utilisable</div>
                            <div class="fw-bold fs-5 text-success">{{ number_format((float) $montantUtilisable, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border bg-light h-100">
                            <div class="text-muted small">Montant non utilisable</div>
                            <div class="fw-bold fs-5 text-secondary">{{ number_format($montantReserve, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                </div>

                <p class="text-muted small mb-0">
                    La caissière voit le solde total, mais les opérations de sortie sont limitées au montant utilisable.
                </p>

                @if ($canModule('caisse.approvisionnement'))
                    <hr>
                    <form method="POST" action="{{ route('caisse.utilisable.update') }}">
                        @csrf
                        @if (! empty($redirectTo))
                            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                        @endif
                        <div class="mb-0">
                            <label for="{{ $modalId }}_nouveau_utilisable_display" class="form-label">
                                Augmenter le montant utilisable
                            </label>
                            <div class="input-group">
                                <input type="text" id="{{ $modalId }}_nouveau_utilisable_display"
                                    class="form-control @error('montant_utilisable') is-invalid @enderror"
                                    data-amount-input inputmode="numeric" autocomplete="off"
                                    placeholder="Ex : {{ number_format((float) $montantUtilisable, 0, '', ' ') }}"
                                    value="{{ old('montant_utilisable') ? number_format((float) old('montant_utilisable'), 0, '', ' ') : '' }}"
                                    required>
                                <input type="hidden" name="montant_utilisable" id="{{ $modalId }}_nouveau_utilisable"
                                    data-amount-target value="{{ old('montant_utilisable') }}">
                                <span class="input-group-text">FCFA</span>
                            </div>
                            <div class="form-text">
                                Minimum : {{ number_format((float) $montantUtilisable, 0, ',', ' ') }} FCFA —
                                Maximum : {{ number_format((float) $soldeCaisse, 0, ',', ' ') }} FCFA
                            </div>
                            @error('montant_utilisable')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                @else
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
