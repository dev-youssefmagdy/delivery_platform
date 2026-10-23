/*
| Website variant of the "validate before submit" form (P5.5, SRS 17.3). Vanilla JS, no admin code, no dependencies.
|
|   submit -> preventDefault -> ignore when busy
|     1. clear previous errors
|     2. browser checks (checkValidity) -> inline errors in the page language, focus the first, STOP
|     3. server validation call: same URL + FormData with header `Precognition: true`
|          422 -> inline errors under each field, STOP (nothing is sent)
|          429 / 419 / 5xx / network -> message in the summary, STOP
|          204 -> valid
|     4. the real submit (same URL, JSON) -> success panel with the reference, or the same error rendering
|
| Input is never cleared on errors. Without JavaScript the form posts normally and errors come from the session.
*/

const SELECTOR = 'form[data-site-form]';

function csrf(form) {
    const input = form.querySelector('input[name="_token"]');

    return input ? input.value : '';
}

function fieldOf(form, name) {
    return form.querySelector('[data-field="' + CSS.escape(name) + '"]');
}

function controlOf(form, name) {
    return form.elements.namedItem(name);
}

function clearErrors(form, only = null) {
    const targets = only ? [only] : Array.from(form.querySelectorAll('[data-field]')).map((el) => el.getAttribute('data-field'));

    targets.forEach((name) => {
        const slot = form.querySelector('[data-error-for="' + CSS.escape(name) + '"]');
        const control = controlOf(form, name);

        if (slot) {
            slot.textContent = '';
            slot.hidden = true;
        }

        if (control && control.setAttribute) {
            control.removeAttribute('aria-invalid');
        }
    });

    if (!only) {
        const summary = form.querySelector('[data-error-summary]');

        if (summary) {
            summary.hidden = true;
            summary.querySelector('[data-error-list]').replaceChildren();
        }
    }
}

function showError(form, name, message) {
    const slot = form.querySelector('[data-error-for="' + CSS.escape(name) + '"]');
    const control = controlOf(form, name);

    if (slot) {
        slot.textContent = message;
        slot.hidden = false;
    }

    if (control && control.setAttribute) {
        control.setAttribute('aria-invalid', 'true');
    }
}

function renderErrors(form, errors, summaryMessage) {
    const list = form.querySelector('[data-error-list]');
    const summary = form.querySelector('[data-error-summary]');
    const items = [];
    let first = null;

    Object.keys(errors).filter((name) => name !== '_code').forEach((name) => {
        const message = Array.isArray(errors[name]) ? errors[name][0] : String(errors[name]);

        if (fieldOf(form, name)) {
            showError(form, name, message);
            first = first || name;
        }

        items.push({ name, message });
    });

    if (summary && list) {
        list.replaceChildren();

        items.forEach(({ name, message }) => {
            const li = document.createElement('li');

            if (fieldOf(form, name)) {
                const a = document.createElement('a');
                a.href = '#f-' + name;
                a.textContent = message;
                a.addEventListener('click', (event) => {
                    event.preventDefault();
                    const control = controlOf(form, name);
                    control && control.focus && control.focus();
                });
                li.appendChild(a);
            } else {
                li.textContent = message;
            }

            list.appendChild(li);
        });

        if (items.length === 0 && summaryMessage) {
            const li = document.createElement('li');
            li.textContent = summaryMessage;
            list.appendChild(li);
        }

        summary.hidden = false;
    }

    const control = first ? controlOf(form, first) : null;

    if (control && control.focus) {
        control.focus();
    } else if (summary) {
        summary.focus();
    }
}

function clientErrors(form) {
    const errors = {};

    Array.from(form.elements).forEach((el) => {
        if (!el.name || !el.willValidate || el.checkValidity()) {
            return;
        }

        const v = el.validity;
        const message = v.valueMissing
            ? el.getAttribute('data-msg-required')
            : el.getAttribute('data-msg-invalid');

        errors[el.name] = [message || el.validationMessage];
    });

    return errors;
}

function setBusy(form, busy) {
    form.dataset.busy = busy ? '1' : '';
    form.setAttribute('aria-busy', busy ? 'true' : 'false');

    const button = form.querySelector('[data-submit]');

    if (!button) {
        return;
    }

    button.disabled = busy;
    const spinner = button.querySelector('[data-spinner]');
    const label = button.querySelector('[data-submit-label]');

    if (spinner) {
        spinner.classList.toggle('hidden', !busy);
    }

    if (label) {
        if (busy) {
            label.dataset.original = label.textContent;
            label.textContent = form.dataset.msgSending || label.textContent;
        } else if (label.dataset.original) {
            label.textContent = label.dataset.original;
        }
    }
}

async function send(form, precognitive, only = null) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(form),
    };

    if (precognitive) {
        headers.Precognition = 'true';

        if (only) {
            headers['Precognition-Validate-Only'] = only;
        }
    }

    return fetch(form.action, { method: 'POST', body: new FormData(form), headers, credentials: 'same-origin' });
}

function failureMessage(form, response) {
    if (response.status === 429) {
        return form.dataset.msgThrottled;
    }

    if (response.status === 419) {
        return form.dataset.msgExpired;
    }

    return form.dataset.msgGeneric;
}

async function submit(form) {
    if (form.dataset.busy === '1') {
        return;
    }

    clearErrors(form);

    const local = clientErrors(form);

    if (Object.keys(local).length > 0) {
        renderErrors(form, local);

        return;
    }

    setBusy(form, true);

    try {
        const check = await send(form, true);

        if (check.status === 422) {
            const body = await check.json();
            renderErrors(form, body.errors || {}, body.message);
            setBusy(form, false);

            return;
        }

        if (check.status !== 204 && !check.ok) {
            renderErrors(form, {}, failureMessage(form, check));
            setBusy(form, false);

            return;
        }

        // Valid: fire the submit event (a hook may cancel it), then the real request.
        const proceed = form.dispatchEvent(new CustomEvent('site-form:submit', { bubbles: true, cancelable: true }));

        if (!proceed) {
            setBusy(form, false);

            return;
        }

        const response = await send(form, false);

        if (response.status === 422) {
            const body = await response.json();
            renderErrors(form, body.errors || {}, body.message);
            setBusy(form, false);

            return;
        }

        if (!response.ok) {
            renderErrors(form, {}, failureMessage(form, response));
            setBusy(form, false);

            return;
        }

        const body = await response.json();
        const wrap = form.closest('[data-site-form-wrap]');
        const panel = wrap && wrap.querySelector('[data-success-panel]');

        if (panel) {
            panel.querySelectorAll('[data-reference]').forEach((el) => {
                el.textContent = (body.data && body.data.reference) || '';
            });
            form.hidden = true;
            panel.hidden = false;
            panel.focus();
            panel.scrollIntoView({ block: 'center' });
        }

        setBusy(form, false);
    } catch (error) {
        renderErrors(form, {}, form.dataset.msgNetwork);
        setBusy(form, false);
    }
}

function liveValidate(form, control) {
    // On blur: only the browser checks, in the page language (the server runs on submit). Cheap and never blocks typing.
    if (!control.name || control.type === 'hidden' || control.name === 'website_url') {
        return;
    }

    clearErrors(form, control.name);

    if (control.value !== '' && !control.checkValidity()) {
        showError(form, control.name, control.getAttribute('data-msg-invalid') || control.validationMessage);
    }
}

export function bindSiteForms(root = document) {
    root.querySelectorAll(SELECTOR).forEach((form) => {
        if (form.dataset.bound) {
            return;
        }

        form.dataset.bound = '1';
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submit(form);
        });
        form.addEventListener('focusout', (event) => {
            if (event.target instanceof HTMLElement && event.target.matches('input, select, textarea')) {
                liveValidate(form, event.target);
            }
        });
    });
}
