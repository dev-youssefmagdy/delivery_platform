import { AdminTables } from './datatables.js';
import { realtime } from './realtime.js';

/**
 * "Live refresh" switch (<x-admin.live-refresh>): while on, the table reloads on a timer and, debounced, whenever the
 * operations feed reports a booking change or a gap. Hidden tabs do not poll. The choice is kept in this browser only.
 */
const KEY = 'admin.live-refresh.';

function stored(table) {
    try {
        return window.localStorage.getItem(KEY + table) === '1';
    } catch {
        return false;
    }
}

function remember(table, on) {
    try {
        window.localStorage.setItem(KEY + table, on ? '1' : '0');
    } catch {
        // Private mode or blocked storage: the switch simply starts off next time.
    }
}

export function initLiveRefresh(root = document) {
    root.querySelectorAll('[data-live-refresh]:not([data-live-ready])').forEach((el) => {
        el.dataset.liveReady = 'true';
        const table = el.dataset.table;
        const seconds = Math.max(5, Number(el.dataset.seconds) || 20);
        const input = el.querySelector('[data-live-refresh-input]');
        let timer = null;
        let debounce = null;
        let off = null;

        const reload = () => {
            if (!document.hidden) {
                AdminTables.reload(table);
            }
        };

        const start = () => {
            timer = window.setInterval(reload, seconds * 1000);
            realtime.connect();
            off = realtime.onEvent((type, _payload, meta) => {
                if (type === 'booking_changed' || type === 'safety_incident' || meta.gap) {
                    window.clearTimeout(debounce);
                    debounce = window.setTimeout(reload, 1500);
                }
            });
        };

        const stop = () => {
            window.clearInterval(timer);
            window.clearTimeout(debounce);
            off?.();
            timer = null;
            off = null;
        };

        input.checked = stored(table);

        if (input.checked) {
            start();
        }

        input.addEventListener('change', () => {
            remember(table, input.checked);
            stop();

            if (input.checked) {
                start();
                reload();
            }
        });
    });
}
