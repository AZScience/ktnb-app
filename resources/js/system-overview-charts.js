import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Legend,
    Tooltip,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Legend, Tooltip);

const GRADIENT_PAIRS = {
    '#e11d48': ['#fb7185', '#e11d48'],
    '#ea580c': ['#fdba74', '#ea580c'],
    '#db2777': ['#f9a8d4', '#db2777'],
    '#dc2626': ['#fca5a5', '#dc2626'],
};

function makeGradient(ctx, chartArea, baseColor) {
    const [from, to] = GRADIENT_PAIRS[baseColor] || [baseColor, baseColor];
    const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
    gradient.addColorStop(0, from);
    gradient.addColorStop(1, to);
    return gradient;
}

function hasChartData(chartConfig) {
    if (!chartConfig?.datasets?.length) {
        return false;
    }

    return chartConfig.datasets.some((dataset) => dataset.data?.some((value) => value > 0));
}

function buildStackedBarChart(canvas, chartConfig) {
    if (!canvas || !hasChartData(chartConfig)) {
        return null;
    }

    const sourceDatasets = chartConfig.datasets;

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: chartConfig.labels,
            datasets: sourceDatasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.data,
                backgroundColor: dataset.color,
                borderRadius: { topRight: 6, bottomRight: 6 },
                borderSkipped: false,
                maxBarThickness: 28,
            })),
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        font: { size: 11, weight: '600' },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleFont: { size: 12, weight: '700' },
                    bodyFont: { size: 11 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        footer(items) {
                            const total = items.reduce((sum, item) => sum + (item.parsed.x || 0), 0);
                            return total > 0 ? `Tổng: ${total}` : '';
                        },
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: { precision: 0, font: { size: 11 } },
                    border: { display: false },
                },
                y: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#475569',
                    },
                    border: { display: false },
                },
            },
            animation: {
                duration: 900,
                easing: 'easeOutQuart',
            },
        },
        plugins: [
            {
                id: 'systemOverviewGradients',
                beforeDatasetsDraw(chartInstance) {
                    const { ctx, chartArea } = chartInstance;
                    if (!chartArea) {
                        return;
                    }

                    chartInstance.data.datasets.forEach((dataset, index) => {
                        const baseColor = sourceDatasets[index]?.color;
                        dataset.backgroundColor = makeGradient(ctx, chartArea, baseColor);
                    });
                },
            },
        ],
    });
}

export function initSystemOverviewCharts() {
    const root = document.getElementById('system-overview-charts');
    if (!root) {
        return;
    }

    let payload;
    try {
        payload = JSON.parse(root.dataset.charts || '{}');
    } catch (_) {
        return;
    }

    buildStackedBarChart(root.querySelector('[data-chart="department"]'), payload.byDepartment);
    buildStackedBarChart(root.querySelector('[data-chart="building"]'), payload.byBuilding);

    root.querySelectorAll('[data-empty-chart]').forEach((emptyState) => {
        const key = emptyState.dataset.emptyChart;
        const config = payload[key];
        if (!hasChartData(config)) {
            emptyState.classList.remove('hidden');
        }
    });
}

document.addEventListener('DOMContentLoaded', initSystemOverviewCharts);
