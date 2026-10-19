import { http, normalizeError } from './http.js';
import { t } from './config.js';
import { toast } from './toast.js';
import { filtersFor } from './datatables.js';

/**
 * Queued CSV export (Plan §5.4): [data-export-url][data-export-table] posts the current filters, then polls the
 * export status until a signed download link is ready.
 */
const POLL_MS = 2000;
const MAX_POLLS = 150;

async function poll(statusUrl, attempt = 0) {
    const { data } = await http.get(statusUrl);
    const status = data?.data ?? {};

    if (status.finished) {
        return status;
    }

    if (attempt >= MAX_POLLS) {
        throw new Error('timeout');
    }

    await new Promise((resolve) => setTimeout(resolve, POLL_MS));

    return poll(statusUrl, attempt + 1);
}

export function initExports() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-export-url]');

        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();
        button.disabled = true;
        button.toggleAttribute('data-loading', true);
        let pending = null;

        try {
            const { data } = await http.post(button.dataset.exportUrl, { filters: filtersFor(button.dataset.exportTable) });
            pending = toast.info(data.message || t('exports.queued'), { timeout: 0 });
            const status = await poll(data.data.status_url);
            if (status.download_url) {
                toast.success(t('exports.ready', { rows: status.rows }), {
                    timeout: 0,
                    action: { label: t('exports.download'), onClick: () => window.location.assign(status.download_url) },
                });
            } else {
                toast.error(t('exports.failed'));
            }
        } catch (error) {
            const normalized = normalizeError(error);
            toast.error(error?.message === 'timeout' ? t('exports.failed') : normalized.message, { requestId: normalized.requestId });
        } finally {
            pending?.dismiss();
            button.disabled = false;
            button.removeAttribute('data-loading');
        }
    });
}
