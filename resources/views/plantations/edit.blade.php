@extends('layout.main')

@section('title', 'Modifier le planteur')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Modifier le planteur</h3>
        <a href="{{ route('plantations.show', $planteurId) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
@endsection

@section('content')
    <div id="editError" class="alert alert-danger d-none" role="alert"></div>
    <div id="editSuccess" class="alert alert-success d-none" role="alert"></div>

    <div id="editLoader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-3">Chargement...</div>
    </div>

    <form id="editForm" class="d-none">
        <input type="hidden" name="id" id="planteurId">

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Identité</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">N° fiche</label>
                            <input type="text" class="form-control" id="numero_fiche" name="numero_fiche" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom & prénoms <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_prenoms" name="nom_prenoms" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="telephone" name="telephone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pièce d'identité</label>
                            <input type="text" class="form-control" id="piece_identite" name="piece_identite">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lieu de naissance</label>
                            <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Situation matrimoniale</label>
                            <select class="form-select" id="situation_matrimoniale" name="situation_matrimoniale">
                                <option value="">—</option>
                                <option value="Célibataire">Célibataire</option>
                                <option value="Marié(e)">Marié(e)</option>
                                <option value="Divorcé(e)">Divorcé(e)</option>
                                <option value="Veuf(ve)">Veuf(ve)</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nombre d'enfants</label>
                            <input type="number" min="0" class="form-control" id="nombre_enfants" name="nombre_enfants">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Exploitation</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Région</label>
                            <input type="text" class="form-control" id="region" name="region">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sous-préfecture</label>
                            <input type="text" class="form-control" id="sous_prefecture_village" name="sous_prefecture_village">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Village</label>
                            <input type="text" class="form-control" id="village" name="village">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Enregistrer les modifications
            </button>
            <a href="{{ route('plantations.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const planteurId = @json($planteurId);
            const apiUrl = @json(route('plantations.api'));
            const csrfToken = @json(csrf_token());
            const showUrl = @json(route('plantations.show', $planteurId));
            const loaderEl = document.getElementById('editLoader');
            const formEl = document.getElementById('editForm');
            const errorEl = document.getElementById('editError');
            const successEl = document.getElementById('editSuccess');

            function formatDateForInput(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                if (Number.isNaN(d.getTime())) return '';
                return d.toISOString().slice(0, 10);
            }

            function fillForm(planteur) {
                document.getElementById('planteurId').value = planteur.id || '';
                document.getElementById('numero_fiche').value = planteur.numero_fiche || '';
                document.getElementById('nom_prenoms').value = planteur.nom_prenoms || '';
                document.getElementById('telephone').value = planteur.telephone || '';
                document.getElementById('piece_identite').value = planteur.piece_identite || '';
                document.getElementById('date_naissance').value = formatDateForInput(planteur.date_naissance);
                document.getElementById('lieu_naissance').value = planteur.lieu_naissance || '';
                document.getElementById('situation_matrimoniale').value = planteur.situation_matrimoniale || '';
                document.getElementById('nombre_enfants').value = planteur.nombre_enfants ?? '';

                const expl = planteur.exploitation || {};
                document.getElementById('region').value = expl.region || '';
                document.getElementById('sous_prefecture_village').value = expl.sous_prefecture_village || '';
                document.getElementById('village').value = expl.village || '';
                document.getElementById('latitude').value = expl.latitude || '';
                document.getElementById('longitude').value = expl.longitude || '';
            }

            async function load() {
                try {
                    const res = await fetch(`${apiUrl}?action=planteurs&id=${encodeURIComponent(planteurId)}`, { cache: 'no-store' });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json.error || json.message || 'Erreur API');
                    const planteur = json.data?.planteurs?.[0] || json.data;
                    if (!planteur?.id) throw new Error('Planteur introuvable.');
                    fillForm(planteur);
                    loaderEl.classList.add('d-none');
                    formEl.classList.remove('d-none');
                } catch (e) {
                    loaderEl.classList.add('d-none');
                    errorEl.textContent = e.message || String(e);
                    errorEl.classList.remove('d-none');
                }
            }

            formEl.addEventListener('submit', async function (e) {
                e.preventDefault();
                const submitBtn = formEl.querySelector('button[type="submit"]');
                const original = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...';
                errorEl.classList.add('d-none');
                successEl.classList.add('d-none');

                try {
                    const data = {
                        action: 'update_planteur',
                        id: document.getElementById('planteurId').value,
                        nom_prenoms: document.getElementById('nom_prenoms').value,
                        telephone: document.getElementById('telephone').value,
                        piece_identite: document.getElementById('piece_identite').value,
                        date_naissance: document.getElementById('date_naissance').value,
                        lieu_naissance: document.getElementById('lieu_naissance').value,
                        situation_matrimoniale: document.getElementById('situation_matrimoniale').value,
                        nombre_enfants: document.getElementById('nombre_enfants').value,
                        exploitation: {
                            region: document.getElementById('region').value,
                            sous_prefecture_village: document.getElementById('sous_prefecture_village').value,
                            village: document.getElementById('village').value,
                            latitude: document.getElementById('latitude').value,
                            longitude: document.getElementById('longitude').value,
                        },
                    };

                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(data),
                    });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json.error || json.message || 'Mise à jour impossible');

                    successEl.textContent = 'Planteur modifié avec succès.';
                    successEl.classList.remove('d-none');
                    setTimeout(() => { window.location.href = showUrl; }, 1000);
                } catch (err) {
                    errorEl.textContent = err.message || String(err);
                    errorEl.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = original;
                }
            });

            load();
        });
    </script>
@endpush
