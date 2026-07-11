{{-- Modal confirmation paiement avance --}}
<div class="modal fade" id="confirmAvancePaymentModal" tabindex="-1" aria-labelledby="confirmAvancePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:48px;height:48px;background:#e8f5e9;">
                        <i class="bi bi-cash-coin fs-4 text-success"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="confirmAvancePaymentModalLabel">Confirmation de paiement</h5>
                        <small class="text-muted">Avance agent — caisse groupe</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-3">
                    Vous allez payer cette avance. Le montant sera
                    <strong>débité de la caisse du groupe (PGF)</strong>
                    et crédité au financement de l’agent.
                </p>

                <div class="rounded-3 border bg-light p-3 mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Montant</span>
                        <span class="fw-bold text-success" id="confirmAvanceMontant">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Date demande</span>
                        <span class="fw-semibold" id="confirmAvanceDate">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Mode</span>
                        <span class="fw-semibold" id="confirmAvanceMode">—</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Commentaire</span>
                        <span class="fw-semibold text-end ms-3" id="confirmAvanceCommentaire">—</span>
                    </div>
                </div>

                <div class="rounded-3 border border-warning-subtle bg-warning bg-opacity-10 px-3 py-2 small">
                    <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                    Caisse utilisable actuelle :
                    <strong>{{ number_format((float) ($montantUtilisable ?? 0), 0, ',', ' ') }} FCFA</strong>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Annuler
                </button>
                <form method="POST" id="confirmAvancePaymentForm" action="">
                    @csrf
                    <button type="submit" class="btn btn-success" id="confirmAvancePaymentSubmit">
                        <i class="bi bi-check2-circle me-1"></i>Confirmer le paiement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
