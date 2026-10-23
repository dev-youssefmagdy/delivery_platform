/*
| Public website entry (P5.5). Independent from the admin bundle (Plan §5.1): no Alpine, no jQuery, no admin modules.
| Only two small behaviours: the mobile menu and the validate-before-submit forms.
*/
import { bindSiteForms } from './site-form.js';

function bindNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');

    if (!toggle || !panel) {
        return;
    }

    const set = (open) => {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('hidden', !open);
    };

    toggle.addEventListener('click', () => set(toggle.getAttribute('aria-expanded') !== 'true'));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            set(false);
            toggle.focus();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindNav();
    bindSiteForms();
});
