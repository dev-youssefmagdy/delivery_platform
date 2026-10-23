// Provider location pings: POST /provider/location, batches of 1 sample, one VU = one provider (every ~5 s while online).
// Target rate is a parameter: PROVIDERS online x 1 ping / 5 s. The route is rate limited per user (RATE_LIMIT_LOCATION_PER_MINUTE).
import { sleep } from 'k6';
import { BASE, tokens, headers, ok, http } from './lib.js';

const PROVIDER_TOKENS = tokens(__ENV.PROVIDER_TOKENS_FILE || './provider_tokens.json');
const PROVIDERS = Number(__ENV.PROVIDERS || 100);

export const options = {
  scenarios: {
    pings: { executor: 'constant-vus', vus: PROVIDERS, duration: __ENV.DURATION || '2m' },
  },
  // Thresholds are the SRS targets to compare against, not results. Edit them with the numbers agreed in SRS 23.1.
  thresholds: { http_req_failed: ['rate<0.01'], http_req_duration: [`p(95)<${__ENV.P95_MS || 300}`] },
};

export default function () {
  const token = PROVIDER_TOKENS[(__VU - 1) % PROVIDER_TOKENS.length];
  const lat = 30.04 + Math.random() * 0.05;
  const lng = 31.23 + Math.random() * 0.05;
  const body = JSON.stringify({ samples: [{ lat, lng, recorded_at: new Date().toISOString(), accuracy_m: 8, heading: 90, speed_mps: 6, source: 'gps' }] });
  ok(http.post(`${BASE}/provider/location`, body, { headers: headers(token) }), 'location');
  sleep(Number(__ENV.PING_SECONDS || 5));
}
