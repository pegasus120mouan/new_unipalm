<div class="modal fade" id="champEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="champEditForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>Modifier le champ <code id="champEditCode" class="text-danger"></code>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="champEditError" class="alert alert-danger d-none"></div>
                <div id="champEditLoader" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-2">Chargement du champ...</div>
                </div>
                <div id="champEditFields">
                    <div class="text-muted small mb-3" id="champEditContext"></div>
                    <div class="mb-3">
                        <label class="form-label" for="champEditType">Type de culture <span class="text-danger">*</span></label>
                        <select class="form-select" id="champEditType" required>
                            <option value="Palmier a huile">Palmier à huile</option>
                            <option value="Hevea">Hévéa</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="champEditAutreGroup">
                        <label class="form-label" for="champEditAutre">Précisez la culture</label>
                        <input type="text" class="form-control" id="champEditAutre" maxlength="100">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="champEditSuperficie">Superficie (ha)</label>
                            <input type="number" class="form-control" id="champEditSuperficie" min="0" step="0.01">
                            <div class="form-text d-none" id="champEditGpsHint"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="champEditAge">Âge (années)</label>
                            <input type="number" class="form-control" id="champEditAge" min="0" step="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="champEditMode">Mode</label>
                            <select class="form-select" id="champEditMode">
                                <option value="">—</option>
                                <option value="Pur">Pur</option>
                                <option value="Associe">Associé</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="champEditProduction">Production estimée (kg)</label>
                            <input type="number" class="form-control" id="champEditProduction" min="0" step="1">
                        </div>
                    </div>

                    <div id="champEditLocalisation">
                        <hr class="my-4">
                        <h6 class="mb-1"><i class="bi bi-geo-alt me-1"></i>Localisation</h6>
                        <div class="form-text mt-0 mb-3" id="champEditLocalisationHint"></div>
                        <div class="mb-3">
                            <label class="form-label" for="champEditRegion">Région</label>
                            <select class="form-select" id="champEditRegion"></select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="champEditSousPrefecture">Sous-préfecture</label>
                                <select class="form-select" id="champEditSousPrefecture"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="champEditVillage">Village</label>
                                <input type="text" class="form-control" id="champEditVillage" maxlength="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary" id="champEditSubmit">
                    <i class="bi bi-check-lg"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const apiUrl = @json(route('plantations.api'));
        const csrfToken = @json(csrf_token());
        const knownTypes = ['Palmier a huile', 'Hevea', 'Autre'];

        const modalEl = document.getElementById('champEditModal');
        const form = document.getElementById('champEditForm');
        const errorEl = document.getElementById('champEditError');
        const loaderEl = document.getElementById('champEditLoader');
        const fieldsEl = document.getElementById('champEditFields');
        const submitBtn = document.getElementById('champEditSubmit');
        const typeSelect = document.getElementById('champEditType');
        const autreGroup = document.getElementById('champEditAutreGroup');
        const autreInput = document.getElementById('champEditAutre');

        let modalInstance = null;
        let current = null;

        function modal() {
            if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
            return modalInstance;
        }

        function showError(message) {
            errorEl.textContent = message || '';
            errorEl.classList.toggle('d-none', !message);
        }

        function valueOrEmpty(value) {
            return value === null || value === undefined ? '' : value;
        }

        function toggleAutre() {
            autreGroup.classList.toggle('d-none', typeSelect.value !== 'Autre');
        }
        typeSelect.addEventListener('change', toggleAutre);

        function localisationOf(champ) {
            return {
                region: String(champ.region ?? '').trim(),
                sous_prefecture_village: String(champ.sous_prefecture ?? champ.sous_prefecture_village ?? '').trim(),
                village: String(champ.village ?? '').trim(),
            };
        }

        const referentielUrl = @json(route('plantations.referentiel.regions'));
        const regionSelect = document.getElementById('champEditRegion');
        const spSelect = document.getElementById('champEditSousPrefecture');
        let regionsPromise = null;
        const sousPrefecturesPromises = {};
        let localisationToken = 0;
        let localisationReady = false;

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function normalize(value) {
            return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        }

        async function fetchList(url, attempts = 3) {
            for (let attempt = 1; ; attempt++) {
                try {
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok || !json?.success) throw new Error(json?.error || 'Liste des régions indisponible.');
                    return Array.isArray(json.data) ? json.data : [];
                } catch (error) {
                    if (attempt >= attempts) throw error;
                    await new Promise((resolve) => setTimeout(resolve, 800 * attempt));
                }
            }
        }

        function loadRegions() {
            if (!regionsPromise) {
                regionsPromise = fetchList(referentielUrl).catch((error) => { regionsPromise = null; throw error; });
            }
            return regionsPromise;
        }

        function loadSousPrefectures(regionId) {
            if (!sousPrefecturesPromises[regionId]) {
                sousPrefecturesPromises[regionId] = fetchList(`${referentielUrl}?region_id=${encodeURIComponent(regionId)}`)
                    .catch((error) => { delete sousPrefecturesPromises[regionId]; throw error; });
            }
            return sousPrefecturesPromises[regionId];
        }

        /** Remplit la liste ; une valeur actuelle absente de la liste reste sélectionnable */
        function fillSelect(select, items, current, placeholder) {
            const match = items.find((item) => normalize(item.nom) === normalize(current));
            let html = `<option value="">${escapeHtml(placeholder)}</option>`;
            if (current && !match) {
                html += `<option value="${escapeHtml(current)}">${escapeHtml(current)} (valeur actuelle)</option>`;
            }
            html += items.map((item) => `<option value="${escapeHtml(item.nom)}" data-id="${escapeHtml(item.id)}">${escapeHtml(item.nom)}</option>`).join('');
            select.innerHTML = html;
            select.value = match ? match.nom : (current || '');
        }

        function setLoadingSelect(select) {
            select.disabled = true;
            select.innerHTML = '<option value="">Chargement...</option>';
        }

        async function refreshSousPrefectures(current, token) {
            const regionId = regionSelect.selectedOptions[0]?.dataset.id;
            if (!regionId) {
                fillSelect(spSelect, [], current, regionSelect.value ? '—' : "Choisissez d'abord une région");
                spSelect.disabled = !current;
                return;
            }
            setLoadingSelect(spSelect);
            const items = await loadSousPrefectures(regionId);
            if (token !== localisationToken) return;
            fillSelect(spSelect, items, current, 'Choisir une sous-préfecture');
            spSelect.disabled = false;
        }

        async function setupLocalisation(localisation) {
            const token = ++localisationToken;
            localisationReady = false;
            setLoadingSelect(regionSelect);
            setLoadingSelect(spSelect);
            try {
                const regions = await loadRegions();
                if (token !== localisationToken) return;
                fillSelect(regionSelect, regions, localisation.region, 'Choisir une région');
                regionSelect.disabled = false;
                await refreshSousPrefectures(localisation.sous_prefecture_village, token);
                if (token === localisationToken) localisationReady = true;
            } catch (error) {
                if (token !== localisationToken) return;
                // Listes indisponibles : on garde les valeurs actuelles, sans modification possible
                fillSelect(regionSelect, [], localisation.region, '—');
                fillSelect(spSelect, [], localisation.sous_prefecture_village, '—');
                document.getElementById('champEditLocalisationHint').textContent =
                    `${error.message || error} La région et la sous-préfecture ne peuvent pas être modifiées pour le moment.`;
            }
        }

        regionSelect.addEventListener('change', function () {
            refreshSousPrefectures('', localisationToken).catch((error) => showError(error.message || String(error)));
        });

        /** Tant que les listes ne sont pas chargées, la région et la sous-préfecture restent inchangées */
        function localisationSaisie(avant) {
            return {
                region: localisationReady ? regionSelect.value.trim() : avant.region,
                sous_prefecture_village: localisationReady ? spSelect.value.trim() : avant.sous_prefecture_village,
                village: document.getElementById('champEditVillage').value.trim(),
            };
        }

        async function postApi(payload) {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json?.success) throw new Error(json?.error || json?.message || 'Modification impossible.');
            return json;
        }

        function fill(champ) {
            const code = champ.type_culture_code || '';
            const type = knownTypes.includes(code) ? code : (code ? 'Autre' : 'Palmier a huile');
            document.getElementById('champEditCode').textContent = champ.numero_champ || '';
            document.getElementById('champEditContext').textContent = [champ.exploitation_label, champ.planteur_nom]
                .filter(Boolean).join(' • ');
            typeSelect.value = type;
            autreInput.value = type === 'Autre' ? (champ.autre_culture || (knownTypes.includes(code) ? '' : code)) : '';
            toggleAutre();
            document.getElementById('champEditSuperficie').value = valueOrEmpty(champ.superficie_ha);
            document.getElementById('champEditAge').value = valueOrEmpty(champ.age_culture);
            document.getElementById('champEditMode').value = ['Pur', 'Associe'].includes(champ.mode_culture) ? champ.mode_culture : '';
            document.getElementById('champEditProduction').value = valueOrEmpty(champ.production_estimee_kg);

            const localisation = localisationOf(champ);
            document.getElementById('champEditLocalisation').classList.toggle('d-none', !champ.exploitation_id);
            document.getElementById('champEditLocalisationHint').textContent =
                `Localisation de l'exploitation ${champ.exploitation_numero || ''} : elle s'applique à tous les champs de cette exploitation.`;
            document.getElementById('champEditVillage').value = localisation.village;
            setupLocalisation(localisation);

            const gps = (Array.isArray(champ.parcelles) ? champ.parcelles : [])
                .reduce((sum, p) => sum + (Number(p.superficie_calculee) || 0), 0);
            const gpsHint = document.getElementById('champEditGpsHint');
            gpsHint.textContent = gps > 0 ? `Superficie GPS de la parcelle : ${gps.toFixed(2)} ha` : '';
            gpsHint.classList.toggle('d-none', gps <= 0);
        }

        async function fetchChamp(planteurId, champId) {
            const res = await fetch(
                `${apiUrl}?action=champ&id=${encodeURIComponent(planteurId)}&champ_id=${encodeURIComponent(champId)}`,
                { headers: { Accept: 'application/json' }, cache: 'no-store' }
            );
            const json = await res.json();
            if (!res.ok || !json?.success) throw new Error(json?.error || json?.message || 'Impossible de charger le champ.');
            return Object.assign({ planteur_nom: json.data?.planteur?.nom_prenoms || '' }, json.data?.champ || {});
        }

        /**
         * options : { planteurId, champId, champ (facultatif : sinon chargé depuis l'API), onDone(champ) }
         */
        window.openChampEdit = async function (options) {
            current = options;
            showError('');
            submitBtn.disabled = true;
            modal().show();

            if (options.champ) {
                fill(options.champ);
                submitBtn.disabled = false;
                return;
            }

            fieldsEl.classList.add('d-none');
            loaderEl.classList.remove('d-none');
            try {
                current.champ = await fetchChamp(options.planteurId, options.champId);
                fill(current.champ);
                fieldsEl.classList.remove('d-none');
                submitBtn.disabled = false;
            } catch (error) {
                showError(error.message || String(error));
            } finally {
                loaderEl.classList.add('d-none');
            }
        };

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!current?.champ) return;

            const type = typeSelect.value;
            const autre = autreInput.value.trim();
            if (type === 'Autre' && !autre) {
                showError('Précisez le nom de la culture.');
                return;
            }
            showError('');

            const original = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...';
            try {
                const avant = localisationOf(current.champ);
                const apres = localisationSaisie(avant);
                const localisationModifiee = current.champ.exploitation_id
                    && Object.keys(apres).some((key) => apres[key] !== avant[key]);

                if (localisationModifiee && apres.region && !apres.sous_prefecture_village) {
                    throw new Error('Choisissez la sous-préfecture.');
                }

                if (localisationModifiee) {
                    await postApi(Object.assign({
                        action: 'update_exploitation',
                        planteur_id: current.planteurId,
                        exploitation_id: current.champ.exploitation_id,
                    }, apres));
                }

                await postApi({
                    action: 'update_champ',
                    planteur_id: current.planteurId,
                    champ_id: current.champ.id ?? current.champId,
                    type_culture: type,
                    autre_culture: type === 'Autre' ? autre : '',
                    superficie_ha: document.getElementById('champEditSuperficie').value,
                    age_culture: document.getElementById('champEditAge').value,
                    mode_culture: document.getElementById('champEditMode').value,
                    production_estimee_kg: document.getElementById('champEditProduction').value,
                });

                modal().hide();
                if (typeof current.onDone === 'function') current.onDone(current.champ);
            } catch (error) {
                showError(error.message || String(error));
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            fieldsEl.classList.remove('d-none');
            loaderEl.classList.add('d-none');
        });
    });
</script>
