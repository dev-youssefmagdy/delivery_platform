import { beforeEach, describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const error = vi.fn();

vi.mock('../core/http.js', () => ({
    http: { post: (...args) => post(...args) },
    normalizeError: (e) => ({ message: e.message, requestId: null }),
}));
vi.mock('../core/toast.js', () => ({ toast: { error: (...args) => error(...args) } }));

const { adminReveal } = await import('./ui.js');

describe('adminReveal', () => {
    beforeEach(() => {
        post.mockReset();
        error.mockReset();
        document.body.innerHTML = '<div id="root"><span data-reveal-field="phone">+20••••4567</span><span data-reveal-field="email">m•••@x.test</span></div>';
    });

    const component = () => Object.assign(adminReveal('/reveal'), { $root: document.getElementById('root') });

    it('swaps in the values the server returns, scoped to its own root, and marks itself revealed', async () => {
        post.mockResolvedValue({ data: { data: { phone: '+201001234567', email: null } } });
        const c = component();

        await c.reveal();

        expect(post).toHaveBeenCalledWith('/reveal');
        expect(document.querySelector('[data-reveal-field=phone]').textContent).toBe('+201001234567');
        expect(document.querySelector('[data-reveal-field=email]').textContent).toBe('m•••@x.test');
        expect(c.revealed).toBe(true);
        expect(c.loading).toBe(false);
    });

    it('keeps the values masked and reports the error when the request fails', async () => {
        post.mockRejectedValue(new Error('Forbidden'));
        const c = component();

        await c.reveal();

        expect(document.querySelector('[data-reveal-field=phone]').textContent).toBe('+20••••4567');
        expect(c.revealed).toBe(false);
        expect(error).toHaveBeenCalledWith('Forbidden', { requestId: null });
    });
});
