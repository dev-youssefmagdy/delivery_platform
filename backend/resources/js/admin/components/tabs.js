import { http, normalizeError } from '../core/http.js';
import { toast } from '../core/toast.js';

/**
 * Accessible tabs (role=tablist, arrow keys). Panels with data-tab-url lazy-load their HTML on first
 * activation (e.g. a related DataTable). A field revealing itself (admin:reveal) activates its panel.
 */
export function adminTabs(initial) {
    return {
        active: initial,
        loaded: {},

        init() {
            this.$el.addEventListener('admin:reveal', (event) => {
                let panel = event.target.closest('[data-tab-panel]');

                while (panel && panel.closest('[data-tabs]') !== this.$el) {
                    panel = panel.parentElement?.closest('[data-tab-panel]') ?? null;
                }

                if (panel) {
                    this.select(panel.dataset.tabPanel);
                }
            });
            this.load(this.active);
        },

        select(id) {
            this.active = id;
            this.load(id);
        },

        isActive(id) {
            return this.active === id;
        },

        keydown(event) {
            const tabs = [...this.$el.querySelectorAll(':scope [role="tab"]')].filter((tab) => tab.closest('[data-tabs]') === this.$el);
            const index = tabs.indexOf(document.activeElement);
            const rtl = document.documentElement.dir === 'rtl';
            const step = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1, Home: -Infinity, End: Infinity }[event.key];

            if (step === undefined || index < 0) {
                return;
            }

            event.preventDefault();
            const next = step === -Infinity ? 0 : step === Infinity ? tabs.length - 1 : (index + step + tabs.length) % tabs.length;
            tabs[next].focus();
            tabs[next].click();
        },

        async load(id) {
            const panel = [...this.$el.querySelectorAll(`[data-tab-panel="${id}"]`)].find((el) => el.closest('[data-tabs]') === this.$el);
            const url = panel?.dataset.tabUrl;

            if (!url || this.loaded[id]) {
                return;
            }

            this.loaded[id] = true;

            try {
                const response = await http.get(url, { headers: { Accept: 'text/html' } });
                panel.innerHTML = response.data;
                window.Admin?.init(panel);
            } catch (error) {
                this.loaded[id] = false;
                toast.error(normalizeError(error).message);
            }
        },
    };
}
