/**
 * Runtime configuration rendered by the admin layout into <script type="application/json" id="admin-config">:
 * { locale, dir, csrf, i18n: {...}, maps: {...}, routes: {...} }.
 */
let cached = null;

export function config() {
    if (cached) {
        return cached;
    }

    const el = typeof document !== 'undefined' ? document.getElementById('admin-config') : null;

    try {
        cached = el ? JSON.parse(el.textContent || '{}') : {};
    } catch {
        cached = {};
    }

    cached.i18n ??= {};

    return cached;
}

/** Test helper / late override. */
export function setConfig(value) {
    cached = { i18n: {}, ...value };
}

/**
 * Translate a key from config().i18n (dot notation), replacing :placeholders.
 */
export function t(key, replacements = {}) {
    const value = key.split('.').reduce((node, part) => (node && typeof node === 'object' ? node[part] : undefined), config().i18n);
    let text = typeof value === 'string' ? value : key;

    for (const [name, replacement] of Object.entries(replacements)) {
        text = text.replaceAll(`:${name}`, String(replacement));
    }

    return text;
}

export function csrfToken() {
    return config().csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
