/**
 * Applies the instructions of a successful admin AJAX response (AdminFormResponse):
 * toast → replace_html → reset → close_modal → reload_table → reload → redirect.
 * Tables and modals are reached through DOM events so this module has no hard dependency on them.
 */
export function applyInstructions(data, { form = null, message = null, toast = null } = {}) {
    const instructions = data && typeof data === 'object' ? data : {};
    const navigates = Boolean(instructions.redirect || instructions.reload);

    if (message && toast) {
        if (navigates) {
            toast.flash('success', message);
        } else {
            toast.success(message);
        }
    }

    if (instructions.replace_html && typeof instructions.replace_html === 'object') {
        for (const [selector, html] of Object.entries(instructions.replace_html)) {
            document.querySelectorAll(selector).forEach((el) => {
                el.innerHTML = html;
                window.Admin?.init?.(el);
            });
        }
    }

    if (instructions.reset && form) {
        form.reset();
        form.dispatchEvent(new CustomEvent('admin:form-reset', { bubbles: true }));
    }

    const closeModal = instructions.close_modal ?? (form?.dataset.closeOnSuccess === 'true' ? true : null);

    if (closeModal) {
        const id = typeof closeModal === 'string' ? closeModal : form?.closest('[data-modal]')?.dataset.modal;
        window.dispatchEvent(new CustomEvent('admin-modal:close', { detail: { id } }));
    }

    const tables = new Set([
        ...[instructions.reload_table].flat().filter(Boolean),
        ...(form?.dataset.reloadTable ? form.dataset.reloadTable.split(',') : []),
    ]);

    tables.forEach((id) => document.dispatchEvent(new CustomEvent('admin:table-reload', { detail: { id: id.trim() } })));

    if (instructions.redirect) {
        window.location.assign(instructions.redirect);
    } else if (instructions.reload) {
        window.location.reload();
    }
}
