import { http } from './http.js';

/**
 * Live overlap warning for the zone polygon field: `[data-zone-overlap]` (data-url, data-input = id of the hidden
 * polygon input, optional data-zone-id, data-label) posts the polygon whenever the drawing changes and lists the
 * zones it overlaps. Overlap is only a warning; the editor never blocks saving.
 */
export function overlapMessage(label, zones) {
    if (!Array.isArray(zones) || zones.length === 0) {
        return '';
    }

    return `${label} ${zones.map((zone) => `${zone.name} (${zone.city})`).join(document.documentElement.lang === 'en' ? ', ' : '، ')}`;
}

export function initZoneOverlap(root = document) {
    root.querySelectorAll('[data-zone-overlap]:not([data-overlap-ready])').forEach((el) => {
        el.dataset.overlapReady = 'true';
        const input = document.getElementById(el.dataset.input);

        if (!input) {
            return;
        }

        let timer = null;
        let sequence = 0;

        const render = (text) => {
            el.textContent = text;
            el.className = text ? 'mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800' : 'mt-3';
        };

        const check = async () => {
            const mine = ++sequence;

            if (!input.value) {
                render('');

                return;
            }

            try {
                const { data } = await http.post(el.dataset.url, { polygon: input.value, zone_id: el.dataset.zoneId || null });

                if (mine === sequence) {
                    render(overlapMessage(el.dataset.label, data?.data?.overlaps));
                }
            } catch {
                if (mine === sequence) {
                    render('');
                }
            }
        };

        input.addEventListener('change', () => {
            clearTimeout(timer);
            timer = setTimeout(check, 400);
        });

        if (input.value) {
            check();
        }
    });
}
