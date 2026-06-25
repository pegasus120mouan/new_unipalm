<div class="modal fade" id="addPretModal" tabindex="-1" aria-labelledby="addPretModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPretModalLabel">Nouveau prêt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" action="{{ route('prets.store') }}">
                @csrf
                @if (! empty($redirectTo))
                    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                @endif
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pret_agent_id" class="form-label">Agent</label>
                        @if (! empty($selectedAgentId))
                            @php
                                $selectedAgent = $agents->firstWhere('id_agent', (int) $selectedAgentId);
                            @endphp
                            <input type="hidden" name="id_agent" value="{{ $selectedAgentId }}">
                            <input type="text" id="pret_agent_id" class="form-control"
                                value="{{ $selectedAgent?->full_name }}" disabled>
                        @else
                            <select name="id_agent" id="pret_agent_id"
                                class="form-select @error('id_agent') is-invalid @enderror" required>
                                <option value="">Sélectionner un agent</option>
                                @foreach ($agents as $agentOption)
                                    <option value="{{ $agentOption->id_agent }}"
                                        @selected(old('id_agent') == $agentOption->id_agent)>
                                        {{ $agentOption->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('id_agent')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="pret_montant_display" class="form-label">Montant du prêt (FCFA)</label>
                        <input type="text" id="pret_montant_display"
                            class="form-control @error('montant_initial') is-invalid @enderror"
                            data-amount-input
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Ex : 500 000"
                            value="{{ old('montant_initial') !== null && old('montant_initial') !== '' ? number_format((float) old('montant_initial'), 0, '', ' ') : '' }}"
                            required>
                        <input type="hidden" name="montant_initial" id="pret_montant_initial" data-amount-target
                            value="{{ old('montant_initial') }}">
                        @error('montant_initial')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="pret_motif" class="form-label">Motif</label>
                        <textarea name="motif" id="pret_motif" rows="3"
                            class="form-control @error('motif') is-invalid @enderror">{{ old('motif') }}</textarea>
                        @error('motif')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
@endpush
