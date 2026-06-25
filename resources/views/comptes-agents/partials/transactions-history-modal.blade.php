<div class="modal fade" id="transactionsHistoryModal" tabindex="-1" aria-labelledby="transactionsHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('comptes-agents.transactions.pdf', $agent) }}" method="GET" target="_blank">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold" id="transactionsHistoryModalLabel">
                            Historique des transactions
                        </h5>
                        <div class="text-muted small">Sélectionnez une période pour consulter les paiements</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="mb-3">
                        <label for="transactions_date_debut" class="form-label">Date début</label>
                        <input type="date" name="date_debut" id="transactions_date_debut" class="form-control"
                            value="{{ now()->subDays(30)->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="transactions_date_fin" class="form-label">Date fin</label>
                        <input type="date" name="date_fin" id="transactions_date_fin" class="form-control"
                            value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Voir l'historique</button>
                </div>
            </form>
        </div>
    </div>
</div>
