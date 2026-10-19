import { clearErrors } from './form-errors.js';

/**
 * Dangerous actions (Plan §5.2 confirm-action / §5.6.5). Any element with [data-confirm-action] opens the
 * global confirm dialog (<x-admin.partials.confirm-dialog>), optionally with a MANDATORY reason textarea.
 * The dialog body is itself an <x-admin.form>, so the reason goes through the same Precognition-validated
 * AJAX protocol; on success the dialog closes and the referenced table(s) reload.
 *
 * askConfirm({ title, message, confirmLabel }) → Promise<boolean> is the request-less variant used by
 * <x-admin.form confirm="…">.
 */
export function confirmDialog() {
    return {
        open: false,
        mode: 'request',
        title: '',
        message: '',
        confirmLabel: '',
        variant: 'danger',
        reasonRequired: false,
        resolver: null,
        returnFocus: null,

        init() {
            window.addEventListener('admin-confirm:open', (event) => this.show(event.detail || {}));
            this.$nextTick(() => this.$refs.form?.addEventListener('ajax-form:success', () => this.hide()));
        },

        show(detail) {
            const form = this.$refs.form;

            this.returnFocus = document.activeElement;
            this.mode = detail.mode || 'request';
            this.title = detail.title || this.$el.dataset.defaultTitle;
            this.message = detail.message || this.$el.dataset.defaultMessage;
            this.confirmLabel = detail.confirmLabel || this.$el.dataset.defaultConfirm;
            this.variant = detail.variant || 'danger';
            this.reasonRequired = detail.reasonRequired === true || detail.reasonRequired === 'true';
            this.resolver = detail.resolve || null;

            if (this.mode === 'request') {
                form.setAttribute('action', detail.url);
                let spoof = form.querySelector('input[name="_method"]');

                if (!spoof) {
                    spoof = Object.assign(document.createElement('input'), { type: 'hidden', name: '_method' });
                    form.prepend(spoof);
                }

                spoof.value = (detail.method || 'POST').toUpperCase();
                form.dataset.reloadTable = detail.reloadTable || '';
            }

            const reason = form.querySelector('textarea[name="reason"]');
            reason.value = '';
            reason.required = this.reasonRequired;
            reason.disabled = !this.reasonRequired;
            clearErrors(form);

            this.open = true;
            this.$nextTick(() => (this.reasonRequired ? reason : this.$refs.cancel)?.focus());
        },

        confirm() {
            this.resolver?.(true);
            this.resolver = null;
            this.hide();
        },

        hide() {
            this.resolver?.(false);
            this.resolver = null;
            this.open = false;
            this.returnFocus?.focus?.();
        },
    };
}

export function askConfirm({ title, message, confirmLabel, variant } = {}) {
    return new Promise((resolve) => {
        window.dispatchEvent(new CustomEvent('admin-confirm:open', {
            detail: { mode: 'ask', title, message, confirmLabel, variant: variant || 'primary', resolve },
        }));
    });
}

export function initConfirmActions() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm-action]');

        if (!trigger || trigger.disabled) {
            return;
        }

        event.preventDefault();
        const d = trigger.dataset;

        window.dispatchEvent(new CustomEvent('admin-confirm:open', {
            detail: {
                mode: 'request',
                url: d.url,
                method: d.method,
                title: d.title,
                message: d.message,
                confirmLabel: d.confirmLabel,
                variant: d.variant,
                reasonRequired: d.reasonRequired,
                reloadTable: d.reloadTable,
            },
        }));
    });

    // <x-admin.form confirm="…">: ask before the real submit (hook on step 4 of the protocol).
    document.addEventListener('ajax-form:validated', (event) => {
        const form = event.target;

        if (!form.dataset.confirm) {
            return;
        }

        event.preventDefault();
        askConfirm({ title: form.dataset.confirmTitle, message: form.dataset.confirm, confirmLabel: form.dataset.confirmLabel })
            .then((ok) => (ok ? event.detail.proceed() : event.detail.cancel()));
    });
}
