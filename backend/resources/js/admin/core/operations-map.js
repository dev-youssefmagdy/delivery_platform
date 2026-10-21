import { config, t } from './config.js';
import { baseMap, leaflet } from './maps.js';
import { realtime } from './realtime.js';

/**
 * Live operations map (SRS §16.6). [data-operations-map] holds the first snapshot (data-snapshot) and the URL to refetch
 * it. Online providers and active bookings are two clustered layers; hovering shows an id and a category, never a name
 * or phone number (the snapshot does not contain them). Events from the operations feed move markers in place; a gap in
 * the feed, an unknown booking or a provider coming online triggers a (debounced) snapshot refetch, and the snapshot is
 * also refetched on a timer, so a dead websocket degrades to polling instead of a frozen map.
 */
const ACTIVE = ['searching_offers', 'offers_received', 'provider_selected', 'provider_heading_to_pickup', 'provider_arrived', 'in_progress', 'delivery_attempt_failed'];
const SEARCHING = ['searching_offers', 'offers_received'];
const COLOURS = { provider: '#16a34a', busy: '#2563eb', searching: '#f59e0b', assigned: '#7c3aed' };

const short = (id) => String(id).slice(0, 8);

export async function initOperationsMap(root = document) {
    const el = root.querySelector('[data-operations-map]:not([data-map-ready])');

    if (!el) {
        return;
    }

    el.dataset.mapReady = 'true';

    const L = await leaflet();
    await Promise.all([import('leaflet.markercluster'), import('leaflet.markercluster/dist/MarkerCluster.css'), import('leaflet.markercluster/dist/MarkerCluster.Default.css')]);

    const map = baseMap(L, el, null, null, true, { scrollWheelZoom: true });
    const layers = { providers: L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 50 }), bookings: L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 50 }) };
    const providers = new Map();
    const bookings = new Map();
    const labels = JSON.parse(el.dataset.statusLabels || '{}');

    Object.values(layers).forEach((layer) => map.addLayer(layer));

    const marker = (point, colour, tooltip) => L.circleMarker([point.lat, point.lng], { radius: 8, color: '#ffffff', weight: 2, fillColor: colour, fillOpacity: 0.95 }).bindTooltip(tooltip, { direction: 'top' });

    const providerTip = (p) => `${t('operations.provider')} ${short(p.id)}${p.category ? ` · ${p.category}` : ''} · ${p.busy ? t('operations.busy') : t('operations.available')}`;
    const bookingTip = (b) => `${b.code}${b.category ? ` · ${b.category}` : ''} · ${b.status_label ?? labels[b.status] ?? b.status}`;
    const bookingColour = (status) => (SEARCHING.includes(status) ? COLOURS.searching : COLOURS.assigned);

    function upsertProvider(p) {
        const existing = providers.get(p.id);

        if (existing) {
            existing.data = { ...existing.data, ...p };
            existing.marker.setLatLng([existing.data.lat, existing.data.lng]).setStyle({ fillColor: existing.data.busy ? COLOURS.busy : COLOURS.provider });
            existing.marker.setTooltipContent(providerTip(existing.data));

            return;
        }

        const created = marker(p, p.busy ? COLOURS.busy : COLOURS.provider, providerTip(p));
        providers.set(p.id, { data: p, marker: created });
        layers.providers.addLayer(created);
    }

    function upsertBooking(b) {
        const existing = bookings.get(b.id);

        if (existing) {
            existing.data = { ...existing.data, ...b };
            existing.marker.setStyle({ fillColor: bookingColour(existing.data.status) });
            existing.marker.setTooltipContent(bookingTip(existing.data));

            return;
        }

        const created = marker(b, bookingColour(b.status), bookingTip(b));
        bookings.set(b.id, { data: b, marker: created });
        layers.bookings.addLayer(created);
    }

    function drop(store, layer, id) {
        const existing = store.get(id);

        if (existing) {
            layer.removeLayer(existing.marker);
            store.delete(id);
        }
    }

    function counters() {
        const values = {
            providers: providers.size,
            busy: [...providers.values()].filter((p) => p.data.busy).length,
            bookings: bookings.size,
            searching: [...bookings.values()].filter((b) => SEARCHING.includes(b.data.status)).length,
        };

        Object.entries(values).forEach(([key, value]) => document.querySelectorAll(`[data-stat="${key}"]`).forEach((node) => { node.textContent = String(value); }));
    }

    function apply(snapshot) {
        const providerIds = new Set(snapshot.providers.map((p) => p.id));
        const bookingIds = new Set(snapshot.bookings.map((b) => b.id));

        [...providers.keys()].filter((id) => !providerIds.has(id)).forEach((id) => drop(providers, layers.providers, id));
        [...bookings.keys()].filter((id) => !bookingIds.has(id)).forEach((id) => drop(bookings, layers.bookings, id));
        snapshot.providers.forEach(upsertProvider);
        snapshot.bookings.forEach(upsertBooking);
        counters();

        const updated = document.querySelector('[data-map-updated]');

        if (updated) {
            updated.textContent = new Date(snapshot.generated_at || Date.now()).toLocaleTimeString(config().locale || undefined);
        }
    }

    let refreshTimer = null;

    async function refresh() {
        try {
            const response = await window.Admin.http.get(el.dataset.snapshotUrl);
            apply(response.data.data);
        } catch {
            // The next tick or event retries; errors are surfaced by the http client's toast.
        }
    }

    const scheduleRefresh = () => {
        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refresh, 1500);
    };

    document.querySelectorAll('[data-map-layer]').forEach((toggle) => toggle.addEventListener('change', () => {
        const layer = layers[toggle.dataset.mapLayer];

        if (layer) {
            toggle.checked ? map.addLayer(layer) : map.removeLayer(layer);
        }
    }));

    apply(JSON.parse(el.dataset.snapshot || '{"providers":[],"bookings":[],"generated_at":null}'));

    const all = [...providers.values(), ...bookings.values()].map((item) => [item.data.lat, item.data.lng]);

    if (all.length > 0) {
        map.fitBounds(all, { padding: [32, 32], maxZoom: 14 });
    }

    realtime.onEvent((type, payload, meta) => {
        if (meta.gap) {
            scheduleRefresh();

            return;
        }

        if (type === 'provider_moved') {
            const known = providers.get(payload.uuid);

            if (known) {
                upsertProvider({ id: payload.uuid, lat: payload.lat, lng: payload.lng, busy: payload.busy });
                counters();
            }
        } else if (type === 'provider_changed') {
            if (payload.online) {
                scheduleRefresh();
            } else {
                drop(providers, layers.providers, payload.uuid);
                counters();
            }
        } else if (type === 'booking_changed') {
            if (!ACTIVE.includes(payload.status)) {
                drop(bookings, layers.bookings, payload.uuid);
                counters();
            } else if (bookings.has(payload.uuid)) {
                upsertBooking({ id: payload.uuid, status: payload.status, status_label: labels[payload.status] });
                counters();
            } else {
                scheduleRefresh();
            }
        }
    });

    window.setInterval(refresh, (Number(el.dataset.pollSeconds) || 30) * 1000);
    realtime.connect();
}
