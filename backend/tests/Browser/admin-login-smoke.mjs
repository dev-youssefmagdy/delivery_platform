// Real-browser smoke check of the admin login form (run through scripts/browser-smoke.sh).
//
// Drives headless Chrome over the DevTools protocol with no extra dependency (Node >= 20.10 with
// --experimental-websocket). It proves, in a real browser, the "validate before submit" protocol on the
// login form:
//   1. an invalid form is stopped by client checks: no request leaves the page;
//   2. a server-side validation failure (Precognition 422, reset-password form) shows inline errors and never
//      sends the real submit;
//   3. a wrong password goes through validation (204) and submit (422) and shows one generic error;
//   4. correct credentials validate, submit, and land on the dashboard.
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
        ws.addEventListener('message', (event) => {
            const msg = JSON.parse(event.data);
            if (msg.id && this.pending.has(msg.id)) {
                const { resolve, reject } = this.pending.get(msg.id);
                this.pending.delete(msg.id);
                msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
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

    // 5. Sign out through the account menu form.
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
