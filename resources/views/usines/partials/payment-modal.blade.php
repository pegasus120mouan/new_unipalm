@php
    $modalId = $modalId ?? 'paymentModal'.$usine->id_usine;
    $banques = $banques ?? collect();
    $isCurrentModal = (int) old('payment_usine_id') === (int) $usine->id_usine;
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

                    @if ($banques->isEmpty())
                        <div class="alert alert-warning mb-3">
                            Aucune banque active n'est disponible. Ajoutez une banque dans la gestion des banques avant d'enregistrer un paiement.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="{{ $modalId }}_id_banque" class="form-label">Banque</label>
                        <select name="id_banque" id="{{ $modalId }}_id_banque"
                            class="form-select @error('id_banque') is-invalid @enderror" required
                            @disabled($banques->isEmpty())>
                            <option value="" disabled @selected(! $isCurrentModal || ! old('id_banque'))>
                                Sélectionner une banque
                            </option>
                            @foreach ($banques as $banque)
                                <option value="{{ $banque->id_banque }}"
                                    @selected($isCurrentModal && (int) old('id_banque') === (int) $banque->id_banque)>
                                    {{ $banque->nom_banque }}@if ($banque->numero_compte) — {{ $banque->numero_compte }}@endif
                                </option>
                            @endforeach
                        </select>
                        @if ($isCurrentModal)
                            @error('id_banque')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_montant_display" class="form-label">Montant du paiement (FCFA)</label>
                        <input type="text" id="{{ $modalId }}_montant_display"
                            class="form-control @error('montant') is-invalid @enderror"
                            data-amount-input
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Ex : 500 000"
                            value="{{ $isCurrentModal && old('montant') !== null && old('montant') !== '' ? number_format((float) old('montant'), 0, '', ' ') : '' }}"
                            required>
                        <input type="hidden" name="montant" id="{{ $modalId }}_montant" data-amount-target
                            value="{{ $isCurrentModal ? old('montant') : '' }}">
                        @if ($isCurrentModal)
                            @error('montant')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_date_paiement" class="form-label">Date du paiement</label>
                        <input type="date" name="date_paiement" id="{{ $modalId }}_date_paiement"
                            class="form-control @error('date_paiement') is-invalid @enderror"
                            value="{{ $isCurrentModal ? old('date_paiement', now()->format('Y-m-d')) : now()->format('Y-m-d') }}"
                            required>
                        @if ($isCurrentModal)
                            @error('date_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_mode_paiement" class="form-label">Mode de paiement</label>
                        <select name="mode_paiement" id="{{ $modalId }}_mode_paiement"
                            class="form-select @error('mode_paiement') is-invalid @enderror" required>
                            <option value="" disabled @selected(! $isCurrentModal || ! old('mode_paiement'))>
                                Sélectionner un mode de paiement
                            </option>
                            <option value="Espèces" @selected($isCurrentModal && old('mode_paiement') === 'Espèces')>Espèces</option>
                            <option value="Chèque" @selected($isCurrentModal && old('mode_paiement') === 'Chèque')>Chèque</option>
                            <option value="Virement" @selected($isCurrentModal && old('mode_paiement') === 'Virement')>Virement</option>
                            <option value="Mobile Money" @selected($isCurrentModal && old('mode_paiement') === 'Mobile Money')>Mobile Money</option>
                        </select>
                        @if ($isCurrentModal)
                            @error('mode_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="{{ $modalId }}_reference_paiement" class="form-label">Référence du paiement</label>
                        <input type="text" name="reference_paiement" id="{{ $modalId }}_reference_paiement"
                            class="form-control @error('reference_paiement') is-invalid @enderror"
                            value="{{ $isCurrentModal ? old('reference_paiement') : '' }}"
                            placeholder="N° chèque, N° transaction...">
                        @if ($isCurrentModal)
                            @error('reference_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-success" @disabled($banques->isEmpty())>
                        <i class="bi bi-save"></i> Enregistrer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
