import { http, normalizeError } from './http.js';
import { serializeFields, toSearchParams } from './dom.js';
import { initCharts } from './charts.js';

/**
 * Dashboard filter form (P5.3): a Precognition call validates the range first (errors inline, nothing is requested while invalid), then the
 * widgets partial is fetched over AJAX from the same URL and swapped in; the URL keeps `?filters[...]` so the view is shareable.
 */
export function dashboardFilters(url, target = 'dashboard-widgets') {
    return {
        loading: false,
        errors: {},

        form() {
            return this.$el.tagName === 'FORM' ? this.$el : this.$el.querySelector('form');
        },

        preset(days) {
            const form = this.form();
            const today = new Date(`${form.dataset.today}T00:00:00`);
            const from = new Date(today);
            from.setDate(from.getDate() - (days - 1));
            const iso = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            const set = (name, value) => {
                const field = form.querySelector(`[name="filters[range][${name}]"]`);

                if (field) {
                    field.value = value;
                    field._flatpickr?.setDate(value, false);
                }
            };

            set('from', iso(from));
            set('to', iso(today));
            this.apply();
        },

        errorFor(name) {
            return (this.errors[name] ?? [])[0] ?? '';
        },

        async apply() {
            const params = toSearchParams({ filters: serializeFields(this.form()).filters ?? {} });
            this.errors = {};
            this.loading = true;

            try {
                await http.get(url, { params, paramsSerializer: { serialize: () => params.toString() }, headers: { Precognition: 'true' } });
                const { data } = await http.get(url, { params, paramsSerializer: { serialize: () => params.toString() }, headers: { Accept: 'text/html' }, responseType: 'text' });
                const container = document.getElementById(target);

                if (container) {
                    container.innerHTML = data;
                    initCharts(container);
                }

                const next = new URL(window.location.href);
                [...next.searchParams.keys()].filter((key) => key.startsWith('filters[')).forEach((key) => next.searchParams.delete(key));
                params.forEach((value, key) => next.searchParams.append(key, value));
                window.history.replaceState(window.history.state, '', next.toString());
            } catch (error) {
                const normalized = normalizeError(error);

                if (normalized.status === 422) {
                    this.errors = normalized.errors;
                } else {
                    window.Admin?.toast?.error(normalized.message, { requestId: normalized.requestId });
                }
            } finally {
                this.loading = false;
            }
        },
    };
}
