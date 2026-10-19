import { http } from './http.js';
import { t } from './config.js';

/**
 * Tom Select enhancement (lazy-loaded):
 *  - <select data-remote-select data-lookup-url="/admin/lookups/{lookup}"> searches the lookup endpoint (?q=);
 *  - <select data-enhanced-select> makes a static select searchable.
 * The original <select> stays the submitted field (errors/precognition keep working).
 */
export async function initSelects(root = document) {
    const selects = root.querySelectorAll('select[data-remote-select]:not(.tomselected), select[data-enhanced-select]:not(.tomselected)');

    if (!selects.length) {
        return;
    }

    const { default: TomSelect } = await import('tom-select');

    selects.forEach((select) => {
        const remote = select.hasAttribute('data-remote-select');
        const settings = {
            plugins: select.multiple ? ['remove_button'] : [],
            allowEmptyOption: !select.required,
            maxOptions: 50,
            hidePlaceholder: false,
            placeholder: select.dataset.placeholder || t('form.select_placeholder'),
            render: {
                no_results: () => `<div class="no-results px-3 py-2 text-slate-500">${t('common.no_results')}</div>`,
                loading: () => `<div class="px-3 py-2 text-slate-500">${t('common.loading')}</div>`,
            },
            onChange: () => select.dispatchEvent(new Event('change', { bubbles: true })),
        };

        if (remote) {
            Object.assign(settings, {
                valueField: 'id',
                labelField: 'text',
                searchField: ['text'],
                preload: 'focus',
                loadThrottle: 300,
                load(query, callback) {
                    http.get(select.dataset.lookupUrl, { params: { q: query } })
                        .then((response) => callback((response.data?.data ?? []).map((option) => ({ ...option, id: String(option.id) }))))
                        .catch(() => callback());
                },
            });
        }

        new TomSelect(select, settings);
    });
}
