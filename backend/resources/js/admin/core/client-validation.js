import { t } from './config.js';
import { nameToKey } from './dom.js';

/**
 * Step 2 of the protocol (Plan §5.3): browser constraint validation (required, maxlength, type, min/max,
 * pattern) with messages from the admin i18n bundle (the browser's own messages follow the browser language,
 * not the panel language), plus `data-max-kb` file size checks.
 *
 * @returns {Record<string, string[]>} field key => messages
 */
export function clientValidate(form) {
    const errors = {};
    const fields = form.querySelectorAll('input, select, textarea');

    for (const field of fields) {
        const key = field.dataset.errorKey || nameToKey(field.name);

        if (!key || field.disabled || field.closest('[data-skip-client-validation]')) {
            continue;
        }

        const message = messageFor(field);

        if (message && !errors[key]) {
            errors[key] = [message];
        }
    }

    return errors;
}

function messageFor(field) {
    if (field.type === 'file' && field.dataset.maxKb && field.files?.length) {
        const max = Number(field.dataset.maxKb) * 1024;

        if (Array.from(field.files).some((file) => file.size > max)) {
            return t('validation.file_too_large', { max: field.dataset.maxKb });
        }
    }

    if (!field.willValidate || field.checkValidity()) {
        return null;
    }

    const v = field.validity;

    if (v.valueMissing) {
        return t('validation.required');
    }

    if (v.typeMismatch) {
        return t(field.type === 'email' ? 'validation.email' : field.type === 'url' ? 'validation.url' : 'validation.invalid');
    }

    if (v.tooLong) {
        return t('validation.max_length', { max: field.maxLength });
    }

    if (v.tooShort) {
        return t('validation.min_length', { min: field.minLength });
    }

    if (v.rangeUnderflow) {
        return t('validation.min_value', { min: field.min });
    }

    if (v.rangeOverflow) {
        return t('validation.max_value', { max: field.max });
    }

    if (v.stepMismatch) {
        return t('validation.step');
    }

    if (v.badInput) {
        return t('validation.number');
    }

    return t('validation.invalid');
}
