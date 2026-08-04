<div class="modal fade" id="addUsineFinancementModal" tabindex="-1" aria-labelledby="addUsineFinancementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('usines.financements.store', $usine) }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addUsineFinancementModalLabel">
                        Financement reçu — {{ $usine->nom_usine }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->has('financement'))
                        <div class="alert alert-danger">{{ $errors->first('financement') }}</div>
                    @endif

                    <div class="alert alert-light border mb-3">
                        <div class="small text-muted">
                            L’usine nous verse un financement. Le montant sera crédité sur la banque sélectionnée.
                        </div>
                    </div>

                    @if ($banques->isEmpty())
                        <div class="alert alert-warning mb-3">
                            Aucune banque active n'est disponible.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="usine_fin_id_banque" class="form-label">Banque</label>
                        <select name="id_banque" id="usine_fin_id_banque"
                            class="form-select @error('id_banque') is-invalid @enderror" required
                            @disabled($banques->isEmpty())>
                            <option value="" disabled @selected(! old('id_banque'))>Sélectionner une banque</option>
                            @foreach ($banques as $banque)
                                <option value="{{ $banque->id_banque }}" @selected((int) old('id_banque') === (int) $banque->id_banque)>
                                    {{ $banque->nom_banque }}@if ($banque->numero_compte) — {{ $banque->numero_compte }}@endif
                                </option>
                            @endforeach
                        </select>
                        @error('id_banque')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="usine_fin_montant_display" class="form-label">Montant (FCFA)</label>
                        <input type="text" id="usine_fin_montant_display"
                            class="form-control @error('montant') is-invalid @enderror"
                            data-amount-input
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Ex : 5 000 000"
                            value="{{ old('montant') !== null && old('montant') !== '' ? number_format((float) old('montant'), 0, '', ' ') : '' }}"
                            required>
                        <input type="hidden" name="montant" id="usine_fin_montant" data-amount-target
                            value="{{ old('montant') }}">
                        @error('montant')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="usine_fin_date" class="form-label">Date du financement</label>
                        <input type="date" name="date_financement" id="usine_fin_date"
                            class="form-control @error('date_financement') is-invalid @enderror"
                            value="{{ old('date_financement', now()->format('Y-m-d')) }}" required>
                        @error('date_financement')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="usine_fin_mode" class="form-label">Mode de paiement</label>
                        <select name="mode_paiement" id="usine_fin_mode"
                            class="form-select @error('mode_paiement') is-invalid @enderror" required>
                            <option value="" disabled @selected(! old('mode_paiement'))>Sélectionner un mode</option>
                            <option value="Espèces" @selected(old('mode_paiement') === 'Espèces')>Espèces</option>
                            <option value="Chèque" @selected(old('mode_paiement') === 'Chèque')>Chèque</option>
                            <option value="Virement" @selected(old('mode_paiement') === 'Virement')>Virement</option>
                            <option value="Mobile Money" @selected(old('mode_paiement') === 'Mobile Money')>Mobile Money</option>
                        </select>
                        @error('mode_paiement')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="usine_fin_reference" class="form-label">Référence</label>
                        <input type="text" name="reference_paiement" id="usine_fin_reference"
                            class="form-control @error('reference_paiement') is-invalid @enderror"
                            value="{{ old('reference_paiement') }}"
                            placeholder="N° chèque, N° transaction...">
                        @error('reference_paiement')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="usine_fin_motif" class="form-label">Motif</label>
                        <textarea name="motif" id="usine_fin_motif" rows="2"
                            class="form-control @error('motif') is-invalid @enderror"
                            placeholder="Optionnel">{{ old('motif') }}</textarea>
                        @error('motif')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-success" @disabled($banques->isEmpty())>
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
