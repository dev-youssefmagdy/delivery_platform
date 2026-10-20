// Real-browser smoke check of the admin login form (run through scripts/browser-smoke.sh).
//
// Drives headless Chrome over the DevTools protocol with no extra dependency (Node >= 20.10 with
// --experimental-websocket). It proves, in a real browser, the "validate before submit" protocol on the
// login form:
//   1. an invalid form is stopped by client checks: no request leaves the page;
//   2. a server-side validation failure (Precognition 422, reset-password form) shows inline errors and never
//      sends the real submit;
//   3. a wrong password goes through validation (204) and submit (422) and shows one generic error;
//   4. correct credentials validate, submit, and land on the dashboard;
//   5. (P1.4) the service-category form-schema builder: repeater add/save through the Precognition protocol and
//      the live preview (visible_if) without JavaScript errors;
//   6. (P1.6) the customers module: masked phone in the listing, lazy tab with its own DataTable, audited reveal of
//      the contact details, and the suspend modal going through Precognition before the real submit.
//   7. (P2.1) the verification review screen: the document viewer loads a private file through a short-lived signed
//      URL, and rejecting a document goes through the confirm dialog with a mandatory reason (Precognition first).
//
// Environment: BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME, CHROME_BIN (optional), CDP_PORT (optional).
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const BASE_URL = process.env.BASE_URL ?? 'http://127.0.0.1:8099';
const EMAIL = process.env.ADMIN_EMAIL;
const PASSWORD = process.env.ADMIN_PASSWORD;
const NAME = process.env.ADMIN_NAME;
const PORT = process.env.CDP_PORT ?? '9339';
const CHROME = process.env.CHROME_BIN ?? 'google-chrome';

if (!EMAIL || !PASSWORD || !NAME) {
    console.error('ADMIN_EMAIL, ADMIN_PASSWORD and ADMIN_NAME are required.');
    process.exit(2);
}

const profile = mkdtempSync(join(tmpdir(), 'admin-smoke-'));
const chrome = spawn(CHROME, [
    '--headless=new', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage', '--no-first-run',
    `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`, '--lang=en-US', 'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const failures = [];
const check = (ok, label) => {
    console.log(`${ok ? 'PASS' : 'FAIL'}: ${label}`);
    if (!ok) failures.push(label);
};

async function waitForChrome() {
    for (let i = 0; i < 100; i++) {
        try {
            const res = await fetch(`http://127.0.0.1:${PORT}/json/version`);
            if (res.ok) return;
        } catch { /* not up yet */ }
        await sleep(100);
    }
    throw new Error('Chrome did not start');
}

class Page {
    constructor(ws) {
        this.ws = ws;
        this.id = 0;
        this.pending = new Map();
        this.requests = [];
        this.jsErrors = [];
        ws.addEventListener('message', (event) => {
            const msg = JSON.parse(event.data);
            if (msg.id && this.pending.has(msg.id)) {
                const { resolve, reject } = this.pending.get(msg.id);
                this.pending.delete(msg.id);
                msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
            } else if (msg.method === 'Runtime.exceptionThrown') {
                this.jsErrors.push(msg.params.exceptionDetails?.exception?.description ?? msg.params.exceptionDetails?.text ?? 'exception');
            } else if (msg.method === 'Runtime.consoleAPICalled' && ['error', 'warning'].includes(msg.params.type)) {
                this.jsErrors.push(`console.${msg.params.type}: ` + msg.params.args.map((a) => a.value ?? a.description ?? '').join(' ').slice(0, 300));
            } else if (msg.method === 'Network.requestWillBeSent') {
                const { request } = msg.params;
                this.requests.push({ url: request.url, method: request.method, headers: request.headers });
            }
        });
    }

    send(method, params = {}) {
        const id = ++this.id;
        this.ws.send(JSON.stringify({ id, method, params }));
        return new Promise((resolve, reject) => this.pending.set(id, { resolve, reject }));
    }

    async eval(expression) {
        const { result, exceptionDetails } = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (exceptionDetails) throw new Error(exceptionDetails.exception?.description ?? 'evaluation failed');
        return result.value;
    }

    async waitFor(expression, label, timeout = 8000) {
        const start = Date.now();
        while (Date.now() - start < timeout) {
            try {
                if (await this.eval(expression)) return true;
            } catch { /* page navigating */ }
            await sleep(100);
        }
        console.log(`   (timeout waiting for: ${label})`);
        return false;
    }

    async goto(path) {
        await this.send('Page.navigate', { url: `${BASE_URL}${path}` });
        await this.waitFor("document.readyState === 'complete' && !!document.querySelector('[data-ajax-form]')", `form on ${path}`);
        await sleep(300); // let Alpine/ajax-form bind
    }

    posts(pathPart) {
        return this.requests.filter((r) => r.method === 'POST' && r.url.includes(pathPart));
    }

    /** Any write (POST/PUT/PATCH/DELETE) to a path, for forms that spoof or use real verbs. */
    writes(pathPart) {
        return this.requests.filter((r) => r.method !== 'GET' && r.url.includes(pathPart));
    }
}

const setField = (name, value) => `(() => { const el = document.querySelector('[name="${name}"]'); el.value = ${JSON.stringify(value)}; el.dispatchEvent(new Event('input', { bubbles: true })); return true; })()`;
const submit = "document.querySelector('[data-ajax-form]').requestSubmit()";
const errorText = (field) => `(document.querySelector('[data-error-for="${field}"]')?.textContent || '').trim()`;
const isPrecognitive = (request) => Object.entries(request.headers).some(([key, value]) => key.toLowerCase() === 'precognition' && value === 'true');

try {
    await waitForChrome();
    const targets = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
    const target = targets.find((t) => t.type === 'page');
    const ws = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { ws.addEventListener('open', resolve); ws.addEventListener('error', reject); });
    const page = new Page(ws);
    await page.send('Network.enable');
    await page.send('Page.enable');
    await page.send('Runtime.enable');

    // 1. Client checks stop an empty form: nothing is sent.
    await page.goto('/admin/login');
    check(await page.eval("!!document.querySelector('input[name=email]') && !!document.querySelector('input[name=password]')"), 'login form renders');
    await page.eval(submit);
    check(await page.waitFor(`${errorText('email')} !== ''`, 'required error on email'), 'empty submit shows inline errors');
    check(page.posts('/admin/login').length === 0, 'empty submit sends no request at all (client checks block it)');
    check((await page.eval('location.pathname')) === '/admin/login', 'empty submit stays on the login page');

    // 2. Server-side validation (Precognition) blocks the real submit.
    await page.goto('/admin/reset-password/not-a-real-token?email=' + encodeURIComponent(EMAIL));
    page.requests.length = 0;
    await page.eval(setField('password', 'weak'));
    await page.eval(setField('password_confirmation', 'weak'));
    await page.eval(submit);
    check(await page.waitFor(`${errorText('password')} !== ''`, 'server error on password'), 'weak password: server validation error shown inline');
    const resetPosts = page.posts('/admin/reset-password');
    check(resetPosts.length === 1 && isPrecognitive(resetPosts[0]), 'only the Precognition validation call was sent');
    check(resetPosts.every(isPrecognitive), 'no real submit was sent while errors exist');

    // 3. Wrong password: validation passes (204), submit answers 422 with one generic message.
    await page.goto('/admin/login');
    page.requests.length = 0;
    await page.eval(setField('email', EMAIL));
    await page.eval(setField('password', 'Wrong-Password-1!'));
    await page.eval(submit);
    check(await page.waitFor(`${errorText('email')} !== ''`, 'generic credentials error'), 'wrong password shows the generic error');
    const wrong = page.posts('/admin/login');
    check(wrong.length === 2 && isPrecognitive(wrong[0]) && !isPrecognitive(wrong[1]), 'validation call first, then the real submit');
    check((await page.eval('location.pathname')) === '/admin/login', 'still on the login page');

    // 4. Correct credentials: validate, submit, land on the dashboard.
    page.requests.length = 0;
    await page.eval(setField('password', PASSWORD));
    await page.eval(submit);
    check(await page.waitFor("location.pathname === '/admin/dashboard'", 'dashboard redirect', 12000), 'valid credentials end on /admin/dashboard');
    const ok = page.posts('/admin/login');
    check(ok.length === 2 && isPrecognitive(ok[0]) && !isPrecognitive(ok[1]), 'valid login: Precognition call, then the real submit');
    check(await page.waitFor(`document.body.innerText.includes(${JSON.stringify(NAME)})`, 'admin name on page'), 'dashboard greets the signed-in admin');

    // 5. Service category: form-schema builder (repeater) and live preview. Category 7 is the seeded furniture truck.
    page.jsErrors.length = 0;
    await page.goto('/admin/service-categories/7');
    check(await page.waitFor("!!document.querySelector('[data-tab-trigger=\"schema\"]')", 'tabs'), 'category page renders its tabs');
    await page.eval("document.querySelector('[data-tab-trigger=\"schema\"]').click()");
    const rowsBefore = await page.eval("document.querySelectorAll('fieldset[data-field=\"fields\"] [data-repeater-row]').length");
    check(rowsBefore >= 10, `schema builder shows the seeded fields (${rowsBefore})`);
    await page.eval("document.querySelector('fieldset[data-field=\"fields\"] > div.mt-3 button').click()");
    await sleep(200);
    const rowsAfterAdd = await page.eval("document.querySelectorAll('fieldset[data-field=\"fields\"] [data-repeater-row]').length");
    check(rowsAfterAdd === rowsBefore + 1, 'Add field appends a repeater row');

    // An invalid new row (bad key) is stopped by the validation call: no real submit.
    const fillRow = (key) => `(() => {
        const rows = document.querySelectorAll('fieldset[data-field="fields"] [data-repeater-row]');
        const row = rows[rows.length - 1];
        const set = (suffix, value) => { const el = row.querySelector('[name$="[' + suffix + ']"]'); el.value = value; el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); };
        set('key', ${JSON.stringify(key)}); set('type', 'text'); set('label_ar', 'ملاحظة إضافية');
        return row.querySelector('[name$="[key]"]').name;
    })()`;
    const newName = await page.eval(fillRow('items'));
    page.requests.length = 0;
    const schemaForm = "document.querySelector('form[action$=\"/service-categories/7/form-schema\"]')";
    await page.eval(`${schemaForm}.requestSubmit()`);
    const fieldKey = newName.replace(/\[(\w+)\]/g, '.$1');
    check(await page.waitFor(`(document.querySelector('[data-error-for="${fieldKey}"]')?.textContent || '').trim() !== '' || document.querySelector('[data-error-summary-wrapper]:not([hidden])') !== null`, 'builder error'), 'invalid builder row: error shown');
    const invalidPuts = page.writes('/form-schema');
    check(invalidPuts.length === 1 && isPrecognitive(invalidPuts[0]), `invalid schema (duplicate key): only the Precognition call, no real submit (${invalidPuts.length} writes)`);

    // A valid key is validated (204) and then saved; the page reloads with the new row.
    await page.eval(fillRow('extra_note'));
    page.requests.length = 0;
    await page.eval(`${schemaForm}.requestSubmit()`);
    for (let i = 0; i < 100 && page.writes('/form-schema').length < 2; i++) await sleep(100);
    await sleep(1000); // the success response reloads the page
    check(await page.waitFor(`document.querySelectorAll('fieldset[data-field="fields"] [data-repeater-row]').length === ${rowsBefore + 1} && document.readyState === 'complete'`, 'reload with saved row', 12000), 'valid schema saved: page reloaded with the new row');
    const savePuts = page.writes('/form-schema');
    check(savePuts.length === 2 && isPrecognitive(savePuts[0]) && !isPrecognitive(savePuts[1]), `valid schema: Precognition call, then the real submit (${savePuts.map((r) => r.method).join(',')})`);

    // Preview: conditional fields appear as the controlling answer changes.
    await page.eval("document.querySelector('[data-tab-trigger=\"preview\"]').click()");
    await sleep(300);
    const hidden = `(() => { const el = document.querySelector('[data-field-key=carry_distance]'); return !!el && getComputedStyle(el).display === 'none'; })()`;
    check(await page.eval(hidden), 'preview: conditional field is hidden before the answer');
    await page.eval("(() => { const no = document.querySelectorAll('[data-field-key=origin_elevator] input[type=radio]')[1]; no.click(); })()");
    await sleep(300);
    check(await page.eval(`!(${hidden})`), 'preview: conditional field appears after answering "No"');
    check(page.jsErrors.length === 0, `no JavaScript errors on the catalog pages${page.jsErrors.length ? ': ' + page.jsErrors[0] : ''}`);

    // 6. Customers: masked listing, lazy DataTable tab, reveal, suspend modal (Precognition then submit).
    page.jsErrors.length = 0;
    await page.goto('/admin/customers');
    check(await page.waitFor("document.querySelectorAll('#customers-table tbody a[href*=\"/admin/customers/\"]').length > 0", 'customer rows', 12000), 'customers listing loads rows through the DataTable');
    const listingText = await page.eval("document.querySelector('#customers-table').innerText");
    check(!listingText.includes('+201009998877') && listingText.includes('8877'), 'listing shows the phone masked only');
    check(await page.eval("document.querySelector('[data-filters-for=\"customers-table\"]')?.closest('[x-data]')?.querySelector('[x-show]') !== null"), 'filters card present');
    const customerHref = await page.eval("document.querySelector('#customers-table tbody a[href*=\"/admin/customers/\"]').getAttribute('href')");
    await page.goto(new URL(customerHref, BASE_URL).pathname);
    check(await page.waitFor("!!document.querySelector('[data-tab-trigger=\"devices\"]')", 'customer tabs'), 'customer page renders its tabs');
    page.requests.length = 0;
    await page.eval("document.querySelector('[data-tab-trigger=\"devices\"]').click()");
    check(await page.waitFor("!!document.querySelector('#customer-devices-table') && document.querySelectorAll('#customer-devices-table thead th').length >= 5", 'devices table', 12000), 'devices tab lazy-loads its own DataTable');
    check(await page.waitFor("(document.querySelector('#customer-devices-table tbody tr')?.textContent || '').trim() !== ''", 'devices rows'), 'devices DataTable drew its body (empty state, no fake rows)');
    check(page.requests.filter((r) => r.url.includes('/tabs/devices')).length >= 2, 'tab fragment and DataTable AJAX were separate requests');
    await page.eval("document.querySelector('[data-tab-trigger=\"profile\"]').click()");
    await page.eval("document.querySelector('[x-data^=\"adminReveal\"] button').click()");
    check(await page.waitFor("document.querySelector('[data-reveal-field=phone]')?.textContent.trim() === '+201009998877'", 'revealed phone'), 'reveal swaps in the full phone after the audited request');

    await page.eval("document.querySelector('[data-modal-open=\"customer-suspend\"]').click()");
    await sleep(400);
    const suspendForm = "document.querySelector('form[action*=\"/suspend\"]')";
    page.requests.length = 0;
    await page.eval(`${suspendForm}.requestSubmit()`);
    check(await page.waitFor(`(${suspendForm}.querySelector('[data-error-for=reason]')?.textContent || '').trim() !== ''`, 'reason error'), 'suspend without a reason: error shown inline');
    check(page.writes('/suspend').length === 0, 'suspend without a reason sends nothing (client check)');
    await page.eval(`(() => { const el = ${suspendForm}.querySelector('[name=reason]'); el.value = 'Repeated abuse of drivers'; el.dispatchEvent(new Event('input', { bubbles: true })); })()`);
    await page.eval(`${suspendForm}.requestSubmit()`);
    for (let i = 0; i < 100 && page.writes('/suspend').length < 2; i++) await sleep(100);
    const suspendWrites = page.writes('/suspend');
    check(suspendWrites.length === 2 && isPrecognitive(suspendWrites[0]) && !isPrecognitive(suspendWrites[1]), `suspend: Precognition call, then the real submit (${suspendWrites.length} writes)`);
    check(await page.waitFor("document.body.innerText.includes('Repeated abuse of drivers')", 'suspended notice', 12000), 'page reloads showing the suspension and its reason');
    check(page.jsErrors.length === 0, `no JavaScript errors on the customer pages${page.jsErrors.length ? ': ' + page.jsErrors[0] : ''}`);

    // 7. Verification review: queue row -> review screen -> document viewer -> reject with a mandatory reason.
    page.jsErrors.length = 0;
    await page.goto('/admin/verification');
    check(await page.waitFor("document.querySelectorAll('#verification-queue-table tbody a[href*=\"/admin/verification/\"]').length > 0", 'queue rows', 12000), 'verification queue loads rows through the DataTable');
    const reviewHref = await page.eval("document.querySelector('#verification-queue-table tbody a[href*=\"/admin/verification/\"]').getAttribute('href')");
    await page.send('Page.navigate', { url: new URL(reviewHref, BASE_URL).href });
    check(await page.waitFor("document.readyState === 'complete' && !!document.querySelector('[data-document-viewer]')", 'review screen', 12000), 'review screen renders the document viewer');
    await sleep(500);
    page.requests.length = 0;
    await page.eval("document.querySelector('[data-viewer-item]').click()");
    check(await page.waitFor("(() => { const img = document.querySelector('[data-viewer-image]'); return !!img && img.complete && img.naturalWidth > 0; })()", 'document image', 12000), 'selecting a document loads the private file through a signed URL');
    const signed = page.requests.find((r) => r.url.includes('/admin/provider-files/'));
    check(!!signed && signed.url.includes('signature=') && signed.url.includes('expires='), 'the file was requested with a signed, expiring URL');
    check(!page.requests.some((r) => /\/storage\/|private-files/.test(r.url)), 'no public storage path is ever requested');

    await page.eval("document.querySelector('[data-viewer-reject]').click()");
    await sleep(400);
    const confirmForm = "document.querySelector('[role=alertdialog] form')";
    page.requests.length = 0;
    await page.eval(`${confirmForm}.requestSubmit()`);
    check(await page.waitFor(`(${confirmForm}.querySelector('[data-error-for=reason]')?.textContent || '').trim() !== ''`, 'reason error'), 'reject without a reason: error shown inline');
    check(page.writes('/reject').length === 0, 'reject without a reason sends nothing (client check)');
    await page.eval(`(() => { const el = ${confirmForm}.querySelector('[name=reason]'); el.value = 'The photo is not readable'; el.dispatchEvent(new Event('input', { bubbles: true })); })()`);
    await page.eval(`${confirmForm}.requestSubmit()`);
    for (let i = 0; i < 100 && page.writes('/reject').length < 2; i++) await sleep(100);
    const rejectWrites = page.writes('/reject');
    check(rejectWrites.length === 2 && isPrecognitive(rejectWrites[0]) && !isPrecognitive(rejectWrites[1]), `reject: Precognition call, then the real submit (${rejectWrites.length} writes)`);
    check(await page.waitFor("document.readyState === 'complete' && document.body.innerText.includes('Rejected') && !!document.querySelector('[data-document-viewer]')", 'rejected state', 12000), 'page reloads showing the document as rejected');
    check(page.jsErrors.length === 0, `no JavaScript errors on the review screen${page.jsErrors.length ? ': ' + page.jsErrors[0] : ''}`);

    // 8. Sign out through the account menu form.
    await page.eval("document.querySelector('form[action$=\"/admin/logout\"] button[type=submit]').closest('form').requestSubmit()");
    check(await page.waitFor("location.pathname === '/admin/login'", 'back at login after logout', 12000), 'logout returns to the login page');

    ws.close();
} catch (error) {
    console.error(error);
    failures.push(String(error));
} finally {
    chrome.kill('SIGKILL');
    await sleep(300);
    try { rmSync(profile, { recursive: true, force: true }); } catch { /* Chrome may still hold files */ }
}

console.log(failures.length === 0 ? '\nBrowser smoke check passed.' : `\nBrowser smoke check FAILED (${failures.length}).`);
process.exit(failures.length === 0 ? 0 : 1);
