// Shared helpers for the k6 scripts. Nothing here is a measurement; see docs/PERFORMANCE.md for how to record real numbers.
import http from 'k6/http';
import { check } from 'k6';

export const BASE = __ENV.BASE_URL || 'http://localhost:8000/api/v1';

// TOKENS_FILE: JSON array of Sanctum bearer tokens (create them with the perf database users; see loadtest/README.md).
export function tokens(file) {
  return JSON.parse(open(file));
}

export function headers(token, extra = {}) {
  return Object.assign({
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'Accept-Language': 'en',
  }, extra);
}

export function uuid() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
  });
}

export function ok(res, name) {
  return check(res, { [`${name} 2xx`]: (r) => r.status >= 200 && r.status < 300 });
}

export { http };
