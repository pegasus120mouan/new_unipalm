{{-- Modal confirmation validation financement --}}
<div class="modal fade" id="confirmValiderFinancementModal" tabindex="-1" aria-labelledby="confirmValiderFinancementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:48px;height:48px;background:#e8f5e9;">
                        <i class="bi bi-check2-circle fs-4 text-success"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="confirmValiderFinancementModalLabel">Valider le financement</h5>
                        <small class="text-muted">Crédit agent + débit solde chef de groupe</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-3">
                    Vous allez <strong>valider</strong> cette demande.
                    Le montant sera ajouté au <strong>solde financement</strong> de l’agent
                    et déduit du <strong>solde du chef de groupe</strong> associé.
                </p>

                <div class="rounded-3 border bg-light p-3 mb-0">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Code</span>
                        <code class="fw-semibold" id="confirmValiderFinCode">—</code>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Agent</span>
                        <span class="fw-semibold" id="confirmValiderFinAgent">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Date</span>
                        <span class="fw-semibold" id="confirmValiderFinDate">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Montant</span>
                        <span class="fw-bold text-success" id="confirmValiderFinMontant">—</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Motif</span>
                        <span class="fw-semibold text-end ms-3" id="confirmValiderFinMotif">—</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Annuler
                </button>
                <form method="POST" id="confirmValiderFinancementForm" action="">
                    @csrf
                    <button type="submit" class="btn btn-success" id="confirmValiderFinancementSubmit">
                        <i class="bi bi-check2-circle me-1"></i>Confirmer la validation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
