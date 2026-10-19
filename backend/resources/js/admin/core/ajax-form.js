import { http as defaultHttp, normalizeError } from './http.js';
import { toast as defaultToast } from './toast.js';
import { t } from './config.js';
import { clearErrors, clearFieldError, errorsForKey, renderErrors, summaryMessage } from './form-errors.js';
import { clientValidate } from './client-validation.js';
import { applyInstructions } from './response-instructions.js';
import { nameToKey, revealAndFocus } from './dom.js';

/**
 * "Validate before submit" protocol for every <form data-ajax-form> (Plan §5.3):
 *
 *   submit ─► preventDefault ─► ignore when busy
 *     1. clear previous errors
 *     2. client checks (constraint validation) ─► invalid: inline errors, focus first, STOP
 *     3. server validation: same URL + method, FormData, `Precognition: true`
 *          422 ─► inline errors + summary toast + focus first, STOP (submit prevented)
 *          403/419/5xx/network ─► toast, STOP
 *          204 ─► valid
 *     4. dispatch cancelable `ajax-form:validated` (detail.proceed()/cancel() for async confirm hooks)
 *     5. dispatch cancelable `ajax-form:submit`; unless prevented, the real AJAX submit runs:
 *          200 ─► toast + AdminFormResponse instructions; 422 ─► inline errors; 409 ─► toast + reload offer;
 *          other ─► toast with correlation id. Submit buttons are disabled + loading throughout.
 *
 * Live validation (default on, `data-live-validation="false"` to opt out): on change/blur of a touched
 * field, debounced 400 ms, a precognitive request with `Precognition-Validate-Only: <field>`.
 */
const instances = new WeakMap();

export class AjaxForm {
    constructor(form, { http = defaultHttp, toast = defaultToast, liveDelay = 400 } = {}) {
        this.form = form;
        this.http = http;
        this.toast = toast;
        this.busy = false;
        this.liveDelay = liveDelay;
        this.liveTimers = new Map();
        this.liveSequence = new Map();

        form.setAttribute('novalidate', '');
        this.handleSubmit = this.handleSubmit.bind(this);
        form.addEventListener('submit', this.handleSubmit);

        if (form.dataset.liveValidation !== 'false') {
            this.handleLive = this.handleLive.bind(this);
            form.addEventListener('change', this.handleLive);
            form.addEventListener('focusout', this.handleLive);
        }

        instances.set(form, this);
    }

    static for(form) {
        return instances.get(form) ?? null;
    }

    get url() {
        return this.form.getAttribute('action') || window.location.href;
    }

    /** Spoofed method (PUT/PATCH/DELETE) travels as `_method` in the FormData; transport is always POST. */
    get method() {
        const spoofed = this.form.querySelector('input[name="_method"]')?.value;

        return (spoofed || this.form.getAttribute('method') || 'POST').toUpperCase();
    }

    payload() {
        const data = new FormData(this.form);

        if (this.method !== 'POST' && !data.has('_method')) {
            data.append('_method', this.method);
        }

        return data;
    }

    async handleSubmit(event) {
        event.preventDefault();

        if (this.busy) {
            return { stage: 'busy', ok: false };
        }

        return this.run(event.submitter ?? null);
    }

    /**
     * Runs the full protocol. Resolves with { stage, ok } (useful for hooks and tests).
     */
    async run(submitter = null) {
        this.busy = true;
        this.setLoading(true, submitter);
        this.cancelLive();

        try {
            clearErrors(this.form);

            const clientErrors = clientValidate(this.form);

            if (Object.keys(clientErrors).length) {
                this.showErrors(clientErrors);

                return { stage: 'client', ok: false };
            }

            if (!(await this.validateOnServer())) {
                return { stage: 'precognition', ok: false };
            }

            if (!(await this.dispatchValidated())) {
                return { stage: 'validated', ok: false };
            }

            const submitEvent = new CustomEvent('ajax-form:submit', { bubbles: true, cancelable: true, detail: { form: this.form } });

            if (!this.form.dispatchEvent(submitEvent)) {
                return { stage: 'submit', ok: false };
            }

            return await this.submit();
        } finally {
            this.busy = false;
            this.setLoading(false, submitter);
        }
    }

    async validateOnServer() {
        try {
            const response = await this.http.request({
                url: this.url,
                method: 'post',
                data: this.payload(),
                headers: { Precognition: 'true' },
            });

            if (response.status !== 204 && response.headers?.['precognition-success'] !== 'true') {
                // The endpoint ignored Precognition (missing middleware): never treat that as "valid".
                this.toast.error(t('http.500'));

                return false;
            }

            return true;
        } catch (error) {
            const normalized = normalizeError(error);

            if (normalized.status === 422) {
                this.showErrors(normalized.errors, normalized.message);
            } else {
                this.handleFailure(normalized);
            }

            return false;
        }
    }

    /**
     * Fires `ajax-form:validated`. A listener may call preventDefault() and later detail.proceed() or
     * detail.cancel() (e.g. after a confirm dialog).
     */
    dispatchValidated() {
        return new Promise((resolve) => {
            let settled = false;
            const settle = (value) => {
                if (!settled) {
                    settled = true;
                    resolve(value);
                }
            };

            const event = new CustomEvent('ajax-form:validated', {
                bubbles: true,
                cancelable: true,
                detail: { form: this.form, proceed: () => settle(true), cancel: () => settle(false) },
            });

            if (this.form.dispatchEvent(event)) {
                settle(true);
            }
        });
    }

    async submit() {
        try {
            const response = await this.http.request({ url: this.url, method: 'post', data: this.payload() });
            const body = response.data && typeof response.data === 'object' ? response.data : {};

            this.form.dispatchEvent(new CustomEvent('ajax-form:success', { bubbles: true, detail: body }));
            applyInstructions(body.data, { form: this.form, message: body.message, toast: this.toast });

            return { stage: 'submitted', ok: true, body };
        } catch (error) {
            const normalized = normalizeError(error);

            if (normalized.status === 422) {
                this.showErrors(normalized.errors, normalized.message);
            } else {
                this.handleFailure(normalized);
            }

            this.form.dispatchEvent(new CustomEvent('ajax-form:error', { bubbles: true, detail: normalized }));

            return { stage: 'submitted', ok: false, error: normalized };
        }
    }

    showErrors(errors, fallbackMessage = null) {
        const { first, count } = renderErrors(this.form, errors);

        if (count === 0) {
            this.toast.error(fallbackMessage || t('http.422'));

            return;
        }

        this.toast.error(summaryMessage(count));
        revealAndFocus(first);
    }

    handleFailure(error) {
        const reload = { label: t('common.reload'), onClick: () => window.location.reload() };

        switch (true) {
            case error.network:
                this.toast.error(error.message, { action: { label: t('common.retry'), onClick: () => this.form.requestSubmit?.() } });
                break;
            case error.status === 409:
                this.toast.warning(error.message, { action: reload });
                break;
            case error.status === 419:
                this.toast.error(t('http.419'), { action: reload, timeout: 0 });
                break;
            case error.status === 403 || error.status === 404:
                this.toast.error(error.message);
                break;
            default:
                this.toast.error(error.message || t('http.500'), { requestId: error.requestId });
        }
    }

    setLoading(loading, submitter) {
        const buttons = [
            ...this.form.querySelectorAll('button[type="submit"], input[type="submit"]'),
            ...(this.form.id ? document.querySelectorAll(`button[type="submit"][form="${this.form.id}"]`) : []),
        ];
        const primary = submitter || buttons[0];

        buttons.forEach((button) => {
            button.disabled = loading;
        });

        if (primary) {
            primary.toggleAttribute('data-loading', loading);
        }

        this.form.setAttribute('aria-busy', loading ? 'true' : 'false');
    }

    /* ---------------- live validation ---------------- */

    handleLive(event) {
        const field = event.target;

        if (this.busy || !field?.name || field.name.startsWith('_') || (field.type === 'hidden' && event.type === 'focusout')) {
            return;
        }

        const key = field.dataset.errorKey || nameToKey(field.name);

        if (!key || (event.type === 'focusout' && !field.dataset.touched && !field.value)) {
            return;
        }

        field.dataset.touched = 'true';
        clearTimeout(this.liveTimers.get(key));
        this.liveTimers.set(key, setTimeout(() => this.validateField(key), this.liveDelay));
    }

    async validateField(key) {
        if (this.busy) {
            return;
        }

        const sequence = (this.liveSequence.get(key) ?? 0) + 1;
        this.liveSequence.set(key, sequence);

        try {
            await this.http.request({
                url: this.url,
                method: 'post',
                data: this.payload(),
                headers: { Precognition: 'true', 'Precognition-Validate-Only': key },
            });

            if (this.liveSequence.get(key) === sequence) {
                clearFieldError(this.form, key);
            }
        } catch (error) {
            const normalized = normalizeError(error);

            if (this.liveSequence.get(key) !== sequence || this.busy) {
                return;
            }

            if (normalized.status === 422) {
                const own = errorsForKey(normalized.errors, key);
                clearFieldError(this.form, key);

                if (Object.keys(own).length) {
                    renderErrors(this.form, own);
                }
            }
            // Other failures are silent during live validation; the submit path reports them.
        }
    }

    cancelLive() {
        this.liveTimers.forEach((timer) => clearTimeout(timer));
        this.liveTimers.clear();
        this.liveSequence.forEach((value, key) => this.liveSequence.set(key, value + 1));
    }
}

/**
 * Binds every unbound <form data-ajax-form> under root.
 */
export function initAjaxForms(root = document, options = {}) {
    root.querySelectorAll('form[data-ajax-form]').forEach((form) => {
        if (!instances.has(form)) {
            new AjaxForm(form, options);
        }
    });
}
