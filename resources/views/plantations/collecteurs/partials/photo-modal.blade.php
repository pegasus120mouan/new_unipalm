<div class="modal fade" id="changePhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-camera me-2"></i>Changer la photo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="changePhotoForm" enctype="multipart/form-data">
                <div class="modal-body text-center">
                    <input type="hidden" id="photoUserId" name="user_id">
                    <img id="currentPhoto" src="" alt="Photo actuelle" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;border:4px solid #435ebe;">
                    <p id="photoUserName" class="fw-semibold"></p>
                    <label for="newPhoto" class="btn btn-outline-primary">
                        <i class="bi bi-upload"></i> Sélectionner une photo
                    </label>
                    <input type="file" id="newPhoto" name="photo" accept="image/*" class="d-none">
                    <div id="photoPreviewContainer" class="mt-3 d-none">
                        <p class="text-muted small">Aperçu :</p>
                        <img id="photoPreview" src="" alt="Aperçu" class="rounded-circle" style="width:120px;height:120px;object-fit:cover;">
                    </div>
                    <div id="photoError" class="alert alert-danger mt-3 d-none"></div>
                    <div id="photoSuccess" class="alert alert-success mt-3 d-none"></div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="savePhotoBtn" class="btn btn-success" disabled><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
