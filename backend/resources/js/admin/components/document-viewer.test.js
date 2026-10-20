import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../core/http.js', () => ({
    http: { post: vi.fn() },
    normalizeError: (error) => ({ message: error?.message ?? 'error', requestId: null }),
}));
vi.mock('../core/toast.js', () => ({ toast: { error: vi.fn() } }));

import { http } from '../core/http.js';
import { documentViewer } from './document-viewer.js';

const items = [
    { key: 'doc-1', group: 'Provider', mime: 'image/jpeg', status: 'pending', endpoint: '/u/1', approve_url: '/a/1', reject_url: '/r/1' },
    { key: 'doc-2', group: 'Provider', mime: 'application/pdf', status: 'approved', endpoint: '/u/2', approve_url: '/a/2', reject_url: '/r/2' },
    { key: 'photo-1', group: 'ABC 123', mime: 'image/jpeg', status: null, endpoint: '/p/1' },
];

describe('documentViewer', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        http.post.mockReset();
    });

    afterEach(() => vi.useRealTimers());

    it('groups items and only asks for a signed URL when one is selected', async () => {
        const viewer = documentViewer(items, {});

        expect(viewer.groups.map((group) => [group.name, group.entries.length])).toEqual([['Provider', 2], ['ABC 123', 1]]);
        expect(http.post).not.toHaveBeenCalled();

        http.post.mockResolvedValue({ data: { data: { url: 'https://signed/1' } } });
        await viewer.select('doc-1');

        expect(http.post).toHaveBeenCalledWith('/u/1', { mode: 'view' });
        expect(viewer.url).toBe('https://signed/1');
        expect(viewer.isPdf).toBe(false);
    });

    it('refreshes the signed URL before it expires', async () => {
        const viewer = documentViewer(items, {});
        http.post.mockResolvedValue({ data: { data: { url: 'https://signed/x' } } });
        await viewer.select('doc-2');

        expect(viewer.isPdf).toBe(true);
        http.post.mockResolvedValue({ data: { data: { url: 'https://signed/y' } } });
        await vi.advanceTimersByTimeAsync(4 * 60 * 1000 + 10);

        expect(http.post).toHaveBeenCalledTimes(2);
        expect(viewer.url).toBe('https://signed/y');
    });

    it('marks the viewer as failed when the URL cannot be issued', async () => {
        const viewer = documentViewer(items, {});
        http.post.mockRejectedValue(new Error('forbidden'));
        await viewer.select('doc-1');

        expect(viewer.failed).toBe(true);
        expect(viewer.url).toBeNull();
    });

    it('opens the confirm dialog with a mandatory reason for reject only', () => {
        const events = [];
        globalThis.window = globalThis.window ?? globalThis;
        window.dispatchEvent = (event) => events.push(event.detail);
        globalThis.CustomEvent ??= class { constructor(type, init) { this.type = type; this.detail = init?.detail; } };

        const viewer = documentViewer(items, { approve: 'Approve', reject: 'Reject' });
        viewer.currentKey = 'doc-1';
        viewer.approve();
        viewer.reject();

        expect(events[0]).toMatchObject({ url: '/a/1?reload=1', reasonRequired: false, variant: 'success' });
        expect(events[1]).toMatchObject({ url: '/r/1?reload=1', reasonRequired: true, variant: 'danger' });
    });
});
