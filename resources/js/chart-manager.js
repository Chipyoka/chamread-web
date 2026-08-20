/**
 * Centralized Chart.js manager
 * ------------------------------------------------------------
 * Blade chart components render a bare canvas:
 *
 *   <canvas data-chart-type="doughnut" data-chart-config="{{ json_encode([...]) }}"></canvas>
 *
 * No inline <script>, no hardcoded id required. This module:
 *  - assigns a guaranteed-unique id if the canvas doesn't have one
 *  - destroys any pre-existing Chart instance bound to that canvas
 *    before creating a new one (safe against Livewire/AJAX re-renders)
 *  - defers initialization until the canvas actually has non-zero
 *    width/height (fixes charts inside hidden tabs/accordions/etc.)
 *  - watches the DOM for canvases added after initial page load
 */

import Chart from 'chart.js/auto';

const instances = new Map();
let idCounter = 0;

function uid(prefix = 'chart') {
    idCounter += 1;
    return `${prefix}-${Date.now().toString(36)}-${idCounter}`;
}

function destroyIfExists(canvas) {
    const existing = Chart.getChart(canvas) || (canvas.id ? instances.get(canvas.id) : null);
    if (existing) {
        existing.destroy();
        if (canvas.id) instances.delete(canvas.id);
    }
}

function hasSize(el) {
    const rect = el.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
}

function buildChart(canvas) {
    const type = canvas.dataset.chartType;
    let config = {};

    try {
        config = JSON.parse(canvas.dataset.chartConfig || '{}');
    } catch (e) {
        console.error(`[chart-manager] Invalid data-chart-config JSON on canvas`, canvas, e);
        return;
    }

    if (!canvas.id) {
        canvas.id = uid(type || 'chart');
    }

    // Optional simple tooltip formatter without needing inline JS:
    // <canvas data-chart-tooltip-label="Readings" ...> renders "Readings: <value>"
    const tooltipLabel = canvas.dataset.chartTooltipLabel;
    if (tooltipLabel) {
        config.options = config.options || {};
        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = config.options.plugins.tooltip || {};
        config.options.plugins.tooltip.enabled = config.options.plugins.tooltip.enabled ?? true;
        config.options.plugins.tooltip.callbacks = config.options.plugins.tooltip.callbacks || {};
        if (!config.options.plugins.tooltip.callbacks.label) {
            config.options.plugins.tooltip.callbacks.label = (context) => `${tooltipLabel}: ${context.raw}`;
        }
    }

    destroyIfExists(canvas);

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error(`[chart-manager] Could not get 2D context for canvas #${canvas.id}`);
        return;
    }

    const chart = new Chart(ctx, { type, ...config });

    canvas.dataset.chartInitialized = 'true';
    instances.set(canvas.id, chart);
}

function initChart(canvas) {
    if (!canvas || !(canvas instanceof HTMLCanvasElement)) return;
    if (canvas.dataset.chartObserving === 'true') return; // already queued

    if (hasSize(canvas)) {
        buildChart(canvas);
        return;
    }

    // Canvas has zero size right now (hidden tab, collapsed panel, etc.)
    // Wait until it becomes visible/sized before drawing.
    canvas.dataset.chartObserving = 'true';
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting && hasSize(canvas)) {
                obs.disconnect();
                canvas.dataset.chartObserving = 'false';
                buildChart(canvas);
            }
        });
    });
    observer.observe(canvas);
}

/**
 * Initialize every chart canvas currently in `root`.
 * Safe to call multiple times — already-initialized canvases are skipped
 * unless their config has changed (see refreshChart below).
 */
export function initAllCharts(root = document) {
    root.querySelectorAll('canvas[data-chart-type]').forEach((canvas) => {
        if (canvas.dataset.chartInitialized === 'true') return;
        initChart(canvas);
    });
}

/** Force re-render a specific canvas (e.g. after live data update). */
export function refreshChart(canvas) {
    if (typeof canvas === 'string') canvas = document.getElementById(canvas);
    if (!canvas) return;
    canvas.dataset.chartInitialized = 'false';
    initChart(canvas);
}

export function getChartInstance(id) {
    return instances.get(id);
}

/** Watch for canvases added after initial load (Livewire, AJAX, Alpine x-if, etc.) */
export function watchForCharts(root = document.body) {
    const mo = new MutationObserver((mutations) => {
        mutations.forEach((m) => {
            m.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) return;
                if (node.matches?.('canvas[data-chart-type]')) initChart(node);
                node.querySelectorAll?.('canvas[data-chart-type]').forEach((c) => initChart(c));
            });
        });
    });
    mo.observe(root, { childList: true, subtree: true });
    return mo;
}