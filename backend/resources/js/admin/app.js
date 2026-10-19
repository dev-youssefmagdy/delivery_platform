/*
| Admin panel entry (Plan §5.1). Alpine + axios + jQuery/DataTables 2 (+ Responsive); heavier libraries
| (Tom Select, flatpickr, Leaflet, Chart.js) are lazy-loaded by the modules that need them.
*/
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { http } from './core/http.js';
import { toast } from './core/toast.js';
import { initAjaxForms } from './core/ajax-form.js';
import { AdminTables } from './core/datatables.js';
import { filtersCard } from './core/filters-card.js';
import { initSelects } from './core/remote-select.js';
import { initDatePickers } from './core/date-pickers.js';
import { initMaps } from './core/maps.js';
import { initCharts } from './core/charts.js';
import { initExports } from './core/exports.js';
import { askConfirm, confirmDialog, initConfirmActions } from './core/confirm-action.js';
import { adminModal, initModalTriggers } from './core/modal.js';
import { adminTabs } from './components/tabs.js';
import { adminDropdown, adminFile, adminMoney, adminRepeater, adminSidebar } from './components/ui.js';

const Admin = {
    http,
    toast,
    tables: AdminTables,
    confirm: askConfirm,

    /** Initialise non-Alpine behaviour inside root (page load, AJAX-loaded modal/tab content, new rows). */
    init(root = document) {
        initAjaxForms(root);
        AdminTables.init(root);
        initSelects(root);
        initDatePickers(root);
        initMaps(root);
        initCharts(root);
    },
};

window.Admin = Admin;
window.AdminTables = AdminTables;
window.Alpine = Alpine;

Alpine.plugin(focus);
Alpine.data('filtersCard', filtersCard);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('adminModal', adminModal);
Alpine.data('adminTabs', adminTabs);
Alpine.data('adminDropdown', adminDropdown);
Alpine.data('adminSidebar', adminSidebar);
Alpine.data('adminRepeater', adminRepeater);
Alpine.data('adminMoney', adminMoney);
Alpine.data('adminFile', adminFile);

initConfirmActions();
initModalTriggers();
initExports();

Alpine.start();
Admin.init(document);
toast.consumeFlash();
