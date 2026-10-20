import { http, normalizeError } from './http.js';
import { toast } from './toast.js';

/**
 * [data-document-open] buttons (document tables): ask the endpoint for a fresh, permissioned, 5-minute signed URL and
 * open the file in a new tab (or download it). The file is audited by the server when it is actually served.
 */
export function initDocumentLinks() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-document-open]');

        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();
        button.disabled = true;
        button.toggleAttribute('data-loading', true);

        try {
            const { data } = await http.post(button.dataset.documentOpen, { mode: button.dataset.documentMode || 'view' });
            const url = data?.data?.url;

            if (url) {
                const anchor = Object.assign(document.createElement('a'), { href: url, target: '_blank', rel: 'noopener' });
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
            }
        } catch (error) {
            const normalized = normalizeError(error);
            toast.error(normalized.message, { requestId: normalized.requestId });
        } finally {
            button.disabled = false;
            button.removeAttribute('data-loading');
        }
    });
}
