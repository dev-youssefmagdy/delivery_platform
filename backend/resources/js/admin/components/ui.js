import { http, normalizeError } from '../core/http.js';
import { toast } from '../core/toast.js';

/**
 * Small Alpine components: dropdown, sidebar, repeater, money input, file input, reveal of masked values.
 */

/**
 * <x-admin.reveal-fields>: POSTs to the (audited, permissioned) reveal endpoint and writes each returned value
 * into the element marked [data-reveal-field="<key>"]. Nothing sensitive is present in the page before that.
 */
export function adminReveal(url) {
    return {
        loading: false,
        revealed: false,

        async reveal() {
            if (this.loading) {
                return;
            }

            this.loading = true;

            try {
                const { data } = await http.post(url);
                const values = data?.data ?? {};

                this.$root.querySelectorAll('[data-reveal-field]').forEach((element) => {
                    const value = values[element.dataset.revealField];

                    if (typeof value === 'string' && value !== '') {
                        element.textContent = value;
                    }
                });
                this.revealed = true;
            } catch (error) {
                const normalized = normalizeError(error);
                toast.error(normalized.message, { requestId: normalized.requestId });
            } finally {
                this.loading = false;
            }
        },
    };
}

export function adminDropdown() {
    return {
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    };
}

const SIDEBAR_KEY = 'admin:sidebar-collapsed';

export function adminSidebar() {
    let collapsed = false;

    try {
        collapsed = localStorage.getItem(SIDEBAR_KEY) === '1';
    } catch {
        /* storage unavailable */
    }

    return {
        mobileOpen: false,
        collapsed,
        toggleCollapsed() {
            this.collapsed = !this.collapsed;

            try {
                localStorage.setItem(SIDEBAR_KEY, this.collapsed ? '1' : '0');
            } catch {
                /* storage unavailable */
            }
        },
    };
}

/**
 * Repeater rows: existing rows are server-rendered; new rows clone <template x-ref="template"> replacing
 * __INDEX__ in names/ids. Indices are never reused, so server error keys (items.3.name) stay addressable.
 */
export function adminRepeater({ next = 0, min = 0, max = 100 } = {}) {
    return {
        next,
        min,
        max,
        count: 0,

        init() {
            this.count = this.rows().length;
        },

        rows() {
            return [...this.$refs.rows.children].filter((row) => row.hasAttribute('data-repeater-row'));
        },

        add() {
            if (this.count >= this.max) {
                return;
            }

            const html = this.$refs.template.innerHTML.replaceAll('__INDEX__', String(this.next++));
            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const row = holder.firstElementChild;
            this.$refs.rows.appendChild(row);
            this.count++;
            window.Admin?.init(row);
            row.querySelector('input, select, textarea')?.focus();
        },

        remove(button) {
            if (this.count <= this.min) {
                return;
            }

            button.closest('[data-repeater-row]')?.remove();
            this.count--;
        },
    };
}

/**
 * Decimal display ↔ integer minor units (ADR-04). Returns '' for invalid input.
 */
export function toMinor(display, exponent = 2) {
    const value = String(display ?? '').trim().replace(/[٠-٩]/g, (d) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d))).replace(/[,٬\s]/g, '').replace('٫', '.');

    if (!/^-?\d+(\.\d+)?$/.test(value)) {
        return '';
    }

    const negative = value.startsWith('-');
    const [whole, fraction = ''] = value.replace('-', '').split('.');

    if (fraction.length > exponent) {
        return '';
    }

    const minor = BigInt(whole) * 10n ** BigInt(exponent) + BigInt((fraction + '0'.repeat(exponent)).slice(0, exponent) || '0');

    return (negative ? -minor : minor).toString();
}

export function fromMinor(minor, exponent = 2) {
    if (minor === '' || minor === null || minor === undefined || !/^-?\d+$/.test(String(minor))) {
        return '';
    }

    const negative = String(minor).startsWith('-');
    const digits = String(minor).replace('-', '').padStart(exponent + 1, '0');
    const whole = digits.slice(0, digits.length - exponent);
    const fraction = exponent > 0 ? `.${digits.slice(-exponent)}` : '';

    return `${negative ? '-' : ''}${whole}${fraction}`;
}

export function adminMoney(exponent = 2) {
    return {
        display: '',

        init() {
            this.display = fromMinor(this.$refs.minor.value, exponent);
            this.$el.closest('form')?.addEventListener('admin:form-reset', () => {
                this.display = fromMinor(this.$refs.minor.value, exponent);
            });
        },

        sync() {
            const previous = this.$refs.minor.value;
            this.$refs.minor.value = this.display === '' ? '' : toMinor(this.display, exponent) || 'invalid';

            if (previous !== this.$refs.minor.value) {
                this.$refs.minor.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
    };
}

export function adminFile() {
    return {
        name: '',
        size: '',
        preview: null,

        change(event) {
            const file = event.target.files?.[0];

            if (this.preview) {
                URL.revokeObjectURL(this.preview);
            }

            this.preview = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
            this.name = file?.name ?? '';
            this.size = file ? `${Math.max(1, Math.round(file.size / 1024))} KB` : '';
        },

        clear() {
            this.$refs.input.value = '';
            this.change({ target: this.$refs.input });
        },
    };
}
