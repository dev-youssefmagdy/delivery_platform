import { http, normalizeError } from '../core/http.js';
import { toast } from '../core/toast.js';

/**
 * Side-by-side review viewer (<x-admin.document-viewer>). Each item carries the endpoint that issues a FRESH signed URL
 * (permissioned, valid for 5 minutes); the file is only requested from storage when the reviewer selects it, which is
 * also when the server audits the view. The URL is refreshed shortly before it expires. Approve / reject open the
 * global confirm dialog (reject with a mandatory reason) and reload the page afterwards.
 */
const REFRESH_AFTER_MS = 4 * 60 * 1000;

export function documentViewer(items, labels = {}) {
    return {
        items,
        labels,
        currentKey: null,
        url: null,
        loading: false,
        failed: false,
        timer: null,

        get current() {
            return this.items.find((item) => item.key === this.currentKey) ?? null;
        },

        get isPdf() {
            return this.current?.mime === 'application/pdf';
        },

        get groups() {
            const grouped = new Map();

            for (const item of this.items) {
                if (!grouped.has(item.group)) {
                    grouped.set(item.group, []);
                }

                grouped.get(item.group).push(item);
            }

            return [...grouped.entries()].map(([name, entries]) => ({ name, entries }));
        },

        async select(key) {
            this.currentKey = key;
            this.url = null;
            this.failed = false;
            clearTimeout(this.timer);
            await this.load();
        },

        async load(mode = 'view') {
            const item = this.current;

            if (!item) {
                return null;
            }

            this.loading = mode === 'view';

            try {
                const { data } = await http.post(item.endpoint, { mode });
                const url = data?.data?.url ?? null;

                if (mode === 'view' && this.currentKey === item.key) {
                    this.url = url;
                    this.timer = setTimeout(() => this.load(), REFRESH_AFTER_MS);
                }

                return url;
            } catch (error) {
                const normalized = normalizeError(error);
                this.failed = true;
                toast.error(normalized.message, { requestId: normalized.requestId });

                return null;
            } finally {
                this.loading = false;
            }
        },

        /** Opens the file in a new tab (view) or downloads it. */
        async open(mode = 'view') {
            const url = await this.load(mode);

            if (url) {
                const anchor = Object.assign(document.createElement('a'), { href: url, target: '_blank', rel: 'noopener' });
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
            }
        },

        approve() {
            this.decide('approve', false);
        },

        reject() {
            this.decide('reject', true);
        },

        decide(kind, reasonRequired) {
            const item = this.current;

            if (!item) {
                return;
            }

            const url = `${kind === 'approve' ? item.approve_url : item.reject_url}?reload=1`;

            window.dispatchEvent(new CustomEvent('admin-confirm:open', {
                detail: {
                    mode: 'request',
                    url,
                    method: 'POST',
                    title: this.labels[`${kind}_title`],
                    message: this.labels[`${kind}_message`],
                    confirmLabel: this.labels[kind],
                    variant: kind === 'approve' ? 'success' : 'danger',
                    reasonRequired,
                },
            }));
        },

        statusTone(status) {
            return { approved: 'success', rejected: 'danger', expired: 'warning', pending: 'info' }[status] ?? 'neutral';
        },
    };
}
