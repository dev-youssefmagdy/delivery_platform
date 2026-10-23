import { t } from './config.js';

/**
 * Toast notifications rendered into #admin-toasts (aria-live region from <x-admin.toast-stack>).
 * toast.success(msg) / error / warning / info; options: { timeout, action: { label, onClick }, requestId }.
 * A toast can be queued across a redirect with toast.flash(type, message).
 */
const ICONS = {
    success: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    error: 'm9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    warning: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
    info: 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
};
const COLORS = { success: 'text-emerald-700', error: 'text-rose-600', warning: 'text-amber-700', info: 'text-sky-700' };
const FLASH_KEY = 'admin:flash';

function container() {
    let el = document.getElementById('admin-toasts');

    if (!el) {
        el = document.createElement('div');
        el.id = 'admin-toasts';
        el.setAttribute('aria-live', 'polite');
        el.className = 'pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end';
        document.body.appendChild(el);
    }

    return el;
}

function show(type, message, options = {}) {
    if (!message) {
        return null;
    }

    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.dataset.toast = type;

    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('viewBox', '0 0 24 24');
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '1.5');
    icon.setAttribute('aria-hidden', 'true');
    icon.setAttribute('class', `size-5 shrink-0 ${COLORS[type] || ''}`);
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('d', ICONS[type] || ICONS.info);
    icon.appendChild(path);

    const body = document.createElement('div');
    body.className = 'min-w-0 flex-1';
    const text = document.createElement('p');
    text.className = 'font-medium text-slate-800';
    text.textContent = message;
    body.appendChild(text);

    if (options.requestId) {
        const ref = document.createElement('p');
        ref.className = 'mt-1 text-xs text-slate-500';
        ref.textContent = t('toast.reference', { id: options.requestId });
        body.appendChild(ref);
    }

    if (options.action) {
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'btn btn-link btn-sm mt-1';
        action.textContent = options.action.label;
        action.addEventListener('click', () => {
            options.action.onClick?.();
            dismiss();
        });
        body.appendChild(action);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'icon-btn size-6 shrink-0';
    close.setAttribute('aria-label', t('common.close'));
    close.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
    close.addEventListener('click', () => dismiss());

    el.append(icon, body, close);
    container().appendChild(el);

    const timeout = options.timeout ?? (type === 'error' || options.action ? 10000 : 5000);
    let timer = timeout > 0 ? setTimeout(() => dismiss(), timeout) : null;

    function dismiss() {
        clearTimeout(timer);
        timer = null;
        el.remove();
    }

    return { element: el, dismiss };
}

export const toast = {
    success: (message, options) => show('success', message, options),
    error: (message, options) => show('error', message, options),
    warning: (message, options) => show('warning', message, options),
    info: (message, options) => show('info', message, options),

    /** Queue a toast for the next page load (after a redirect). */
    flash(type, message) {
        try {
            sessionStorage.setItem(FLASH_KEY, JSON.stringify({ type, message }));
        } catch {
            /* storage unavailable */
        }
    },

    /** Show a queued toast (called once on boot). */
    consumeFlash() {
        try {
            const raw = sessionStorage.getItem(FLASH_KEY);
            sessionStorage.removeItem(FLASH_KEY);

            if (raw) {
                const { type, message } = JSON.parse(raw);
                show(type, message);
            }
        } catch {
            /* storage unavailable */
        }
    },
};
