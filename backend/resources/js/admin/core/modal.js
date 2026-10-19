import { http, normalizeError } from './http.js';
import { toast } from './toast.js';

/**
 * Alpine factory for <x-admin.modal> and <x-admin.drawer>. Open with
 *   window.dispatchEvent(new CustomEvent('admin-modal:open', { detail: { id, url } }))
 * or declaratively with [data-modal-open="<id>"] (+ optional data-modal-url to load HTML via AJAX).
 * Remote HTML is initialised with Admin.init() (forms, tables, pickers). Focus is trapped (x-trap) and
 * restored on close; ESC closes.
 */
export function adminModal(id, url = null) {
    return {
        id,
        url,
        open: false,
        loading: false,
        returnFocus: null,

        init() {
            window.addEventListener('admin-modal:open', (event) => {
                if (event.detail?.id === this.id) {
                    this.show(event.detail.url ?? null);
                }
            });
            window.addEventListener('admin-modal:close', (event) => {
                if (!event.detail?.id || event.detail.id === this.id) {
                    this.hide();
                }
            });
        },

        async show(remoteUrl = null) {
            this.returnFocus = document.activeElement;
            this.open = true;
            const target = remoteUrl || this.url;

            if (!target) {
                return;
            }

            this.loading = true;

            try {
                const response = await http.get(target, { headers: { Accept: 'text/html' } });
                this.$refs.body.innerHTML = response.data;
                this.$nextTick(() => window.Admin?.init(this.$refs.body));
            } catch (error) {
                toast.error(normalizeError(error).message);
                this.hide();
            } finally {
                this.loading = false;
            }
        },

        hide() {
            if (!this.open) {
                return;
            }

            this.open = false;
            this.returnFocus?.focus?.();
        },
    };
}

export function initModalTriggers() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-modal-open]');

        if (!trigger) {
            return;
        }

        event.preventDefault();
        window.dispatchEvent(new CustomEvent('admin-modal:open', {
            detail: { id: trigger.dataset.modalOpen, url: trigger.dataset.modalUrl || null },
        }));
    });
}
