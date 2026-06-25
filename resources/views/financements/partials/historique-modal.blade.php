<div class="modal fade" id="historiqueModal" tabindex="-1" aria-labelledby="historiqueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="historiqueModalLabel">
                    <i class="bi bi-file-pdf"></i> Générer l'historique de financement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="{{ route('financements.pdf', $agent) }}" method="GET" target="_blank">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Agent sélectionné :</strong> {{ $agent->full_name }}
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pdf_date_debut" class="form-label">
                                    <i class="bi bi-calendar3"></i> Date de début
                                </label>
                                <input type="date" class="form-control" id="pdf_date_debut" name="date_debut" required
                                    value="{{ now()->subDays(30)->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pdf_date_fin" class="form-label">
                                    <i class="bi bi-calendar-check"></i> Date de fin
                                </label>
                                <input type="date" class="form-control" id="pdf_date_fin" name="date_fin" required
                                    value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        <small>Le PDF sera généré et téléchargé automatiquement avec tous les financements de l'agent sur la période sélectionnée.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-download"></i> Générer le PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
