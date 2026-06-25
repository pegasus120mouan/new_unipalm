@php
    $modalId = $modalId ?? 'paymentModal'.$usine->id_usine;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('usines.amounts.payment', $usine) }}">
                @csrf
                @if (! empty($redirectTo))
                    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                @endif
                <input type="hidden" name="payment_usine_id" value="{{ $usine->id_usine }}">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        Effectuer un paiement — {{ $usine->nom_usine }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->has('paiement') && (int) old('payment_usine_id') === (int) $usine->id_usine)
                        <div class="alert alert-danger">
                            {{ $errors->first('paiement') }}
                        </div>
                    @endif

                    <div class="alert alert-light border mb-3">
                        <span class="text-muted">Reste à payer :</span>
                        <strong class="text-success">
                            {{ number_format((float) ($resteAPayer ?? $usine->reste_a_payer ?? 0), 0, '', ' ') }} FCFA
                        </strong>
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_montant_display" class="form-label">Montant du paiement (FCFA)</label>
                        <input type="text" id="{{ $modalId }}_montant_display"
                            class="form-control @error('montant') is-invalid @enderror"
                            data-amount-input
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Ex : 500 000"
                            value="{{ (int) old('payment_usine_id') === (int) $usine->id_usine && old('montant') !== null && old('montant') !== '' ? number_format((float) old('montant'), 0, '', ' ') : '' }}"
                            required>
                        <input type="hidden" name="montant" id="{{ $modalId }}_montant" data-amount-target
                            value="{{ (int) old('payment_usine_id') === (int) $usine->id_usine ? old('montant') : '' }}">
                        @if ((int) old('payment_usine_id') === (int) $usine->id_usine)
                            @error('montant')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_date_paiement" class="form-label">Date du paiement</label>
                        <input type="date" name="date_paiement" id="{{ $modalId }}_date_paiement"
                            class="form-control @error('date_paiement') is-invalid @enderror"
                            value="{{ (int) old('payment_usine_id') === (int) $usine->id_usine ? old('date_paiement', now()->format('Y-m-d')) : now()->format('Y-m-d') }}"
                            required>
                        @if ((int) old('payment_usine_id') === (int) $usine->id_usine)
                            @error('date_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_mode_paiement" class="form-label">Mode de paiement</label>
                        <select name="mode_paiement" id="{{ $modalId }}_mode_paiement"
                            class="form-select @error('mode_paiement') is-invalid @enderror" required>
                            <option value="" disabled @selected((int) old('payment_usine_id') !== (int) $usine->id_usine || ! old('mode_paiement'))>
                                Sélectionner un mode de paiement
                            </option>
                            <option value="Espèces" @selected((int) old('payment_usine_id') === (int) $usine->id_usine && old('mode_paiement') === 'Espèces')>Espèces</option>
                            <option value="Chèque" @selected((int) old('payment_usine_id') === (int) $usine->id_usine && old('mode_paiement') === 'Chèque')>Chèque</option>
                            <option value="Virement" @selected((int) old('payment_usine_id') === (int) $usine->id_usine && old('mode_paiement') === 'Virement')>Virement</option>
                            <option value="Mobile Money" @selected((int) old('payment_usine_id') === (int) $usine->id_usine && old('mode_paiement') === 'Mobile Money')>Mobile Money</option>
                        </select>
                        @if ((int) old('payment_usine_id') === (int) $usine->id_usine)
                            @error('mode_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_reference_paiement" class="form-label">Référence du paiement</label>
                        <input type="text" name="reference_paiement" id="{{ $modalId }}_reference_paiement"
                            class="form-control @error('reference_paiement') is-invalid @enderror"
                            value="{{ (int) old('payment_usine_id') === (int) $usine->id_usine ? old('reference_paiement') : '' }}"
                            placeholder="N° chèque, N° transaction...">
                        @if ((int) old('payment_usine_id') === (int) $usine->id_usine)
                            @error('reference_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Enregistrer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
