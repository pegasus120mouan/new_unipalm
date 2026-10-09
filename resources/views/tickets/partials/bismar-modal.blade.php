<div id="bismarOverlay" role="dialog" aria-modal="true" aria-labelledby="bismarTitle">
    <div class="bismar-dialog">
        <div class="bismar-header">
            <h5 id="bismarTitle"><i class="bi bi-question-circle me-2"></i>Ticket BISMAR</h5>
        </div>
        <div class="bismar-body">
            <p class="mb-3 text-center fs-6">Ce ticket est-il un ticket <strong>BISMAR</strong> ?</p>
            <div class="d-flex justify-content-center gap-3 mb-3">
                <button type="button" class="btn btn-outline-success px-4" data-bismar-choice="1">
                    <i class="bi bi-check-lg"></i> Oui
                </button>
                <button type="button" class="btn btn-outline-secondary px-4" data-bismar-choice="0">
                    <i class="bi bi-x-lg"></i> Non
                </button>
            </div>
            <div id="bismarCodeGroup" class="d-none">
                <label for="bismarCodeInput" class="form-label">Code transfert</label>
                <input type="text" id="bismarCodeInput" class="form-control" maxlength="255"
                    autocomplete="off" placeholder="Entrez le code transfert">
                <div class="invalid-feedback">Veuillez saisir le code transfert.</div>
            </div>
            <div id="bismarChoiceError" class="text-danger small text-center d-none">
                Veuillez répondre Oui ou Non.
            </div>
        </div>
        <div class="bismar-footer">
            <button type="button" class="btn btn-primary" id="bismarConfirmBtn">
                <i class="bi bi-save"></i> Enregistrer
            </button>
            <button type="button" class="btn btn-secondary" id="bismarCancelBtn">Annuler</button>
        </div>
    </div>
</div>

<style>
    #bismarOverlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 20000;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.55);
        padding: 1rem;
    }

    #bismarOverlay .bismar-dialog {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    #bismarOverlay .bismar-header {
        background: #435ebe;
        color: #fff;
        padding: 0.9rem 1.25rem;
    }

    #bismarOverlay .bismar-header h5 {
        margin: 0;
        font-size: 1.1rem;
        color: #fff;
    }

    #bismarOverlay .bismar-body {
        padding: 1.5rem 1.25rem 1rem;
    }

    #bismarOverlay .bismar-footer {
        padding: 0 1.25rem 1.25rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
</style>

<script>
    window.setupBismarPrompt = function (config) {
        const form = config.form;
        const usineInput = config.usineInput;
        const bismarInput = config.bismarInput;
        const codeInput = config.codeInput;
        const usines = config.usines || [];

        const overlay = document.getElementById('bismarOverlay');
        // Bootstrap's modal focus trap steals focus from anything outside the open modal.
        form.closest('.modal')?.appendChild(overlay);
        const codeGroup = document.getElementById('bismarCodeGroup');
        const codeField = document.getElementById('bismarCodeInput');
        const choiceError = document.getElementById('bismarChoiceError');
        const choiceButtons = overlay.querySelectorAll('[data-bismar-choice]');
        let choice = null;
        let confirmed = false;

        function usineRequiresBismar() {
            const usine = usines.find((item) => String(item.id) === String(usineInput.value));
            return Boolean(usine && usine.bismar);
        }

        function setChoice(value) {
            choice = value;
            choiceError.classList.add('d-none');
            choiceButtons.forEach((button) => {
                const active = button.dataset.bismarChoice === value;
                const color = button.dataset.bismarChoice === '1' ? 'success' : 'secondary';
                button.classList.toggle('btn-' + color, active);
                button.classList.toggle('btn-outline-' + color, !active);
            });
            codeGroup.classList.toggle('d-none', value !== '1');
            if (value === '1') {
                codeField.focus();
            } else {
                codeField.classList.remove('is-invalid');
            }
        }

        function open() {
            setChoice(bismarInput.value === '1' || bismarInput.value === '0' ? bismarInput.value : null);
            codeField.value = codeInput.value;
            codeField.classList.remove('is-invalid');
            overlay.style.display = 'flex';
        }

        function close() {
            overlay.style.display = 'none';
        }

        choiceButtons.forEach((button) => {
            button.addEventListener('click', () => setChoice(button.dataset.bismarChoice));
        });

        codeField.addEventListener('input', () => codeField.classList.remove('is-invalid'));
        codeField.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                document.getElementById('bismarConfirmBtn').click();
            }
        });

        document.getElementById('bismarCancelBtn').addEventListener('click', close);
        overlay.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });

        document.getElementById('bismarConfirmBtn').addEventListener('click', () => {
            if (choice === null) {
                choiceError.classList.remove('d-none');
                return;
            }

            const code = codeField.value.trim();
            if (choice === '1' && code === '') {
                codeField.classList.add('is-invalid');
                codeField.focus();
                return;
            }

            bismarInput.value = choice;
            codeInput.value = choice === '1' ? code : '';
            confirmed = true;
            close();

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });

        form.addEventListener('submit', (event) => {
            if (!usineRequiresBismar()) {
                bismarInput.value = '';
                codeInput.value = '';
                return;
            }

            if (confirmed) {
                confirmed = false;
                return;
            }

            event.preventDefault();
            open();
        });
    };
</script>
