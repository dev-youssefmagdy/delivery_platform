/**
 * Charts for the dashboard and the reports (P5.3, ADR-178). Chart.js is lazy-loaded; <x-admin.chart.line|bar|donut> render a
 * <figure data-admin-chart> whose <canvas data-chart='{"type","labels","series","unit",...}'> carries the payload.
 *
 * Rules (dataviz): series colours are the validated categorical SLOTS (--viz-1..8, set in admin.css for light and dark) and follow the
 * series identity, never its rank; marks are thin (2px lines, 4px rounded bar ends, 2px surface gap between fills); the grid and axes are
 * quiet; every chart has an HTML legend, a hover tooltip and a table of the same numbers; text uses text tokens, never the series colour.
 * RTL: the time axis runs right to left, the value axis sits on the right, tooltips and legends are right to left, numbers use the
 * viewer's locale digits. A DataTable-bound chart (data-chart-for) is redrawn from `json.chart` after every table reload.
 */
const instances = new WeakMap();
let ChartLib = null;

const isRtl = () => document.documentElement.dir === 'rtl';
const locale = () => (document.documentElement.lang === 'ar' ? 'ar-EG' : document.documentElement.lang || 'en');

function token(el, name, fallback) {
    return getComputedStyle(el).getPropertyValue(name).trim() || fallback;
}

function seriesColor(el, slot) {
    return token(el, `--viz-${((slot - 1) % 8) + 1}`, '#2a78d6');
}

function formatValue(payload, value, { compact = false } = {}) {
    if (value === null || value === undefined) {
        return '—';
    }

    if (payload.unit === 'money') {
        const major = value / 10 ** (payload.exponent ?? 2);

        try {
            return new Intl.NumberFormat(locale(), { style: 'currency', currency: payload.currency || 'EGP', notation: compact ? 'compact' : 'standard', maximumFractionDigits: compact ? 1 : 2 }).format(major);
        } catch {
            return new Intl.NumberFormat(locale(), { maximumFractionDigits: 2 }).format(major);
        }
    }

    if (payload.unit === 'percent') {
        return `${new Intl.NumberFormat(locale(), { maximumFractionDigits: 1 }).format(value)}%`;
    }

    return new Intl.NumberFormat(locale(), { notation: compact ? 'compact' : 'standard', maximumFractionDigits: 1 }).format(value);
}

function formatLabel(payload, label, long = false) {
    if (payload.labelKind !== 'date' || !/^\d{4}-\d{2}-\d{2}/.test(label)) {
        return label;
    }

    const date = new Date(`${label.slice(0, 10)}T00:00:00`);

    return new Intl.DateTimeFormat(locale(), long ? { year: 'numeric', month: 'short', day: 'numeric' } : { month: 'short', day: 'numeric' }).format(date);
}

/**
 * Donut slices beyond the eighth colour fold into one "other" slice (never a generated hue).
 */
function prepareDonut(payload) {
    const series = payload.series[0] ?? { data: [] };
    const labels = [...payload.labels];
    const data = [...series.data];

    if (labels.length > 8) {
        const rest = data.slice(7).reduce((sum, value) => sum + (value ?? 0), 0);
        labels.splice(7);
        data.splice(7);
        labels.push(document.documentElement.lang === 'ar' ? 'أخرى' : 'Other');
        data.push(rest);
    }

    return { labels, data };
}

function renderLegend(figure, payload) {
    const list = figure.querySelector('[data-chart-legend]');

    if (!list) {
        return;
    }

    const items = payload.type === 'donut'
        ? prepareDonut(payload).labels.map((label, index) => ({ label, slot: index + 1 }))
        : payload.series.map((series) => ({ label: series.label, slot: series.slot }));

    list.innerHTML = '';
    list.hidden = payload.empty || (payload.type !== 'donut' && items.length < 2);

    items.forEach((item) => {
        const li = document.createElement('li');
        li.className = 'inline-flex items-center gap-1.5';
        const swatch = document.createElement('span');
        swatch.className = 'inline-block size-2.5 shrink-0 rounded-sm';
        swatch.style.backgroundColor = seriesColor(figure, item.slot);
        swatch.setAttribute('aria-hidden', 'true');
        li.append(swatch, document.createTextNode(item.label));
        list.append(li);
    });
}

function renderTable(figure, payload) {
    const holder = figure.querySelector('[data-chart-table]');

    if (!holder) {
        return;
    }

    holder.innerHTML = '';

    if (payload.empty) {
        return;
    }

    const table = document.createElement('table');
    table.className = 'w-full text-sm';
    const head = table.createTHead().insertRow();
    const cell = (tag, text, cls) => {
        const el = document.createElement(tag);
        el.textContent = text;
        el.className = cls;
        return el;
    };

    head.append(cell('th', figure.dataset.i18nSeries ?? '', 'bg-slate-50 px-3 py-2 text-start font-medium text-slate-600'));

    if (payload.type === 'donut') {
        const { labels, data } = prepareDonut(payload);
        const total = data.reduce((sum, value) => sum + (value ?? 0), 0);
        head.append(cell('th', figure.dataset.i18nValue ?? '', 'bg-slate-50 px-3 py-2 text-end font-medium text-slate-600'), cell('th', '%', 'bg-slate-50 px-3 py-2 text-end font-medium text-slate-600'));
        const body = table.createTBody();
        labels.forEach((label, index) => {
            const row = body.insertRow();
            row.append(cell('th', label, 'px-3 py-1.5 text-start font-normal text-slate-700'), cell('td', formatValue(payload, data[index]), 'px-3 py-1.5 text-end tabular-nums'), cell('td', total ? `${new Intl.NumberFormat(locale(), { maximumFractionDigits: 1 }).format((100 * data[index]) / total)}%` : '—', 'px-3 py-1.5 text-end tabular-nums'));
        });
    } else {
        payload.series.forEach((series) => head.append(cell('th', series.label, 'bg-slate-50 px-3 py-2 text-end font-medium text-slate-600')));
        const body = table.createTBody();
        payload.labels.forEach((label, index) => {
            const row = body.insertRow();
            row.append(cell('th', formatLabel(payload, label, true), 'px-3 py-1.5 text-start font-normal text-slate-700'));
            payload.series.forEach((series) => row.append(cell('td', formatValue(payload, series.data[index]), 'px-3 py-1.5 text-end tabular-nums')));
        });
    }

    holder.append(table);
}

function altText(figure, payload) {
    const title = figure.dataset.i18nAlt ?? '';

    if (payload.empty) {
        return `${title}. ${figure.querySelector('[data-chart-empty]')?.textContent ?? ''}`;
    }

    const parts = payload.type === 'donut'
        ? (() => {
            const { labels, data } = prepareDonut(payload);
            return labels.map((label, index) => `${label}: ${formatValue(payload, data[index])}`);
        })()
        : payload.series.map((series) => {
            const values = series.data.filter((value) => value !== null);
            const total = values.reduce((sum, value) => sum + value, 0);
            const max = Math.max(...values);

            return `${series.label}: ${figure.dataset.i18nTotal ?? ''} ${formatValue({ ...payload, unit: payload.unit === 'percent' ? 'percent' : payload.unit }, payload.unit === 'percent' ? total / Math.max(1, values.length) : total)}, max ${formatValue(payload, max)}`;
        });

    return `${title}. ${parts.join('; ')}`;
}

function buildConfig(canvas, figure, payload) {
    const rtl = isRtl();
    const text = token(figure, '--viz-text-secondary', '#52514e');
    const grid = token(figure, '--viz-grid', '#e8e7e3');
    const surface = token(figure, '--viz-surface', '#fcfcfb');
    const font = { family: getComputedStyle(document.body).fontFamily, size: 12 };
    const tooltip = {
        rtl,
        textDirection: rtl ? 'rtl' : 'ltr',
        backgroundColor: token(figure, '--viz-tooltip-bg', '#1a1a19'),
        titleColor: '#f0efec',
        bodyColor: '#f0efec',
        borderColor: token(figure, '--viz-tooltip-border', '#383835'),
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        boxPadding: 4,
        usePointStyle: true,
        titleFont: font,
        bodyFont: font,
        callbacks: {
            title: (items) => (items[0] ? formatLabel(payload, items[0].label, true) : ''),
            label: (item) => ` ${item.dataset.label ?? item.label}: ${formatValue(payload, item.parsed.y ?? item.parsed)}`,
        },
    };

    if (payload.type === 'donut') {
        const { labels, data } = prepareDonut(payload);

        return {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: labels.map((_, index) => seriesColor(figure, index + 1)), borderColor: surface, borderWidth: 2, spacing: 2, hoverOffset: 4 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                animation: { duration: 250 },
                plugins: { legend: { display: false }, tooltip: { ...tooltip, callbacks: { title: () => '', label: (item) => ` ${item.label}: ${formatValue(payload, item.parsed)}` } } },
            },
        };
    }

    const isLine = payload.type === 'line';
    const datasets = payload.series.map((series) => {
        const color = seriesColor(figure, series.slot);

        return isLine
            ? { label: series.label, data: series.data, borderColor: color, backgroundColor: color, borderWidth: 2, pointRadius: payload.labels.length > 45 ? 0 : 3, pointHoverRadius: 5, pointBackgroundColor: color, pointBorderColor: surface, pointBorderWidth: 2, tension: 0, fill: false }
            : { label: series.label, data: series.data, backgroundColor: color, borderColor: surface, borderWidth: 2, borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 }, borderSkipped: 'bottom', maxBarThickness: 28 };
    });

    const crosshair = {
        id: 'crosshair',
        afterDatasetsDraw(chart) {
            const active = chart.tooltip?.getActiveElements?.() ?? [];

            if (!isLine || !active.length) {
                return;
            }

            const { ctx, chartArea } = chart;
            const x = active[0].element.x;
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, chartArea.top);
            ctx.lineTo(x, chartArea.bottom);
            ctx.lineWidth = 1;
            ctx.strokeStyle = grid;
            ctx.stroke();
            ctx.restore();
        },
    };

    return {
        type: isLine ? 'line' : 'bar',
        data: { labels: payload.labels, datasets },
        plugins: [crosshair],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 250 },
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 4 } },
            plugins: { legend: { display: false }, tooltip },
            scales: {
                x: {
                    reverse: rtl,
                    grid: { display: false },
                    border: { color: grid },
                    ticks: { color: text, font, maxRotation: 0, autoSkip: true, maxTicksLimit: 8, callback(value) { return formatLabel(payload, this.getLabelForValue(value)); } },
                },
                y: {
                    position: rtl ? 'right' : 'left',
                    beginAtZero: true,
                    grid: { color: grid, lineWidth: 1 },
                    border: { display: false },
                    ticks: { color: text, font, maxTicksLimit: 5, callback: (value) => formatValue(payload, value, { compact: true }) },
                },
            },
        },
    };
}

function render(canvas) {
    const figure = canvas.closest('[data-admin-chart]') ?? canvas.parentElement;
    let payload;

    try {
        payload = JSON.parse(canvas.dataset.chart);
    } catch {
        return;
    }

    instances.get(canvas)?.destroy();
    instances.delete(canvas);
    payload.empty = payload.empty ?? !payload.labels?.length;

    const summary = figure.querySelector('[data-chart-summary]');

    if (summary) {
        summary.textContent = payload.summary ?? '';
    }

    renderLegend(figure, payload);
    renderTable(figure, payload);
    canvas.setAttribute('aria-label', altText(figure, payload));

    const empty = figure.querySelector('[data-chart-empty]');
    empty?.classList.toggle('hidden', !payload.empty);
    empty?.classList.toggle('flex', payload.empty);
    canvas.classList.toggle('invisible', payload.empty);

    if (payload.empty || !ChartLib) {
        return;
    }

    instances.set(canvas, new ChartLib(canvas, buildConfig(canvas, figure, payload)));
}

export async function initCharts(root = document) {
    const canvases = Array.from(root.querySelectorAll('canvas[data-chart]:not([data-chart-ready])'));

    if (!canvases.length) {
        return;
    }

    const { default: Chart } = await import('chart.js/auto');
    ChartLib = Chart;

    canvases.forEach((canvas) => {
        canvas.dataset.chartReady = 'true';
        render(canvas);
    });
}

/**
 * Replaces the payload of the chart of a DataTable (or by figure id) and redraws it.
 */
export function updateChart({ tableId = null, figureId = null, chart }) {
    const figure = figureId ? document.getElementById(figureId) : document.querySelector(`[data-chart-for="${tableId}"]`);
    const canvas = figure?.querySelector('canvas[data-chart]');

    if (!canvas) {
        return;
    }

    canvas.dataset.chart = JSON.stringify(chart);
    canvas.dataset.chartReady = 'true';

    if (ChartLib) {
        render(canvas);
    } else {
        canvas.removeAttribute('data-chart-ready');
        initCharts(figure);
    }
}

document.addEventListener('admin:chart-update', (event) => updateChart(event.detail));

/**
 * Redraw all charts when the colour scheme changes (the slots are re-read from the CSS variables of the active theme).
 */
function redrawAll() {
    document.querySelectorAll('canvas[data-chart][data-chart-ready]').forEach((canvas) => render(canvas));
}

new MutationObserver(redrawAll).observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme', 'dir', 'lang'] });
window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener?.('change', redrawAll);
