<div class="modal fade" id="modalDemandeFinancement" tabindex="-1" aria-labelledby="modalDemandeFinancementLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalDemandeFinancementLabel">
                    <i class="bi bi-wallet2 me-1"></i> Demande de financement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" action="{{ route('comptes-agents.demande-financement.store', $agent) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        La demande sera créée en <strong>attente de validation</strong>.
                        Elle apparaîtra dans le menu <strong>Financements</strong> pour validation.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Agent</label>
                        <input type="text" class="form-control" value="{{ $agent->full_name }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="demande_financement_montant_display" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                        <input type="text" id="demande_financement_montant_display"
                            class="form-control @error('montant') is-invalid @enderror"
                            data-amount-input
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Ex : 2 000 000"
                            value="{{ old('montant') !== null && old('montant') !== '' ? number_format((float) old('montant'), 0, '', ' ') : '' }}"
                            required>
                        <input type="hidden" name="montant" id="demande_financement_montant" data-amount-target
                            value="{{ old('montant') }}">
                        @error('montant')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="demande_financement_motif" class="form-label">Motif <span class="text-danger">*</span></label>
                        <textarea name="motif" id="demande_financement_motif" rows="3"
                            class="form-control @error('motif') is-invalid @enderror"
                            required>{{ old('motif') }}</textarea>
                        @error('motif')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Envoyer la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
