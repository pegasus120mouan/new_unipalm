<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier l'utilisateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" id="editUserId" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editUserNom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="editUserNom" name="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editUserPrenoms" class="form-label">Prénoms</label>
                            <input type="text" class="form-control" id="editUserPrenoms" name="prenoms" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editUserContact" class="form-label">Contact</label>
                            <input type="text" class="form-control" id="editUserContact" name="contact" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editUserLogin" class="form-label">Login</label>
                            <input type="text" class="form-control" id="editUserLogin" name="login" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editUserRole" class="form-label">Rôle</label>
                            <select class="form-select" id="editUserRole" name="role" required>
                                <option value="">Sélectionner un rôle</option>
                                <option value="collecteur">Collecteur</option>
                                <option value="operateur">Opérateur</option>
                                <option value="caissiere">Caissière</option>
                                <option value="directeur">Directeur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editUserStatut" class="form-label">Statut</label>
                            <select class="form-select" id="editUserStatut" name="statut_compte">
                                <option value="1">Actif</option>
                                <option value="0">Inactif</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="editUserZone" class="form-label">Zone</label>
                            <select class="form-select" id="editUserZone" name="zone_id">
                                <option value="">-- Aucune zone --</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small mb-2">Laissez vide pour conserver le mot de passe actuel</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editUserPassword" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="editUserPassword" name="password">
                        </div>
                        <div class="col-md-6">
                            <label for="editUserPasswordConfirm" class="form-label">Confirmation</label>
                            <input type="password" class="form-control" id="editUserPasswordConfirm" name="password_confirm">
                        </div>
                    </div>
                    <div id="editUserError" class="alert alert-danger mt-3 d-none"></div>
                    <div id="editUserSuccess" class="alert alert-success mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white"><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
