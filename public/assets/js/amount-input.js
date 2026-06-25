(function () {
    function digitsOnly(value) {
        return String(value ?? '').replace(/\D/g, '');
    }

    function formatAmount(value) {
        const digits = digitsOnly(value);

        if (!digits) {
            return '';
        }

        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function parseAmount(value) {
        const digits = digitsOnly(value);

        return digits ? parseInt(digits, 10) : 0;
    }

    function syncAmountInput(displayInput, hiddenInput) {
        const digits = digitsOnly(displayInput.value);
        hiddenInput.value = digits;
        displayInput.value = formatAmount(digits);
    }

    function initAmountInput(displayInput) {
        const hiddenInput = displayInput.parentElement.querySelector('input[type="hidden"][data-amount-target]');

        if (!hiddenInput) {
            return;
        }

        const applyFormat = function () {
            syncAmountInput(displayInput, hiddenInput);
        };

        displayInput.addEventListener('input', applyFormat);
        displayInput.addEventListener('blur', applyFormat);

        if (displayInput.value !== '') {
            applyFormat();
        }

        const form = displayInput.closest('form');

        if (form) {
            form.addEventListener('submit', function () {
                syncAmountInput(displayInput, hiddenInput);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-amount-input]').forEach(initAmountInput);
    });

    window.UnipalmAmount = {
        format: formatAmount,
        parse: parseAmount,
    };
})();
