import { serializeFields, toSearchParams } from './dom.js';

/**
 * Alpine component for <x-admin.filters-card> (Plan §5.4): collapsed by default (even when filters are
 * active — the badge tells), Apply (or Enter) reloads the linked table and syncs ?filters[...] to the URL,
 * Reset clears inputs, URL and table. Initial values are pre-filled server-side from the query string, so the
 * first draw is already filtered.
 */
export function filtersCard(target) {
    return {
        open: false,
        count: 0,

        init() {
            this.count = this.countActive();
            this.$el.addEventListener('admin:reveal', () => {
                this.open = true;
            });
        },

        form() {
            return this.$el.querySelector('form[data-filters-for]');
        },

        countActive() {
            const filters = serializeFields(this.form()).filters ?? {};

            return Object.values(filters).filter((value) => {
                if (Array.isArray(value)) {
                    return value.length > 0;
                }

                if (value && typeof value === 'object') {
                    return Object.values(value).some((inner) => inner !== '' && inner !== null);
                }

                return value !== '' && value !== null && value !== undefined;
            }).length;
        },

        apply() {
            this.count = this.countActive();
            this.syncUrl();
            window.AdminTables?.reload(target, true);
        },

        reset() {
            const form = this.form();

            form.querySelectorAll('input, select, textarea').forEach((field) => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = field.value === '' && field.type === 'radio';
                } else if (field.tagName === 'SELECT') {
                    Array.from(field.options).forEach((option) => {
                        option.selected = false;
                    });
                } else if (field.type !== 'hidden' || field.name.startsWith('filters')) {
                    field.value = '';
                }

                field.tomselect?.clear(true);
                field._flatpickr?.clear(false);
            });

            this.apply();
        },

        syncUrl() {
            const url = new URL(window.location.href);
            [...url.searchParams.keys()].filter((key) => key.startsWith('filters[')).forEach((key) => url.searchParams.delete(key));

            const params = toSearchParams({ filters: serializeFields(this.form()).filters ?? {} });
            params.forEach((value, key) => url.searchParams.append(key, value));
            window.history.replaceState(window.history.state, '', url.toString());
        },
    };
}
