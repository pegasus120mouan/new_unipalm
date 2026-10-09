<div class="modal fade" id="transfertPlanteurModal" tabindex="-1" aria-labelledby="transfertPlanteurModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="transfertPlanteurModalLabel">
                    <i class="bi bi-arrow-left-right me-2"></i> Transférer le planteur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="transfertPlanteurForm" autocomplete="off">
                <div class="modal-body">
                    <div id="transfertError" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <div class="fw-bold" id="transfertPlanteurNom"></div>
                        <code class="text-danger" id="transfertPlanteurFiche"></code>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Collecteur actuel</label>
                        <div class="form-control bg-light" id="transfertAncienCollecteur">—</div>
                    </div>

                    <div class="mb-3 position-relative">
                        <label for="transfertCollecteurSearch" class="form-label">Nouveau collecteur</label>
                        <input type="text" id="transfertCollecteurSearch" class="form-control"
                            placeholder="Tapez le nom du collecteur..." required>
                        <input type="hidden" id="transfertCollecteurId">
                        <div id="transfertCollecteurSuggestions" class="list-group position-absolute w-100 shadow-sm"
                            style="z-index: 1070; display: none; max-height: 220px; overflow-y: auto;"></div>
                        <div class="form-text" id="transfertCollecteurHint">Chargement des collecteurs...</div>
                    </div>

                    <div class="mb-3">
                        <label for="transfertMotif" class="form-label">Motif <span class="text-muted small">(facultatif)</span></label>
                        <textarea id="transfertMotif" class="form-control" rows="2" maxlength="1000"
                            placeholder="Ex. : changement de zone, départ du collecteur..."></textarea>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        La fiche, ses <strong id="transfertNbChamps">0</strong> champ(s)
                        (<span id="transfertSuperficie">0</span> ha) et leurs parcelles restent attachés au planteur.
                        Le transfert est enregistré dans l'historique avec l'état des champs à cette date.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning" id="transfertSubmitBtn">
                        <i class="bi bi-arrow-left-right"></i> Transférer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const collecteursUrl = @json(route('plantations.transfert.collecteurs'));
        const transfertUrlTemplate = @json(url('/plantations'));
        const csrfToken = @json(csrf_token());

        const modalEl = document.getElementById('transfertPlanteurModal');
        const form = document.getElementById('transfertPlanteurForm');
        const errorEl = document.getElementById('transfertError');
        const searchInput = document.getElementById('transfertCollecteurSearch');
        const hiddenInput = document.getElementById('transfertCollecteurId');
        const suggestions = document.getElementById('transfertCollecteurSuggestions');
        const hint = document.getElementById('transfertCollecteurHint');
        const submitBtn = document.getElementById('transfertSubmitBtn');

        let collecteurs = null;
        let current = null;
        let modalInstance = null;

        function modal() {
            if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
            return modalInstance;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        let collecteursPromise = null;

        function loadCollecteurs() {
            if (!collecteursPromise) {
                collecteursPromise = fetch(collecteursUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
                    .then(async (res) => {
                        const json = await res.json();
                        if (!res.ok || !json.success) throw new Error(json.error || 'Impossible de charger les collecteurs.');
                        collecteurs = json.data.collecteurs || [];
                        return collecteurs;
                    })
                    .catch((error) => {
                        collecteursPromise = null;
                        throw error;
                    });
            }
            return collecteursPromise;
        }

        loadCollecteurs().catch(() => {});

        function available() {
            return (collecteurs || []).filter((c) => String(c.id) !== String(current?.collecteurId || ''));
        }

        function showSuggestions() {
            if (!collecteurs) {
                suggestions.innerHTML = '<div class="list-group-item text-muted"><span class="spinner-border spinner-border-sm me-1"></span> Chargement des collecteurs...</div>';
                suggestions.style.display = 'block';
                return;
            }
            const term = searchInput.value.trim().toLowerCase();
            const matches = available()
                .filter((c) => !term || c.nom.toLowerCase().includes(term) || (c.zone || '').toLowerCase().includes(term))
                .slice(0, 15);

            suggestions.innerHTML = matches.length
                ? matches.map((c) => `
                    <button type="button" class="list-group-item list-group-item-action" data-id="${escapeHtml(c.id)}">
                        <strong>${escapeHtml(c.nom)}</strong>
                        <small class="text-muted ms-1">${escapeHtml([c.role, c.zone].filter(Boolean).join(' • '))}</small>
                    </button>`).join('')
                : '<div class="list-group-item text-muted">Aucun collecteur trouvé</div>';
            suggestions.style.display = 'block';
        }

        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            showSuggestions();
        });
        searchInput.addEventListener('focus', showSuggestions);
        suggestions.addEventListener('mousedown', function (event) {
            const btn = event.target.closest('button[data-id]');
            if (!btn) return;
            event.preventDefault();
            const collecteur = available().find((c) => String(c.id) === btn.dataset.id);
            if (!collecteur) return;
            hiddenInput.value = collecteur.id;
            searchInput.value = collecteur.nom;
            suggestions.style.display = 'none';
        });
        document.addEventListener('click', function (event) {
            if (!searchInput.contains(event.target) && !suggestions.contains(event.target)) {
                suggestions.style.display = 'none';
            }
        });

        window.openTransfertPlanteur = function (planteur) {
            current = planteur;
            errorEl.classList.add('d-none');
            form.reset();
            hiddenInput.value = '';
            submitBtn.disabled = false;

            document.getElementById('transfertPlanteurNom').textContent = planteur.nom || '—';
            document.getElementById('transfertPlanteurFiche').textContent = planteur.fiche || '';
            document.getElementById('transfertAncienCollecteur').textContent = planteur.collecteurNom || '—';
            document.getElementById('transfertNbChamps').textContent = planteur.nbChamps ?? 0;
            document.getElementById('transfertSuperficie').textContent = Number(planteur.superficie || 0).toFixed(2);

            hint.textContent = 'Chargement des collecteurs...';
            loadCollecteurs()
                .then(() => {
                    hint.textContent = `${available().length} collecteur(s) disponible(s).`;
                    if (document.activeElement === searchInput) showSuggestions();
                })
                .catch((error) => {
                    hint.textContent = error.message;
                    suggestions.style.display = 'none';
                });

            modal().show();
        };

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            errorEl.classList.add('d-none');

            if (!hiddenInput.value) {
                errorEl.textContent = 'Sélectionnez le nouveau collecteur dans la liste.';
                errorEl.classList.remove('d-none');
                return;
            }

            const original = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Transfert...';

            try {
                const res = await fetch(`${transfertUrlTemplate}/${encodeURIComponent(current.id)}/transfert`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        nouveau_collecteur_id: Number(hiddenInput.value),
                        motif: document.getElementById('transfertMotif').value,
                    }),
                });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    const firstError = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                    throw new Error(firstError || json.error || json.message || 'Transfert impossible.');
                }

                modal().hide();
                if (typeof current.onDone === 'function') current.onDone(json);
            } catch (error) {
                errorEl.textContent = error.message || String(error);
                errorEl.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
            }
        });
    });
</script>
