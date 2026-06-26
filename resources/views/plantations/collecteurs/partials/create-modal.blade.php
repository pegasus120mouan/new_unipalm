<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Enregistrer un collecteur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="createUserForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userNom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="userNom" name="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userPrenoms" class="form-label">Prénoms</label>
                            <input type="text" class="form-control" id="userPrenoms" name="prenoms" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userContact" class="form-label">Contact</label>
                            <input type="text" class="form-control" id="userContact" name="contact" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userLogin" class="form-label">Login</label>
                            <input type="text" class="form-control" id="userLogin" name="login" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userPassword" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="userPassword" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userPasswordConfirm" class="form-label">Confirmation</label>
                            <input type="password" class="form-control" id="userPasswordConfirm" name="password_confirm" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userRole" class="form-label">Rôle</label>
                            <select class="form-select" id="userRole" name="role" required>
                                <option value="">Sélectionner un rôle</option>
                                <option value="collecteur">Collecteur</option>
                                <option value="operateur">Opérateur</option>
                                <option value="caissiere">Caissière</option>
                                <option value="directeur">Directeur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="userZone" class="form-label">Zone</label>
                            <select class="form-select" id="userZone" name="zone_id">
                                <option value="">-- Aucune zone --</option>
                            </select>
                        </div>
                    </div>
                    <div id="createUserError" class="alert alert-danger mt-3 d-none"></div>
                    <div id="createUserSuccess" class="alert alert-success mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
