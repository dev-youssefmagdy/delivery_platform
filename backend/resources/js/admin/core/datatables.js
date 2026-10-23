import $ from 'jquery';
import DataTable from 'datatables.net';
import 'datatables.net-responsive';
import { config, csrfToken } from './config.js';
import { serializeFields } from './dom.js';
import { toast } from './toast.js';

/**
 * AdminTables registry (Plan §5.4). <x-admin.datatable> renders <table data-admin-table data-options data-language>;
 * options come from the Yajra HTML builder (AdminDataTable). The linked filters form
 * (<form data-filters-for="<table id>">, rendered by <x-admin.filters-card>) is merged into each request as
 * `filters[...]`. Errors are shown as toasts (never DataTables alerts).
 */
const registry = new Map();

DataTable.ext.errMode = 'none';

$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

export function filtersFor(tableId) {
    const form = document.querySelector(`form[data-filters-for="${tableId}"]`);

    return form ? serializeFields(form).filters ?? {} : {};
}

function handleXhrError(xhr) {
    if (!xhr || xhr.status === 0 || xhr.statusText === 'abort') {
        return;
    }

    const body = xhr.responseJSON || {};

    if (xhr.status === 401) {
        toast.error(body.message, { timeout: 0 });

        if (config().loginUrl) {
            window.setTimeout(() => window.location.assign(config().loginUrl), 1200);
        }

        return;
    }

    if (xhr.status === 419) {
        toast.error(body.message, { action: { label: document.documentElement.dataset.reloadLabel || '↻', onClick: () => window.location.reload() }, timeout: 0 });

        return;
    }

    toast.error(body.message || xhr.statusText, { requestId: body.meta?.request_id ?? xhr.getResponseHeader?.('X-Request-Id') });
}

/**
 * Listings may return `stats` next to their rows (the Offers monitor). Elements inside [data-stats-for="<table id>"]
 * carrying data-stat="<key>" show the value of that key after every draw.
 */
function updateStats(tableId, stats) {
    document.querySelectorAll(`[data-stats-for="${tableId}"] [data-stat]`).forEach((node) => {
        const value = stats[node.dataset.stat];

        if (value !== undefined) {
            node.textContent = String(value);
        }
    });
}

export const AdminTables = {
    init(root = document) {
        root.querySelectorAll('table[data-admin-table]').forEach((el) => this.mount(el));
    },

    mount(el) {
        if (registry.has(el.id) && document.body.contains(registry.get(el.id).table().node())) {
            return registry.get(el.id);
        }

        const options = JSON.parse(el.dataset.options || '{}');
        const ajax = typeof options.ajax === 'string' ? { url: options.ajax } : options.ajax || {};

        options.language = JSON.parse(el.dataset.language || '{}');
        options.ajax = {
            ...ajax,
            data: (data) => {
                data.filters = filtersFor(el.id);
            },
        };

        $(el).on('xhr.dt', (_event, _settings, json, xhr) => {
            if (json === null) {
                handleXhrError(xhr);
            } else {
                if (json.stats) {
                    updateStats(el.id, json.stats);
                }

                if (json.chart) {
                    document.dispatchEvent(new CustomEvent('admin:chart-update', { detail: { tableId: el.id, chart: json.chart } }));
                }
            }
        });

        const table = new DataTable(el, options);
        registry.set(el.id, table);
        el.dispatchEvent(new CustomEvent('admin-table:ready', { bubbles: true, detail: { id: el.id } }));

        return table;
    },

    get(id) {
        return registry.get(id) ?? null;
    },

    reload(id, resetPaging = false) {
        registry.get(id)?.ajax.reload(null, resetPaging);
    },

    reloadAll() {
        registry.forEach((table) => table.ajax.reload(null, false));
    },
};

document.addEventListener('admin:table-reload', (event) => {
    if (event.detail?.id) {
        AdminTables.reload(event.detail.id);
    }
});
