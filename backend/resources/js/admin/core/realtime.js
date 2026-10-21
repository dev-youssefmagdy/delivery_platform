import { config, csrfToken } from './config.js';

/**
 * Realtime connection of the admin panel (SRS §16.6): one Echo/Reverb connection shared by every page widget, on the
 * private channel `admin.operations` (the server only authorizes admins holding `bookings.view`). It exposes the
 * connection health and the time of the last event so the UI never pretends to be live when it is not, and it detects
 * gaps in the stream-wide sequence so listeners can refetch instead of trusting a missed event.
 *
 *   realtime.connect()            start (idempotent); resolves to null when realtime is not configured
 *   realtime.onStatus(fn)         fn(status) with idle | connecting | connected | unavailable | failed | disabled
 *   realtime.onEvent(fn)          fn(type, payload, { gap }) for booking_changed | provider_changed | provider_moved
 *   realtime.state                { status, lastEventAt: Date|null }
 */
const CHANNEL = 'admin.operations';
const EVENTS = ['booking_changed', 'provider_changed', 'provider_moved'];
const CONNECTION_STATES = {
    initialized: 'connecting',
    connecting: 'connecting',
    connected: 'connected',
    unavailable: 'unavailable',
    disconnected: 'unavailable',
    failed: 'failed',
};

const statusListeners = new Set();
const eventListeners = new Set();
let starting = null;
let lastSequence = null;

export const realtime = {
    state: { status: 'idle', lastEventAt: null },

    connect() {
        starting ??= start();

        return starting;
    },

    onStatus(listener) {
        statusListeners.add(listener);
        listener(this.state.status);

        return () => statusListeners.delete(listener);
    },

    onEvent(listener) {
        eventListeners.add(listener);

        return () => eventListeners.delete(listener);
    },
};

function setStatus(status) {
    realtime.state.status = status;
    statusListeners.forEach((listener) => listener(status));
}

async function start() {
    const settings = config().realtime;

    if (!settings?.key) {
        setStatus('disabled');

        return null;
    }

    setStatus('connecting');

    try {
        const [{ default: Echo }, { default: Pusher }] = await Promise.all([import('laravel-echo'), import('pusher-js')]);
        window.Pusher = Pusher;

        const echo = new Echo({
            broadcaster: 'reverb',
            key: settings.key,
            wsHost: settings.host,
            wsPort: settings.port,
            wssPort: settings.port,
            forceTLS: settings.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: { headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } },
        });

        const connection = echo.connector.pusher.connection;
        connection.bind('state_change', ({ current }) => setStatus(CONNECTION_STATES[current] ?? 'connecting'));
        setStatus(CONNECTION_STATES[connection.state] ?? 'connecting');

        const channel = echo.private(CHANNEL);
        channel.subscription?.bind('pusher:subscription_error', () => setStatus('failed'));

        EVENTS.forEach((type) => channel.listen(`.operations.${type}`, (message) => receive(type, message)));

        return echo;
    } catch {
        setStatus('failed');

        return null;
    }
}

function receive(type, message) {
    realtime.state.lastEventAt = new Date();

    const sequence = Number(message?.sequence);
    const gap = Number.isFinite(sequence) && lastSequence !== null && sequence > lastSequence + 1;

    if (Number.isFinite(sequence)) {
        lastSequence = sequence;
    }

    eventListeners.forEach((listener) => listener(type, message?.payload ?? {}, { gap }));
    statusListeners.forEach((listener) => listener(realtime.state.status));
}

/** Fills every <x-admin.realtime-status> (dot, label, time of the last event) and starts the connection. */
export function initRealtimeStatus(root = document) {
    const elements = [...root.querySelectorAll('[data-realtime-status]:not([data-realtime-ready])')];

    if (elements.length === 0) {
        return;
    }

    const labels = config().i18n?.realtime ?? {};
    const colours = { connected: 'bg-success-600', connecting: 'bg-warning-500', unavailable: 'bg-warning-500', failed: 'bg-danger-600', disabled: 'bg-slate-300', idle: 'bg-slate-300' };
    const clock = new Intl.DateTimeFormat(config().locale || undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    elements.forEach((el) => {
        el.dataset.realtimeReady = 'true';
        const dot = el.querySelector('[data-realtime-dot]');
        const label = el.querySelector('[data-realtime-label]');
        const last = el.querySelector('[data-realtime-last]');

        realtime.onStatus((status) => {
            label.textContent = labels[status] ?? status;
            dot.className = `size-2 rounded-full ${colours[status] ?? colours.idle}`;
            el.dataset.status = status;

            if (realtime.state.lastEventAt) {
                last.textContent = clock.format(realtime.state.lastEventAt);
            }
        });
    });

    realtime.connect();
}
