import { cssEscape, keyToName, nameToKey } from './dom.js';
import { t } from './config.js';

/**
 * Inline error rendering for <x-admin.form> (Plan §5.3). Laravel error keys use dot notation
 * (`title.ar`, `items.0.name`, `tags.1`); fields render `data-field="<key>"` wrappers with a
 * `[data-error-for="<key>"]` slot. Resolution order for a key:
 *   1. an explicit [data-error-for=key] slot;
 *   2. the input named like the key (dot → bracket, also `name[]` for list items) → its wrapper's slot;
 *   3. the parent key (drop the last segment) — e.g. `items.0.name` → `items.0` → `items`;
 *   4. otherwise the message is listed in the form's [data-error-summary].
 */

function slotFor(form, key) {
    let current = key;

    while (current) {
        const slot = form.querySelector(`[data-error-for="${cssEscape(current)}"]`);

        if (slot) {
            return slot;
        }

        const input = findInput(form, current);
        const wrapperSlot = input?.closest('[data-field]')?.querySelector('[data-error-for]');

        if (wrapperSlot) {
            return wrapperSlot;
        }

        current = current.includes('.') ? current.slice(0, current.lastIndexOf('.')) : '';
    }

    return null;
}

function findInput(form, key) {
    const name = keyToName(key);
    const candidates = [name, `${name}[]`];
    const listItem = key.match(/^(.*)\.\d+$/);

    if (listItem) {
        candidates.push(`${keyToName(listItem[1])}[]`);
    }

    for (const candidate of candidates) {
        const input = form.querySelector(`[name="${cssEscape(candidate)}"]`);

        if (input) {
            return input;
        }
    }

    return form.querySelector(`[data-error-key="${cssEscape(key)}"]`);
}

function markInvalid(slot, messages) {
    const wrapper = slot.closest('[data-field]');
    slot.textContent = messages.join(' ');
    slot.dataset.active = 'true';

    if (!slot.id) {
        slot.id = `err-${Math.random().toString(36).slice(2, 10)}`;
    }

    wrapper?.classList.add('is-invalid');
    wrapper?.querySelectorAll('input:not([type=hidden]), select, textarea').forEach((input) => {
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', slot.id);
        input.classList.add('is-invalid');
    });

    return wrapper || slot;
}

function syncTabs(form) {
    form.querySelectorAll('[data-tab-panel]').forEach((panel) => {
        const trigger = form.querySelector(`[data-tab-trigger="${cssEscape(panel.dataset.tabPanel)}"]`);

        if (trigger) {
            trigger.toggleAttribute('data-has-error', Boolean(panel.querySelector('.is-invalid')));
        }
    });
}

function summary(form) {
    return form.querySelector('[data-error-summary]');
}

/**
 * @returns {{ first: Element|null, count: number }}
 */
export function renderErrors(form, errors = {}) {
    let first = null;
    let count = 0;
    const unmatched = [];

    for (const [key, raw] of Object.entries(errors)) {
        const messages = (Array.isArray(raw) ? raw : [raw]).filter(Boolean).map(String);

        if (!messages.length) {
            continue;
        }

        count++;
        const slot = slotFor(form, key);

        if (!slot) {
            unmatched.push(...messages);
            continue;
        }

        const target = markInvalid(slot, messages);
        first ??= target;
    }

    const box = summary(form);

    if (box && unmatched.length) {
        box.replaceChildren(...unmatched.map((message) => {
            const item = document.createElement('li');
            item.textContent = message;

            return item;
        }));
        box.closest('[data-error-summary-wrapper]')?.removeAttribute('hidden');
        first ??= box;
    }

    syncTabs(form);

    return { first, count };
}

export function clearErrors(form) {
    form.querySelectorAll('[data-error-for]').forEach((slot) => {
        slot.textContent = '';
        delete slot.dataset.active;
    });
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    form.querySelectorAll('[aria-invalid="true"]').forEach((el) => el.removeAttribute('aria-invalid'));

    const box = summary(form);

    if (box) {
        box.replaceChildren();
        box.closest('[data-error-summary-wrapper]')?.setAttribute('hidden', '');
    }

    syncTabs(form);
}

/**
 * Clears the error of one field key (live validation success) and of its children (`key.*`).
 */
export function clearFieldError(form, key) {
    const slot = slotFor(form, key);

    if (!slot) {
        return;
    }

    slot.textContent = '';
    delete slot.dataset.active;

    const wrapper = slot.closest('[data-field]');

    if (wrapper && !wrapper.querySelector('[data-error-for][data-active]')) {
        wrapper.classList.remove('is-invalid');
        wrapper.querySelectorAll('.is-invalid, [aria-invalid]').forEach((el) => {
            el.classList.remove('is-invalid');
            el.removeAttribute('aria-invalid');
        });
    }

    syncTabs(form);
}

/** Only the errors that belong to `key` (exact or nested). */
export function errorsForKey(errors, key) {
    return Object.fromEntries(Object.entries(errors).filter(([name]) => name === key || name.startsWith(`${key}.`)));
}

export function summaryMessage(count) {
    return count === 1 ? t('form.errors_summary_one') : t('form.errors_summary', { count });
}

export { nameToKey };
