@php
    $modalId = 'payerBordereauGc'.$bordereau->id;
    $montantTotal = (float) $bordereau->montant_total;
    $montantPaye = (float) ($bordereau->montant_paye ?? 0);
    $reste = (float) $bordereau->reste_a_payer;
    $soldeCaisse = $soldeCaisse ?? 0;
    $montantUtilisable = $montantUtilisable ?? $soldeCaisse;
    $limiteCaisse = min((float) $soldeCaisse, (float) $montantUtilisable);
    $soldeFinancement = (float) ($financementStats['solde_financement'] ?? 0);
    $defaultSource = $soldeFinancement > 0 ? 'financement' : 'transactions';
    $maxDefault = match ($defaultSource) {
        'financement' => min($reste, max(0, $soldeFinancement)),
        default => min($reste, max(0, $limiteCaisse)),
    };
    $isErrorModal = (int) (old('payment_bordereau_id') ?? session('payment_bordereau_id')) === (int) $bordereau->id;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true"
    @if ($isErrorModal) data-auto-open="1" @endif>
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('comptes-groupes.bordereaux.payment', ['groupe' => $groupe, 'bordereau' => $bordereau->id]) }}">
                @csrf
                <input type="hidden" name="payment_bordereau_id" value="{{ $bordereau->id }}">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white" id="{{ $modalId }}Label">
                        <i class="bi bi-cash-coin me-1"></i>
                        Paiement bordereau {{ $bordereau->numero }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if ($isErrorModal && $errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <div class="alert alert-secondary py-2">
                        <strong>Agent :</strong> {{ $bordereau->agent_nom ?: ('#'.$bordereau->id_agent) }}
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Montant total</label>
                        <input type="text" class="form-control" value="{{ number_format($montantTotal, 0, '', ' ') }} FCFA" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Déjà payé</label>
                        <input type="text" class="form-control" value="{{ number_format($montantPaye, 0, '', ' ') }} FCFA" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reste à payer</label>
                        <input type="text" class="form-control text-danger fw-semibold" value="{{ number_format($reste, 0, '', ' ') }} FCFA" readonly>
                    </div>

                    @if ($soldeFinancement > 0)
                        <div class="alert alert-warning py-2">
                            <strong>Financement disponible :</strong> {{ number_format($soldeFinancement, 0, '', ' ') }} FCFA
                            <br><small>Le paiement par financement est plafonné à ce montant.</small>
                        </div>
                    @endif

                    <div class="alert alert-info py-2 d-flex align-items-center mb-3">
                        <i class="bi bi-wallet2 me-2"></i>
                        <div>
                            <strong>Solde Caisse :</strong> {{ number_format($soldeCaisse, 0, '', ' ') }} FCFA
                            @if ($limiteCaisse < $soldeCaisse)
                                <br><small>Utilisable : {{ number_format($limiteCaisse, 0, '', ' ') }} FCFA</small>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_source" class="form-label">Source de paiement</label>
                        <select name="source_paiement" id="{{ $modalId }}_source" class="form-select bordereau-payment-source" required
                            data-modal-id="{{ $modalId }}"
                            data-reste="{{ $reste }}"
                            data-financement="{{ $soldeFinancement }}"
                            data-caisse="{{ $limiteCaisse }}">
                            @if ($soldeFinancement > 0)
                                <option value="financement" @selected($isErrorModal ? old('source_paiement') === 'financement' : true)>
                                    Financement (Solde: {{ number_format($soldeFinancement, 0, '', ' ') }} FCFA)
                                </option>
                                <option value="transactions" @selected($isErrorModal && old('source_paiement') === 'transactions') @disabled($limiteCaisse <= 0)>
                                    Sortie de caisse (Utilisable: {{ number_format($limiteCaisse, 0, '', ' ') }} FCFA)
                                </option>
                            @else
                                <option value="transactions" @selected($isErrorModal ? old('source_paiement') === 'transactions' : true) @disabled($limiteCaisse <= 0)>
                                    Sortie de caisse (Utilisable: {{ number_format($limiteCaisse, 0, '', ' ') }} FCFA)
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

                    <div class="mb-0">
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
