import axios from 'axios';
import { config, csrfToken, t } from './config.js';

/**
 * The single axios instance for admin AJAX (Plan §5.1): CSRF, JSON accept, X-Requested-With, a fresh
 * X-Request-Id per request (correlation id, echoed in error toasts) and the UI locale.
 */
export function uuid() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;

        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

export function createHttp() {
    const instance = axios.create({
        withCredentials: true,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    instance.interceptors.request.use((request) => {
        request.headers['X-CSRF-TOKEN'] = csrfToken();
        request.headers['X-Request-Id'] ??= uuid();

        if (config().locale) {
            request.headers['X-Locale'] = config().locale;
            request.headers['Accept-Language'] = config().locale;
        }

        return request;
    });

    return instance;
}

export const http = createHttp();

/**
 * Normalises any axios error/response into the envelope shape the UI uses:
 * { status, message, code, errors: {field: [msg]}, requestId, data, network }.
 */
export function normalizeError(error) {
    const response = error?.response;

    if (!response) {
        return {
            status: 0,
            network: true,
            message: t('http.network'),
            code: 'network_error',
            errors: {},
            requestId: error?.config?.headers?.['X-Request-Id'] ?? null,
            data: null,
        };
    }

    const body = response.data && typeof response.data === 'object' ? response.data : {};
    const rawErrors = body.errors && typeof body.errors === 'object' ? { ...body.errors } : {};
    const code = typeof rawErrors._code === 'string' ? rawErrors._code : null;
    delete rawErrors._code;

    return {
        status: response.status,
        network: false,
        message: body.message || t(`http.${response.status >= 500 ? 500 : response.status}`, {}) || t('http.500'),
        code,
        errors: rawErrors,
        requestId: body.meta?.request_id ?? response.headers?.['x-request-id'] ?? null,
        data: body.data ?? null,
    };
}
