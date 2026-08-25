<script>
    window.poidsHasDecimal = function (raw) {
        return /[.,]/.test(String(raw ?? '').trim());
    };

    window.poidsDoesNotEndWithZero = function (raw) {
        const value = String(raw ?? '').trim().replace(/\s/g, '');
        if (value === '') {
            return false;
        }

        return ! /0$/.test(value);
    };

    window.showPoidsForbiddenModal = function (message) {
        let overlay = document.getElementById('poidsDecimalForbiddenOverlay');
        const text = message || 'Enregistrement interdit';

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'poidsDecimalForbiddenOverlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.innerHTML =
                '<div class="poids-decimal-forbidden-dialog">' +
                    '<div class="poids-decimal-forbidden-header">' +
                        '<h5>Enregistrement interdit</h5>' +
                    '</div>' +
                    '<div class="poids-decimal-forbidden-body"></div>' +
                    '<div class="poids-decimal-forbidden-footer">' +
                        '<button type="button" class="btn btn-danger px-4" data-close-poids-decimal>OK</button>' +
                    '</div>' +
                '</div>';

            overlay.addEventListener('click', function (event) {
                if (event.target === overlay || event.target.closest('[data-close-poids-decimal]')) {
                    overlay.style.display = 'none';
                }
            });

            document.body.appendChild(overlay);
        }

        const body = overlay.querySelector('.poids-decimal-forbidden-body');
        if (body) {
            body.textContent = text;
        }

        overlay.style.display = 'flex';
    };

    window.showPoidsDecimalForbiddenModal = function () {
        window.showPoidsForbiddenModal('Enregistrement nombre à virgule interdit');
    };
</script>

<style>
    #poidsDecimalForbiddenOverlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 20000;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.55);
        padding: 1rem;
    }

    #poidsDecimalForbiddenOverlay .poids-decimal-forbidden-dialog {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    #poidsDecimalForbiddenOverlay .poids-decimal-forbidden-header {
        background: #dc3545;
        color: #fff;
        padding: 0.9rem 1.25rem;
    }

    #poidsDecimalForbiddenOverlay .poids-decimal-forbidden-header h5 {
        margin: 0;
        font-size: 1.1rem;
    }

    #poidsDecimalForbiddenOverlay .poids-decimal-forbidden-body {
        padding: 1.75rem 1.25rem;
        text-align: center;
        font-size: 1.15rem;
    }

    #poidsDecimalForbiddenOverlay .poids-decimal-forbidden-footer {
        padding: 0 1.25rem 1.5rem;
        text-align: center;
    }
</style>
