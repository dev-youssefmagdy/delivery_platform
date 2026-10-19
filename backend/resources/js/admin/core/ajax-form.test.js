import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { AjaxForm } from './ajax-form.js';
import { setConfig } from './config.js';

/**
 * Smoke tests of the "validate before submit" protocol (Plan §5.3).
 */
function buildForm(inner = '') {
    document.body.innerHTML = `
        <form data-ajax-form action="/admin/demo-items" method="POST" id="demo-form">
            <input type="hidden" name="_token" value="token">
            <div data-field="name">
                <input name="name" id="name" value="Widget">
                <p data-error-for="name"></p>
            </div>
            <div data-field="items.0.label">
                <input name="items[0][label]" value="x">
                <p data-error-for="items.0.label"></p>
            </div>
            ${inner}
            <div data-error-summary-wrapper hidden><ul data-error-summary></ul></div>
            <button type="submit">Save</button>
        </form>`;

    return document.getElementById('demo-form');
}

function fakeToast() {
    return { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn(), flash: vi.fn() };
}

const precognitionOk = () => Promise.resolve({ status: 204, headers: { 'precognition-success': 'true' }, data: '' });
const unprocessable = (errors, message = 'The submitted data is invalid.') =>
    Promise.reject({ response: { status: 422, headers: {}, data: { success: false, message, errors: { _code: 'validation_failed', ...errors }, meta: {} } } });

function submit(form) {
    const event = new Event('submit', { bubbles: true, cancelable: true });
    form.dispatchEvent(event);

    return event;
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

beforeEach(() => {
    setConfig({ locale: 'en', i18n: { form: { errors_summary: 'Please fix :count errors.', errors_summary_one: 'Please fix 1 error.' }, validation: { required: 'This field is required.' } } });
    window.requestAnimationFrame = (cb) => cb();
});

afterEach(() => {
    document.body.innerHTML = '';
});

describe('AjaxForm protocol', () => {
    it('renders precognition 422 errors inline and blocks the real submit', async () => {
        const form = buildForm();
        const http = { request: vi.fn(() => unprocessable({ name: ['The name has already been taken.'], 'items.0.label': ['Label is invalid.'] })) };
        const toast = fakeToast();
        const onSubmitEvent = vi.fn();
        form.addEventListener('ajax-form:submit', onSubmitEvent);

        const result = await new AjaxForm(form, { http, toast }).run();

        expect(result).toEqual({ stage: 'precognition', ok: false });
        expect(http.request).toHaveBeenCalledTimes(1);
        expect(http.request.mock.calls[0][0].headers.Precognition).toBe('true');
        expect(form.querySelector('[data-error-for="name"]').textContent).toBe('The name has already been taken.');
        expect(form.querySelector('[data-error-for="items.0.label"]').textContent).toBe('Label is invalid.');
        expect(form.querySelector('[data-field="name"]').classList.contains('is-invalid')).toBe(true);
        expect(form.querySelector('#name').getAttribute('aria-invalid')).toBe('true');
        expect(onSubmitEvent).not.toHaveBeenCalled();
        expect(toast.error).toHaveBeenCalledWith('Please fix 2 errors.');
    });

    it('fires ajax-form:validated then ajax-form:submit on 204 and performs the real AJAX submit', async () => {
        const form = buildForm();
        const http = {
            request: vi.fn()
                .mockImplementationOnce(precognitionOk)
                .mockImplementationOnce(() => Promise.resolve({ status: 200, data: { success: true, message: 'Saved.', data: { reload_table: 'demo-items-table' } } })),
        };
        const toast = fakeToast();
        const order = [];
        const reloaded = vi.fn();
        form.addEventListener('ajax-form:validated', () => order.push('validated'));
        form.addEventListener('ajax-form:submit', () => order.push('submit'));
        form.addEventListener('ajax-form:success', () => order.push('success'));
        document.addEventListener('admin:table-reload', (event) => reloaded(event.detail.id));

        const result = await new AjaxForm(form, { http, toast }).run();

        expect(result.ok).toBe(true);
        expect(order).toEqual(['validated', 'submit', 'success']);
        expect(http.request).toHaveBeenCalledTimes(2);
        expect(http.request.mock.calls[1][0].headers).toBeUndefined();
        expect(http.request.mock.calls[1][0].data).toBeInstanceOf(FormData);
        expect(toast.success).toHaveBeenCalledWith('Saved.');
        expect(reloaded).toHaveBeenCalledWith('demo-items-table');
    });

    it('blocks double submit while a request is in flight', async () => {
        const form = buildForm();
        let release;
        const http = { request: vi.fn(() => new Promise((resolve) => (release = resolve))) };
        new AjaxForm(form, { http, toast: fakeToast() });
        const button = form.querySelector('button[type="submit"]');

        submit(form);
        submit(form);
        submit(form);

        expect(http.request).toHaveBeenCalledTimes(1);
        expect(button.disabled).toBe(true);
        expect(button.hasAttribute('data-loading')).toBe(true);

        release({ status: 204, headers: { 'precognition-success': 'true' } });
        await flush();
        await flush();
    });

    it('stops on client-side constraint errors without calling the server', async () => {
        const form = buildForm('<div data-field="code"><input name="code" required value=""><p data-error-for="code"></p></div>');
        const http = { request: vi.fn() };

        const result = await new AjaxForm(form, { http, toast: fakeToast() }).run();

        expect(result.stage).toBe('client');
        expect(http.request).not.toHaveBeenCalled();
        expect(form.querySelector('[data-error-for="code"]').textContent).toBe('This field is required.');
    });

    it('lets a validated hook cancel, and a submit listener prevent, the real submit', async () => {
        const form = buildForm();
        const http = { request: vi.fn(precognitionOk) };
        const instance = new AjaxForm(form, { http, toast: fakeToast() });

        const cancelHook = (event) => {
            event.preventDefault();
            event.detail.cancel();
        };
        form.addEventListener('ajax-form:validated', cancelHook);
        expect((await instance.run()).stage).toBe('validated');
        form.removeEventListener('ajax-form:validated', cancelHook);

        form.addEventListener('ajax-form:submit', (event) => event.preventDefault());
        expect((await instance.run()).stage).toBe('submit');
        expect(http.request).toHaveBeenCalledTimes(2);
    });

    it('lists errors without a matching field in the summary and spoofs the method', async () => {
        const form = buildForm('<input type="hidden" name="_method" value="PUT">');
        const http = { request: vi.fn(() => unprocessable({ unknown_field: ['Something is off.'] })) };

        await new AjaxForm(form, { http, toast: fakeToast() }).run();

        expect(http.request.mock.calls[0][0].method).toBe('post');
        expect(http.request.mock.calls[0][0].data.get('_method')).toBe('PUT');
        expect(form.querySelector('[data-error-summary]').textContent).toContain('Something is off.');
        expect(form.querySelector('[data-error-summary-wrapper]').hasAttribute('hidden')).toBe(false);
    });

    it('shows a conflict toast with a reload action on 409', async () => {
        const form = buildForm();
        const http = {
            request: vi.fn()
                .mockImplementationOnce(precognitionOk)
                .mockImplementationOnce(() => Promise.reject({ response: { status: 409, headers: {}, data: { message: 'Stale.', errors: { _code: 'stale_version' }, meta: {} } } })),
        };
        const toast = fakeToast();

        const result = await new AjaxForm(form, { http, toast }).run();

        expect(result.ok).toBe(false);
        expect(toast.warning).toHaveBeenCalledWith('Stale.', expect.objectContaining({ action: expect.any(Object) }));
    });

    it('validates a single field live with Precognition-Validate-Only', async () => {
        vi.useFakeTimers();
        const form = buildForm();
        const http = { request: vi.fn(() => unprocessable({ name: ['Too short.'], other: ['ignored'] })) };
        new AjaxForm(form, { http, toast: fakeToast(), liveDelay: 400 });

        form.querySelector('#name').dispatchEvent(new Event('change', { bubbles: true }));
        await vi.advanceTimersByTimeAsync(399);
        expect(http.request).not.toHaveBeenCalled();
        await vi.advanceTimersByTimeAsync(1);
        vi.useRealTimers();
        await flush();

        expect(http.request).toHaveBeenCalledTimes(1);
        expect(http.request.mock.calls[0][0].headers['Precognition-Validate-Only']).toBe('name');
        expect(form.querySelector('[data-error-for="name"]').textContent).toBe('Too short.');
        expect(form.querySelector('[data-error-summary]').textContent).toBe('');
    });
});
