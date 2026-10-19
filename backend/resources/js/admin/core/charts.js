/**
 * Chart.js (lazy-loaded) for <canvas data-chart='{"type": "line", "data": {...}, "options": {...}}'>.
 * Used by dashboard/report widgets (P5.3).
 */
export async function initCharts(root = document) {
    const canvases = root.querySelectorAll('canvas[data-chart]:not([data-chart-ready])');

    if (!canvases.length) {
        return;
    }

    const { default: Chart } = await import('chart.js/auto');

    canvases.forEach((canvas) => {
        canvas.dataset.chartReady = 'true';

        try {
            new Chart(canvas, JSON.parse(canvas.dataset.chart));
        } catch {
            canvas.replaceWith(document.createTextNode(''));
        }
    });
}
