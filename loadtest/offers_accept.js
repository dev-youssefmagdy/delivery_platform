// Provider side of negotiation: GET /provider/offers (available requests), then POST an offer and let the customer accept.
// Needs open bookings in a city where the provider is dispatched; run booking_flow.js alongside, or seed searching bookings.
// ACCEPT=1 also accepts the first received offer with the customer token (contention on the same booking is the point of the test).
import { sleep } from 'k6';
import { BASE, tokens, headers, uuid, ok, http } from './lib.js';

const PROVIDER_TOKENS = tokens(__ENV.PROVIDER_TOKENS_FILE || './provider_tokens.json');
const CUSTOMER_TOKENS = tokens(__ENV.CUSTOMER_TOKENS_FILE || './customer_tokens.json');

export const options = {
  scenarios: {
    providers: { executor: 'constant-vus', vus: Number(__ENV.PROVIDERS || 50), duration: __ENV.DURATION || '5m' },
  },
  thresholds: { http_req_failed: ['rate<0.05'], http_req_duration: [`p(95)<${__ENV.P95_MS || 800}`] },
};

export default function () {
  const token = PROVIDER_TOKENS[(__VU - 1) % PROVIDER_TOKENS.length];
  const list = http.get(`${BASE}/provider/offers`, { headers: headers(token), tags: { name: 'available' } });
  ok(list, 'available requests');

  const first = (list.json('data') || [])[0];

  if (first) {
    const bookingId = first.booking_id || first.booking?.id || first.id;
    const offer = http.post(`${BASE}/provider/bookings/${bookingId}/offers`, JSON.stringify({ price_minor: Number(__ENV.OFFER_MINOR || 5000) }), {
      headers: headers(token, { 'Idempotency-Key': uuid() }), tags: { name: 'offer' },
    });
    ok(offer, 'create offer');

    if (__ENV.ACCEPT === '1' && offer.status < 300) {
      const customer = CUSTOMER_TOKENS[(__VU - 1) % CUSTOMER_TOKENS.length];
      const offerId = offer.json('data.id');
      ok(http.post(`${BASE}/bookings/${bookingId}/offers/${offerId}/accept`, '{}', { headers: headers(customer, { 'Idempotency-Key': uuid() }), tags: { name: 'accept' } }), 'accept');
    }
  }

  sleep(1);
}
