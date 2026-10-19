/**
 * Small DOM helpers shared by the admin modules.
 */

/** `items[0][name]` → `items.0.name`; `tags[]` → `tags`. */
export function nameToKey(name) {
    return String(name || '')
        .replace(/\[\]$/, '')
        .replace(/\]\[|\[|\]/g, '.')
        .replace(/\.+$/, '')
        .replace(/^\./, '');
}

/** `items.0.name` → `items[0][name]` (Laravel error key → input name). */
export function keyToName(key) {
    const [first, ...rest] = String(key).split('.');

    return first + rest.map((part) => `[${part}]`).join('');
}

export function cssEscape(value) {
    if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        return CSS.escape(value);
    }

    return String(value).replace(/["\\\]\[.#:]/g, '\\$&');
}

export function debounce(fn, wait = 400) {
    let timer = null;

    const debounced = (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };

    debounced.cancel = () => clearTimeout(timer);

    return debounced;
}

/**
 * Serialises a form (or any container with named fields) into a nested object, honouring bracket names:
 * `filters[statuses][]` → { filters: { statuses: [...] } }. Empty values are skipped when `skipEmpty`.
 */
export function serializeFields(root, { skipEmpty = true } = {}) {
    const result = {};
    const fields = root.querySelectorAll('input[name], select[name], textarea[name]');

    for (const field of fields) {
        if (field.disabled || field.type === 'file' || field.type === 'submit' || field.type === 'button') {
            continue;
        }

        if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
            continue;
        }

        const values = field.tagName === 'SELECT' && field.multiple
            ? Array.from(field.selectedOptions).map((option) => option.value)
            : [field.value];

        for (const value of values) {
            if (skipEmpty && (value === '' || value === null)) {
                continue;
            }

            assignPath(result, field.name, value);
        }
    }

    return result;
}

function assignPath(target, name, value) {
    const isList = name.endsWith('[]');
    const parts = (isList ? name.slice(0, -2) : name).replace(/\]/g, '').split('[');
    let node = target;

    parts.forEach((part, index) => {
        if (index < parts.length - 1) {
            node[part] = node[part] && typeof node[part] === 'object' ? node[part] : {};
            node = node[part];

            return;
        }

        if (isList) {
            node[part] = Array.isArray(node[part]) ? node[part] : [];
            node[part].push(value);
        } else {
            node[part] = value;
        }
    });
}

/** Flattens a nested object into URLSearchParams with bracket keys. */
export function toSearchParams(object, prefix = '', params = new URLSearchParams()) {
    for (const [key, value] of Object.entries(object || {})) {
        const name = prefix ? `${prefix}[${key}]` : key;

        if (Array.isArray(value)) {
            value.forEach((item) => params.append(`${name}[]`, item));
        } else if (value && typeof value === 'object') {
            toSearchParams(value, name, params);
        } else if (value !== undefined && value !== null && value !== '') {
            params.append(name, value);
        }
    }

    return params;
}

/** Brings a field into view: opens collapsed tabs/sections (admin:reveal), focuses and scrolls. */
export function revealAndFocus(element) {
    if (!element) {
        return;
    }

    element.dispatchEvent(new CustomEvent('admin:reveal', { bubbles: true }));

    const target = element.matches('input, select, textarea, button, [tabindex]')
        ? element
        : element.querySelector('input:not([type=hidden]), select, textarea, [tabindex]') || element;

    requestAnimationFrame?.(() => {
        try {
            target.focus({ preventScroll: true });
        } catch {
            /* element not focusable */
        }

        if (typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    });
}
