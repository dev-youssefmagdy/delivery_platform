import { config } from './config.js';
import { nameToKey } from './dom.js';

/**
 * flatpickr on <input data-datepicker="date|datetime|time"> (lazy-loaded, Arabic locale when the UI is ar).
 * Values are submitted as `Y-m-d` / `Y-m-d H:i` (UTC conversion is the server's job); a localized alt input is
 * shown to the user. Range pairs link through data-range-end="<id>" / data-range-start="<id>".
 */
export async function initDatePickers(root = document) {
    const inputs = root.querySelectorAll('input[data-datepicker]:not(.flatpickr-input)');

    if (!inputs.length) {
        return;
    }

    const [{ default: flatpickr }, { Arabic }] = await Promise.all([import('flatpickr'), import('flatpickr/dist/l10n/ar.js')]);
    const arabic = config().locale === 'ar';

    inputs.forEach((input) => {
        const mode = input.dataset.datepicker || 'date';
        const withTime = mode === 'datetime';

        const picker = flatpickr(input, {
            locale: arabic ? Arabic : 'default',
            enableTime: withTime || mode === 'time',
            noCalendar: mode === 'time',
            time_24hr: true,
            dateFormat: mode === 'time' ? 'H:i' : withTime ? 'Y-m-d H:i' : 'Y-m-d',
            altInput: true,
            altFormat: mode === 'time' ? 'H:i' : withTime ? 'j F Y H:i' : 'j F Y',
            allowInput: true,
            minDate: input.dataset.minDate || null,
            maxDate: input.dataset.maxDate || null,
            position: 'auto',
            onChange: () => input.dispatchEvent(new Event('change', { bubbles: true })),
        });

        if (picker.altInput) {
            picker.altInput.dataset.errorKey = nameToKey(input.name);
            picker.altInput.id = `${input.id}-display`;
            picker.altInput.setAttribute('aria-describedby', input.getAttribute('aria-describedby') || '');

            const label = input.id ? root.querySelector?.(`label[for="${input.id}"]`) : null;

            if (label) {
                label.htmlFor = picker.altInput.id;
            }
        }

        const linkTo = (otherId, key) => {
            const other = document.getElementById(otherId);
            input.addEventListener('change', () => other?._flatpickr?.set(key, input.value || null));
        };

        if (input.dataset.rangeEnd) {
            linkTo(input.dataset.rangeEnd, 'minDate');
        }

        if (input.dataset.rangeStart) {
            linkTo(input.dataset.rangeStart, 'maxDate');
        }

        input.closest('form')?.addEventListener('admin:form-reset', () => picker.clear(false));
    });
}
