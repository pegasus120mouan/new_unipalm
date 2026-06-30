@php
    $modalId = 'payerDemande'.$demande->id_demande;
    $montantTotal = (float) $demande->montant;
    $montantPaye = (float) ($demande->montant_payer ?? 0);
    $reste = (float) ($demande->montant_reste ?? max($montantTotal - $montantPaye, 0));
    $limiteCaisse = min((float) $soldeCaisse, (float) $montantUtilisable);
    $maxDefault = min($reste, max(0, $limiteCaisse));
    $isErrorModal = (int) old('payment_demande_id') === (int) $demande->id_demande;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('caisse.paiements.demandes.store', $demande) }}">
                @csrf
                <input type="hidden" name="payment_demande_id" value="{{ $demande->id_demande }}">
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        Paiement de la demande #{{ $demande->numero_demande }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if ($isErrorModal && $errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Montant total de la demande</label>
                        <input type="text" class="form-control" value="{{ number_format($montantTotal, 0, '', ' ') }} FCFA" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant déjà payé</label>
                        <input type="text" class="form-control" value="{{ number_format($montantPaye, 0, '', ' ') }} FCFA" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reste à payer</label>
                        <input type="text" class="form-control" value="{{ number_format($reste, 0, '', ' ') }} FCFA" readonly>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="bi bi-wallet2 me-2"></i>
                        <div>
                            <strong>Solde Caisse :</strong> {{ number_format($soldeCaisse, 0, '', ' ') }} FCFA
                            @if ($limiteCaisse < $soldeCaisse)
                                <br><small>Utilisable : {{ number_format($limiteCaisse, 0, '', ' ') }} FCFA</small>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_montant_display" class="form-label">
                            Montant du paiement (Max: {{ number_format($maxDefault, 0, '', ' ') }} FCFA)
                        </label>
                        <input type="text" id="{{ $modalId }}_montant_display" class="form-control"
                            data-amount-input inputmode="numeric" autocomplete="off"
                            placeholder="Entrez le montant à payer"
                            value="{{ $isErrorModal && old('montant') ? number_format((float) old('montant'), 0, '', ' ') : '' }}"
                            required>
                        <input type="hidden" name="montant" id="{{ $modalId }}_montant" data-amount-target
                            value="{{ $isErrorModal ? old('montant') : '' }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary" @disabled($limiteCaisse <= 0)>
                        <i class="bi bi-save"></i> Enregistrer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
