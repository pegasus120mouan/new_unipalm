@php
    $modalId = 'payerBordereau'.$bordereau->id_bordereau;
    $montantTotal = (float) $bordereau->montant_total;
    $montantPaye = (float) ($bordereau->montant_payer ?? 0);
    $reste = (float) ($bordereau->montant_reste ?? max($montantTotal - $montantPaye, 0));
    $soldeFinancement = (float) ($financementStats['solde_financement'] ?? 0);
    $defaultSource = $soldeFinancement > 0 ? 'financement' : 'transactions';
    $maxDefault = match ($defaultSource) {
        'financement' => min($reste, max(0, $soldeFinancement)),
        default => min($reste, max(0, $soldeCaisse)),
    };
    $isErrorModal = (int) old('payment_bordereau_id') === (int) $bordereau->id_bordereau;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('comptes-agents.bordereaux.payment', $bordereau) }}">
                @csrf
                <input type="hidden" name="payment_bordereau_id" value="{{ $bordereau->id_bordereau }}">
                <input type="hidden" name="redirect_to" value="{{ route('comptes-agents.show', ['agent' => $agent, 'section' => 'bordereaux']) }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        Paiement du bordereau #{{ $bordereau->numero_bordereau }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if ($isErrorModal && $errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Montant total à payer</label>
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
                            @if ($soldeCaisse < $reste && $soldeFinancement <= 0)
                                <br><small class="text-warning">Solde insuffisant pour payer la totalité</small>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_source" class="form-label">Source de paiement</label>
                        <select name="source_paiement" id="{{ $modalId }}_source" class="form-select bordereau-payment-source" required
                            data-modal-id="{{ $modalId }}"
                            data-reste="{{ $reste }}"
                            data-financement="{{ $soldeFinancement }}"
                            data-caisse="{{ $soldeCaisse }}">
                            @if ($soldeFinancement > 0)
                                <option value="financement" @selected($isErrorModal ? old('source_paiement') === 'financement' : true)>
                                    Financement (Solde: {{ number_format($soldeFinancement, 0, '', ' ') }} FCFA)
                                </option>
                                <option value="transactions" @selected($isErrorModal && old('source_paiement') === 'transactions') @disabled($soldeCaisse <= 0)>
                                    Sortie de caisse (Solde: {{ number_format($soldeCaisse, 0, '', ' ') }} FCFA)
                                </option>
                            @else
                                <option value="transactions" @selected($isErrorModal ? old('source_paiement') === 'transactions' : true) @disabled($soldeCaisse <= 0)>
                                    Sortie de caisse (Solde: {{ number_format($soldeCaisse, 0, '', ' ') }} FCFA)
                                </option>
                                <option value="financement" disabled>
                                    Financement (Solde: 0 FCFA)
                                </option>
                            @endif
                            <option value="cheque" @selected($isErrorModal && old('source_paiement') === 'cheque')>
                                Paiement par chèque
                            </option>
                        </select>
                    </div>

                    <div class="mb-3 bordereau-cheque-field d-none" id="{{ $modalId }}_cheque_field">
                        <label for="{{ $modalId }}_numero_cheque" class="form-label">
                            Numéro de chèque <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="numero_cheque" id="{{ $modalId }}_numero_cheque" class="form-control"
                            maxlength="50" placeholder="Saisissez le numéro de chèque"
                            value="{{ $isErrorModal ? old('numero_cheque') : '' }}">
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_montant_display" class="form-label">
                            Montant à payer (Max: <span id="{{ $modalId }}_max_label">{{ number_format($maxDefault, 0, '', ' ') }}</span> FCFA)
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
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
