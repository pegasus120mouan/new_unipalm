@php
    $redirectTo = route('caisse.paiements.index', array_merge(request()->query(), ['tab' => 'divers']));
@endphp

<section class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-3">
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#nouvelleSortieDiverseModal">
                    <i class="bi bi-dash-circle"></i> Nouvelle sortie diverse
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
                    <input type="hidden" name="tab" value="divers">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Recherche</label>
                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ $filters['search'] ?? '' }}" placeholder="N° sortie ou motif...">
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
                <span>Sorties diverses</span>
                <span class="text-muted">{{ $sorties->total() }} sortie(s)</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>N° sortie</th>
                            <th>Motifs</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sorties as $sortie)
                            <tr>
                                <td>{{ $sortie->date_sortie?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $sortie->numero_sorties }}</td>
                                <td>{{ $sortie->motifs }}</td>
                                <td class="text-end text-danger">{{ number_format((float) $sortie->montant, 0, '', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Aucune sortie diverse enregistrée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sorties->hasPages())
                <div class="card-footer">
                    {{ $sorties->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

<div class="modal fade" id="nouvelleSortieDiverseModal" tabindex="-1" aria-labelledby="nouvelleSortieDiverseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('caisse.paiements.divers.store') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="nouvelleSortieDiverseModalLabel">Nouvelle sortie diverse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Montant utilisable :</strong> {{ number_format($montantUtilisable, 0, '', ' ') }} FCFA
                    </div>
                    <div class="mb-3">
                        <label for="divers_montant_display" class="form-label">Montant <span class="text-danger">*</span></label>
                        <input type="text" id="divers_montant_display" class="form-control"
                            data-amount-input inputmode="numeric" autocomplete="off"
                            placeholder="Montant en FCFA" required>
                        <input type="hidden" name="montant" id="divers_montant" data-amount-target
                            value="{{ old('montant') }}">
                    </div>
                    <div class="mb-3">
                        <label for="motifs" class="form-label">Motifs <span class="text-danger">*</span></label>
                        <textarea name="motifs" id="motifs" class="form-control" rows="4" required
                            placeholder="Décrivez la raison de cette sortie diverse...">{{ old('motifs') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-save"></i> Enregistrer la sortie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('paiement') && $tab === 'divers')
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('nouvelleSortieDiverseModal');
                if (modal) {
                    new bootstrap.Modal(modal).show();
                }
            });
        </script>
    @endpush
@endif
