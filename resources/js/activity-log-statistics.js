import {
    Chart,
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Legend,
    LinearScale,
    Tooltip,
);

const ACTION_COLORS = {
    VIEW: '#8b5cf6',
    CREATE: '#3b82f6',
    UPDATE: '#f97316',
    DELETE: '#ef4444',
    LOGIN: '#22c55e',
    LOGOUT: '#94a3b8',
};

let chartInstances = [];

function destroyCharts() {
    chartInstances.forEach((chart) => chart.destroy());
    chartInstances = [];
}

function track(chart) {
    if (chart) {
        chartInstances.push(chart);
    }
}

function palette(index) {
    const colors = ['#3b82f6', '#8b5cf6', '#f97316', '#22c55e', '#ef4444', '#06b6d4', '#ec4899', '#eab308'];
    return colors[index % colors.length];
}

export function renderActivityLogCharts(tab, stats, root) {
    destroyCharts();
    if (!stats || !root) {
        return;
    }

    if (tab === 'overview') {
        const series = stats.overview?.series || [];
        const canvas = root.querySelector('[data-chart="overview"]');
        if (canvas && series.length) {
            track(new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: series.map((item) => item.label),
                    datasets: [{
                        data: series.map((item) => item.count),
                        backgroundColor: series.map((item) => ACTION_COLORS[item.key] || palette(0)),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label(ctx) {
                                    const item = series[ctx.dataIndex];
                                    return `${item.label}: ${item.count} (${item.percent}%)`;
                                },
                            },
                        },
                    },
                },
            }));
        }

        const featureItems = (stats.overview?.mainFeatures || []).filter((item) => (item.total || 0) > 0);
        const featureCanvas = root.querySelector('[data-chart="overview-features"]');
        if (featureCanvas && featureItems.length) {
            track(new Chart(featureCanvas, {
                type: 'bar',
                data: {
                    labels: featureItems.map((item) => item.module),
                    datasets: [{
                        label: 'Tổng lượt',
                        data: featureItems.map((item) => item.total),
                        backgroundColor: featureItems.map((_, idx) => palette(idx)),
                        borderRadius: 6,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } },
                },
            }));
        }
        return;
    }

    if (tab === 'module') {
        const canvas = root.querySelector('[data-chart="module"]');
        const items = (stats.byModule?.items || stats.byMainFeature?.items || [])
            .filter((item) => item.key !== 'other' && (item.total || 0) > 0);
        if (!canvas || !items.length) {
            return;
        }
        track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: items.map((item) => item.module),
                datasets: [
                    { label: 'Xem', data: items.map((i) => i.VIEW || 0), backgroundColor: ACTION_COLORS.VIEW, stack: 'a' },
                    { label: 'Thêm', data: items.map((i) => i.CREATE || 0), backgroundColor: ACTION_COLORS.CREATE, stack: 'a' },
                    { label: 'Sửa', data: items.map((i) => i.UPDATE || 0), backgroundColor: ACTION_COLORS.UPDATE, stack: 'a' },
                    { label: 'Xóa', data: items.map((i) => i.DELETE || 0), backgroundColor: ACTION_COLORS.DELETE, stack: 'a' },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, beginAtZero: true },
                    y: { stacked: true },
                },
                plugins: { legend: { position: 'bottom' } },
            },
        }));
        return;
    }

    if (tab === 'action') {
        const canvas = root.querySelector('[data-chart="action"]');
        const items = stats.byAction?.items || [];
        if (!canvas || !items.length) {
            return;
        }
        track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: items.map((item) => item.label),
                datasets: [{
                    label: 'Số lượt',
                    data: items.map((item) => item.count),
                    backgroundColor: items.map((item) => ACTION_COLORS[item.key] || palette(0)),
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
        }));
        return;
    }

    if (tab === 'employee') {
        const canvas = root.querySelector('[data-chart="employee"]');
        const items = stats.byEmployee?.items || [];
        if (!canvas || !items.length) {
            return;
        }
        track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: items.map((item) => item.name),
                datasets: [{
                    label: 'Tổng lượt',
                    data: items.map((item) => item.total),
                    backgroundColor: items.map((_, idx) => palette(idx)),
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } },
            },
        }));
    }
}

export function destroyActivityLogCharts() {
    destroyCharts();
}

export function buildActivityLogFilterParams(advancedFilters, filters) {
    const params = new URLSearchParams();
    const adv = advancedFilters || {};
    if (adv.fromDate) {
        params.set('from', adv.fromDate);
    }
    if (adv.toDate) {
        params.set('to', adv.toDate);
    }
    if (adv.action && adv.action !== 'ALL') {
        params.set('action', adv.action);
    }
    (adv.users || []).forEach((userId) => {
        if (userId) {
            params.append('users[]', userId);
        }
    });
    Object.entries(filters || {}).forEach(([key, value]) => {
        if (value) {
            params.set(`filters[${key}]`, value);
        }
    });

    return params;
}
