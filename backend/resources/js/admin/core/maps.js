import { config } from './config.js';

/**
 * Leaflet map fields (lazy-loaded with their CSS):
 *  - [data-map-point]: draggable marker synced with the lat/lng inputs (ids in data-lat / data-lng);
 *  - [data-map-polygon]: Leaflet.draw polygon synced with a hidden JSON input ([[lat, lng], …]) (data-input);
 *  - [data-map-preview]: read-only point/polygon (data-point='[lat,lng]', data-polygon='[[lat,lng],…]').
 * Maps are always LTR inside (tiles), the page around them follows the UI direction.
 */
let leafletPromise = null;

export async function leaflet() {
    leafletPromise ??= (async () => {
        const [{ default: L }, icon, icon2x, shadow] = await Promise.all([
            import('leaflet'),
            import('leaflet/dist/images/marker-icon.png'),
            import('leaflet/dist/images/marker-icon-2x.png'),
            import('leaflet/dist/images/marker-shadow.png'),
            import('leaflet/dist/leaflet.css'),
        ]);

        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({ iconUrl: icon.default, iconRetinaUrl: icon2x.default, shadowUrl: shadow.default });
        window.L = L;

        return L;
    })();

    return leafletPromise;
}

export function baseMap(L, el, center, zoom, interactive = true, extra = {}) {
    const maps = config().maps || {};
    const map = L.map(el, {
        center: center || [maps.default_center?.lat ?? 30.0444, maps.default_center?.lng ?? 31.2357],
        zoom: zoom ?? maps.default_zoom ?? 11,
        scrollWheelZoom: false,
        dragging: interactive,
        zoomControl: interactive,
        doubleClickZoom: interactive,
        keyboard: interactive,
        ...extra,
    });

    L.tileLayer(maps.tile_url || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: maps.attribution || '',
        maxZoom: 19,
    }).addTo(map);

    // Containers inside closed tabs/modals measure 0px until shown.
    new ResizeObserver(() => map.invalidateSize()).observe(el);

    return map;
}

function parse(value) {
    try {
        return value ? JSON.parse(value) : null;
    } catch {
        return null;
    }
}

async function initPoint(el) {
    const L = await leaflet();
    const lat = document.getElementById(el.dataset.lat);
    const lng = document.getElementById(el.dataset.lng);
    const current = () => (lat.value !== '' && lng.value !== '' ? [Number(lat.value), Number(lng.value)] : null);
    const map = baseMap(L, el, current(), current() ? 15 : undefined);
    let marker = null;

    const place = (latlng, emit = true) => {
        if (!marker) {
            marker = L.marker(latlng, { draggable: true, keyboard: true }).addTo(map);
            marker.on('dragend', () => place(marker.getLatLng()));
        }

        marker.setLatLng(latlng);

        if (emit) {
            lat.value = Number(latlng.lat ?? latlng[0]).toFixed(7);
            lng.value = Number(latlng.lng ?? latlng[1]).toFixed(7);
            lat.dispatchEvent(new Event('change', { bubbles: true }));
            lng.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    if (current()) {
        place(current(), false);
    }

    map.on('click', (event) => place(event.latlng));

    [lat, lng].forEach((input) => input.addEventListener('input', () => {
        const point = current();

        if (point && point.every(Number.isFinite)) {
            place(point, false);
            map.panTo(point);
        }
    }));
}

async function initPolygon(el) {
    const L = await leaflet();
    await Promise.all([import('leaflet-draw'), import('leaflet-draw/dist/leaflet.draw.css')]);

    const input = document.getElementById(el.dataset.input);
    const points = parse(input.value);
    const map = baseMap(L, el);
    const layers = new L.FeatureGroup().addTo(map);

    if (Array.isArray(points) && points.length >= 3) {
        const polygon = L.polygon(points).addTo(layers);
        map.fitBounds(polygon.getBounds(), { padding: [16, 16] });
    }

    map.addControl(new L.Control.Draw({
        edit: { featureGroup: layers },
        draw: { polygon: { allowIntersection: false, showArea: true }, polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false },
    }));

    const sync = () => {
        const polygon = layers.getLayers()[0];
        input.value = polygon
            ? JSON.stringify(polygon.getLatLngs()[0].map((point) => [Number(point.lat.toFixed(7)), Number(point.lng.toFixed(7))]))
            : '';
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    map.on(L.Draw.Event.CREATED, (event) => {
        layers.clearLayers();
        layers.addLayer(event.layer);
        sync();
    });
    map.on(L.Draw.Event.EDITED, sync);
    map.on(L.Draw.Event.DELETED, sync);
}

async function initPreview(el) {
    const L = await leaflet();
    const point = parse(el.dataset.point);
    const polygon = parse(el.dataset.polygon);
    const map = baseMap(L, el, point, point ? 14 : undefined, false);

    if (Array.isArray(polygon) && polygon.length >= 3) {
        map.fitBounds(L.polygon(polygon).addTo(map).getBounds(), { padding: [16, 16] });
    }

    if (Array.isArray(point)) {
        L.marker(point, { keyboard: false }).addTo(map);
    }
}

export async function initMaps(root = document) {
    const tasks = [];

    root.querySelectorAll('[data-map-point]:not([data-map-ready])').forEach((el) => {
        el.dataset.mapReady = 'true';
        tasks.push(initPoint(el));
    });
    root.querySelectorAll('[data-map-polygon]:not([data-map-ready])').forEach((el) => {
        el.dataset.mapReady = 'true';
        tasks.push(initPolygon(el));
    });
    root.querySelectorAll('[data-map-preview]:not([data-map-ready])').forEach((el) => {
        el.dataset.mapReady = 'true';
        tasks.push(initPreview(el));
    });

    await Promise.allSettled(tasks);
}
