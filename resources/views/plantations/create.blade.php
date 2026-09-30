@extends('layout.main')

@section('title', 'Enregistrer un planteur')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Enregistrer un planteur</h3>
        <a href="{{ route('plantations.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>
@endsection

@section('content')
    <div id="createError" class="alert alert-danger d-none" role="alert"></div>
    <div id="createSuccess" class="alert alert-success d-none" role="alert"></div>
    <div id="createDoublon" class="alert alert-warning d-none" role="alert"></div>

    <form id="createPlanteurForm">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Identité</div>
                    <div class="card-body">
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
                        <div class="mb-3">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Collecteur</label>
                            <input type="text" class="form-control" id="collecteur" name="collecteur" placeholder="Nom du collecteur">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Le n° de fiche est généré automatiquement à l’enregistrement.
            Un contrôle de doublon est fait sur le nom et prénoms, ou le téléphone.
        </p>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Enregistrer le planteur
            </button>
            <a href="{{ route('plantations.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const formEl = document.getElementById('createPlanteurForm');
            const errorEl = document.getElementById('createError');
            const successEl = document.getElementById('createSuccess');
            const doublonEl = document.getElementById('createDoublon');
            const apiUrl = @json(route('plantations.api'));
            const listUrl = @json(route('plantations.index'));
            const csrfToken = @json(csrf_token());
            let forceCreate = false;

            function payload() {
                return {
                    action: 'create_planteur',
                    nom_prenoms: document.getElementById('nom_prenoms').value,
                    telephone: document.getElementById('telephone').value,
                    piece_identite: document.getElementById('piece_identite').value,
                    date_naissance: document.getElementById('date_naissance').value,
                    lieu_naissance: document.getElementById('lieu_naissance').value,
                    situation_matrimoniale: document.getElementById('situation_matrimoniale').value,
                    nombre_enfants: document.getElementById('nombre_enfants').value,
                    collecteur: document.getElementById('collecteur').value,
                    exploitation: {
                        region: document.getElementById('region').value,
                        sous_prefecture_village: document.getElementById('sous_prefecture_village').value,
                        village: document.getElementById('village').value,
                        latitude: document.getElementById('latitude').value,
                        longitude: document.getElementById('longitude').value,
                    },
                };
            }

            formEl.addEventListener('submit', async function (event) {
                event.preventDefault();
                const submitBtn = formEl.querySelector('button[type="submit"]');
                const original = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...';
                errorEl.classList.add('d-none');
                successEl.classList.add('d-none');
                doublonEl.classList.add('d-none');

                try {
                    const data = payload();
                    if (!forceCreate) {
                        const checkUrl = `${apiUrl}?action=check_doublon`
                            + `&nom_prenoms=${encodeURIComponent(data.nom_prenoms)}`
                            + `&telephone=${encodeURIComponent(data.telephone)}`;
                        const checkRes = await fetch(checkUrl, { cache: 'no-store' });
                        const checkJson = await checkRes.json();
                        const matches = Array.isArray(checkJson?.data?.matches) ? checkJson.data.matches : [];
                        if (checkRes.ok && matches.length > 0) {
                            const lines = matches.slice(0, 8).map(function (item) {
                                const fiche = item.numero_fiche || ('#' + (item.id || ''));
                                return '• ' + fiche + ' — ' + (item.nom_prenoms || '')
                                    + (item.telephone ? ' (' + item.telephone + ')' : '');
                            }).join('\n');
                            const extra = matches.length > 8 ? '\n…' : '';
                            const ok = window.confirm(
                                matches.length + ' planteur(s) existent déjà avec le même nom et prénoms, ou le même téléphone :\n\n'
                                + lines + extra
                                + '\n\nEnregistrer quand même ?'
                            );
                            if (!ok) {
                                doublonEl.textContent = matches.length + ' doublon(s) potentiel(s) détecté(s). Enregistrement annulé.';
                                doublonEl.classList.remove('d-none');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = original;
                                return;
                            }
                            forceCreate = true;
                        }
                    }

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
                    if (!res.ok || json?.success === false) {
                        throw new Error(json?.error || json?.message || 'Enregistrement impossible.');
                    }

                    successEl.textContent = json?.message || 'Planteur enregistré avec succès.';
                    successEl.classList.remove('d-none');
                    setTimeout(function () { window.location.href = listUrl; }, 900);
                } catch (error) {
                    errorEl.textContent = error.message || String(error);
                    errorEl.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = original;
                }
            });
        });
    </script>
@endpush
