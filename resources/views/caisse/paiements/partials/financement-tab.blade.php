@php
    $redirectTo = route('caisse.paiements.index', array_merge(request()->query(), ['tab' => 'financement']));
@endphp

<section class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouveauFinancementCaisseModal">
                    <i class="bi bi-plus-circle"></i> Nouveau financement
                </button>
            </div>
        </div>
    </div>
</section>

<section class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-funnel"></i> Filtres
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('caisse.paiements.index') }}" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="financement">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Recherche</label>
                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ $filters['search'] ?? '' }}" placeholder="Code, agent ou motif...">
                    </div>
                    <div class="col-md-3">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control"
                            value="{{ $filters['date_debut'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control"
                            value="{{ $filters['date_fin'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
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
                <span>Financements</span>
                <span class="text-muted">{{ $financements->total() }} financement(s)</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Code</th>
                            <th>Agent</th>
                            <th>Motif</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($financements as $financement)
                            <tr>
                                <td>{{ $financement->date_financement?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td><code>{{ $financement->code_affiche }}</code></td>
                                <td>{{ $financement->agent?->full_name ?? '—' }}</td>
                                <td>{{ $financement->motif_affiche ?: '—' }}</td>
                                <td class="text-end text-danger">{{ number_format((float) $financement->montant, 0, '', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aucun financement enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($financements->hasPages())
                <div class="card-footer">
                    {{ $financements->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

<div class="modal fade" id="nouveauFinancementCaisseModal" tabindex="-1" aria-labelledby="nouveauFinancementCaisseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('caisse.paiements.financement.store') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="nouveauFinancementCaisseModalLabel">Nouveau financement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Montant utilisable :</strong> {{ number_format($montantUtilisable, 0, '', ' ') }} FCFA
                        <div class="small mt-1">La caisse sera débitée et le solde financement de l'agent sera crédité.</div>
                    </div>
                    <div class="mb-3">
                        <label for="financement_agent_id" class="form-label">Agent <span class="text-danger">*</span></label>
                        <select name="id_agent" id="financement_agent_id" class="form-select @error('id_agent') is-invalid @enderror" required>
                            <option value="">Sélectionner un agent</option>
                            @foreach ($agents as $agentOption)
                                <option value="{{ $agentOption->id_agent }}" @selected((int) old('id_agent') === (int) $agentOption->id_agent)>
                                    {{ $agentOption->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_agent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="financement_montant_display" class="form-label">Montant <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" id="financement_montant_display" class="form-control @error('montant') is-invalid @enderror"
                                data-amount-input inputmode="numeric" autocomplete="off"
                                placeholder="Montant en FCFA" required
                                value="{{ old('montant') ? number_format((float) old('montant'), 0, '', ' ') : '' }}">
                            <input type="hidden" name="montant" id="financement_montant" data-amount-target value="{{ old('montant') }}">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        @error('montant')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="financement_motif" class="form-label">Motif <span class="text-danger">*</span></label>
                        <textarea name="motif" id="financement_motif" class="form-control @error('motif') is-invalid @enderror" rows="3" required
                            placeholder="Décrivez la raison de ce financement...">{{ old('motif') }}</textarea>
                        @error('motif')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('paiement') && $tab === 'financement')
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('nouveauFinancementCaisseModal');
                if (modal) {
                    new bootstrap.Modal(modal).show();
                }
            });
        </script>
    @endpush
@endif
