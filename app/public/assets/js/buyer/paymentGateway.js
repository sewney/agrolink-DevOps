function securePayNormalizeCardNumber(value) {
    return String(value || '').replace(/\D/g, '').slice(0, 19);
}

function securePayFormatCardNumber(value) {
    return securePayNormalizeCardNumber(value).replace(/(.{4})/g, '$1 ').trim();
}

function securePayIsLuhnValid(cardNumber) {
    return /^\d{12,19}$/.test(cardNumber);
}

function securePayShowError(message) {
    const errorEl = document.getElementById('securePayError');
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.classList.remove('is-hidden');
}

function securePayClearError() {
    const errorEl = document.getElementById('securePayError');
    if (!errorEl) return;
    errorEl.textContent = '';
    errorEl.classList.add('is-hidden');
}

function securePayValidateForm() {
    const holder = (document.getElementById('spCardHolder')?.value || '').trim();
    const cardNumber = securePayNormalizeCardNumber(document.getElementById('spCardNumber')?.value || '');
    const monthRaw = (document.getElementById('spExpiryMonth')?.value || '').replace(/\D/g, '');
    const yearRaw = (document.getElementById('spExpiryYear')?.value || '').replace(/\D/g, '');
    const cvv = (document.getElementById('spCvv')?.value || '').replace(/\D/g, '');

    if (!/^[A-Za-z][A-Za-z\s.'-]{1,79}$/.test(holder)) {
        securePayShowError('Enter a valid card holder name.');
        return false;
    }

    if (!securePayIsLuhnValid(cardNumber)) {
        securePayShowError('Enter a valid card number.');
        return false;
    }

    const month = Number(monthRaw);
    if (!monthRaw || Number.isNaN(month) || month < 1 || month > 12) {
        securePayShowError('Expiry month must be between 01 and 12.');
        return false;
    }

    if (!/^\d{2}$/.test(yearRaw)) {
        securePayShowError('Expiry year must be 2 digits.');
        return false;
    }

    const fullYear = Number(`20${yearRaw}`);
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth() + 1;

    if (fullYear < currentYear || (fullYear === currentYear && month < currentMonth)) {
        securePayShowError('Card is expired.');
        return false;
    }

    if (!/^\d{3,4}$/.test(cvv)) {
        securePayShowError('CVV must be 3 or 4 digits.');
        return false;
    }

    securePayClearError();
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('securePayForm');
    const submitBtn = document.getElementById('securePaySubmitBtn');
    const processing = document.getElementById('securePayProcessing');

    const cardInput = document.getElementById('spCardNumber');
    if (cardInput) {
        cardInput.addEventListener('input', () => {
            cardInput.value = securePayFormatCardNumber(cardInput.value);
        });
    }

    const monthInput = document.getElementById('spExpiryMonth');
    if (monthInput) {
        monthInput.addEventListener('input', () => {
            monthInput.value = monthInput.value.replace(/\D/g, '').slice(0, 2);
        });
    }

    const yearInput = document.getElementById('spExpiryYear');
    if (yearInput) {
        yearInput.addEventListener('input', () => {
            yearInput.value = yearInput.value.replace(/\D/g, '').slice(0, 2);
        });
    }

    const cvvInput = document.getElementById('spCvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', () => {
            cvvInput.value = cvvInput.value.replace(/\D/g, '').slice(0, 4);
        });
    }

    if (!form) return;

    form.addEventListener('submit', event => {
        if (!securePayValidateForm()) {
            event.preventDefault();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing payment...';
        }

        if (processing) {
            processing.classList.remove('is-hidden');
        }
    });
});
