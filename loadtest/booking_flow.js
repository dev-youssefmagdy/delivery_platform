// Customer booking creation and offer polling: POST /bookings (idempotent) then GET /bookings/{id}/offers.
// BOOKING_PAYLOAD_FILE is a JSON body that passes CreateBookingRequest for your seeded city/category (copy one from the API
// docs, docs/API.md section on bookings); the script only adds the idempotency key.
import { sleep } from 'k6';
import { BASE, tokens, headers, uuid, ok, http } from './lib.js';

const CUSTOMER_TOKENS = tokens(__ENV.CUSTOMER_TOKENS_FILE || './customer_tokens.json');
const PAYLOAD = open(__ENV.BOOKING_PAYLOAD_FILE || './booking_payload.json');

export const options = {
  scenarios: {
    create: { executor: 'constant-arrival-rate', rate: Number(__ENV.BOOKINGS_PER_MINUTE || 30), timeUnit: '1m', duration: __ENV.DURATION || '5m', preAllocatedVUs: 50, maxVUs: 200 },
  },
  thresholds: { http_req_failed: ['rate<0.01'], 'http_req_duration{name:create}': [`p(95)<${__ENV.P95_MS || 800}`], 'http_req_duration{name:offers}': [`p(95)<${__ENV.P95_MS || 800}`] },
};

export default function () {
  const token = CUSTOMER_TOKENS[(__VU - 1) % CUSTOMER_TOKENS.length];
  const res = http.post(`${BASE}/bookings`, PAYLOAD, { headers: headers(token, { 'Idempotency-Key': uuid() }), tags: { name: 'create' } });

  if (!ok(res, 'create booking')) {
    return;
  }

  const id = res.json('data.id') || res.json('data.uuid');

  for (let i = 0; i < Number(__ENV.OFFER_POLLS || 3); i++) {
    sleep(2);
    ok(http.get(`${BASE}/bookings/${id}/offers`, { headers: headers(token), tags: { name: 'offers' } }), 'offers list');
  }
}
