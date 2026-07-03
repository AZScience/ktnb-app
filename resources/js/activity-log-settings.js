import { columnKeyIconPaths } from './nttu-icons.js';
import { hasTranslation, t } from './language.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice, ROWS_PER_PAGE_OPTIONS } from './nttu-pagination.js';
import {
    buildActivityLogFilterParams,
    destroyActivityLogCharts,
    renderActivityLogCharts,
} from './activity-log-statistics.js';

const ACTION_MAP = {
    LOGIN: { label: 'Đăng nhập', color: 'bg-green-100 text-green-800 border-green-300' },
    LOGOUT: { label: 'Đăng xuất', color: 'bg-gray-100 text-gray-800 border-gray-300' },
    CREATE: { label: 'Thêm mới', color: 'bg-blue-100 text-blue-800 border-blue-300' },
    UPDATE: { label: 'Cập nhật', color: 'bg-orange-100 text-orange-800 border-orange-300' },
    DELETE: { label: 'Xóa', color: 'bg-red-100 text-red-800 border-red-300' },
    VIEW: { label: 'Xem', color: 'bg-purple-100 text-purple-800 border-purple-300' },
};

const COLUMN_HEADER_ICONS = {
    formattedTime: columnKeyIconPaths.date,
    userName: columnKeyIconPaths.name,
    userEmail: columnKeyIconPaths.email,
    action: 'M13 10V3L4 14h7v7l9-11h-7z',
    module: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
    details: columnKeyIconPaths.content,
    ipAddress: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

const ROWS_PER_PAGE_OPTIONS_EXTENDED = [...ROWS_PER_PAGE_OPTIONS, 100];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function loadJson(key, fallback) {
    try {
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch {
        return fallback;
    }
}

function saveJson(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function formatJson(data) {
    if (!data) return 'Không có dữ liệu';
    if (typeof data === 'string') return data;
    try {
        return JSON.stringify(data, null, 2);
    } catch {
        return String(data);
    }
}

export function registerActivityLogSettings(Alpine) {
    Alpine.data('activityLogSettingsPage', (config) => ({
        logs: config.logs || [],
        userOptions: config.userOptions || [],
        routes: config.routes || {},
        canDelete: config.canDelete !== false,
        canExport: config.canExport !== false,
        toast: null,
        deleting: false,

        currentPage: normalizeCurrentPage(loadJson('nttu_accesslog_page_v1', 1), 1),
        rowsPerPage: normalizeRowsPerPage(loadJson('nttu_accesslog_rows_v1', 20), 20),
        sortConfig: loadJson('nttu_accesslog_sort_v1', []),
        columnVisibility: loadJson('nttu_accesslog_colvis_v1', {
            formattedTime: true,
            userName: true,
            userEmail: true,
            action: true,
            module: true,
            details: true,
            ipAddress: true,
        }),
        filters: loadJson('nttu_accesslog_filters_v1', {}),
        advancedFilters: loadJson('nttu_accesslog_adv_v1', {
            fromDate: '',
            toDate: '',
            users: [],
            action: 'ALL',
        }),
        selectedIds: loadJson('nttu_accesslog_selected_v1', []),

        headerPopover: null,
        settingsOpen: false,
        rowMenuOpen: null,
        advancedOpen: false,
        viewOpen: false,
        deleteOpen: false,
        bulkDeleteOpen: false,
        selectedLog: null,
        logToDelete: null,
        userPickerOpen: false,
        userSearch: '',

        rowsPerPageOptions: ROWS_PER_PAGE_OPTIONS_EXTENDED,

        activeMainTab: 'history',
        statsData: null,
        statsLoading: false,
        statsError: null,

        actionMap: ACTION_MAP,

        init() {
            this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 20);
            this.currentPage = normalizeCurrentPage(this.currentPage, this.totalPages);
            this.$watch('currentPage', (v) => localStorage.setItem('nttu_accesslog_page_v1', String(v)));
            this.$watch('selectedIds', (v) => saveJson('nttu_accesslog_selected_v1', v));
            this.$watch('activeMainTab', (tab) => {
                if (tab !== 'history') {
                    void this.fetchStatistics();
                } else {
                    destroyActivityLogCharts();
                }
            });
            this.$watch('advancedFilters', () => {
                if (this.activeMainTab !== 'history') {
                    void this.fetchStatistics();
                }
            }, { deep: true });
            this.$watch('filters', () => {
                if (this.activeMainTab !== 'history') {
                    void this.fetchStatistics();
                }
            }, { deep: true });
        },

        get visibleColumnKeys() {
            return Object.keys(this.columns).filter((key) => this.columnVisibility[key]);
        },

        get selectedSet() {
            return new Set(this.selectedIds);
        },

        columnHeaderIconPath(key) {
            return COLUMN_HEADER_ICONS[key] || '';
        },

        text(key) {
            return t(key);
        },

        statsCell(value) {
            const num = Number(value);
            if (!Number.isFinite(num) || num === 0) {
                return '';
            }

            return num.toLocaleString('vi-VN');
        },

        statsPercent(value) {
            const num = Number(value);
            if (!Number.isFinite(num) || num === 0) {
                return '';
            }

            return `${num}%`;
        },

        get mainTabs() {
            return [
                { id: 'history', label: t('Lịch sử hoạt động') },
                { id: 'overview', label: t('Thống kê tổng quan') },
                { id: 'module', label: t('Theo chức năng chính') },
                { id: 'action', label: t('Theo hành động') },
                { id: 'employee', label: t('Theo nhân viên') },
            ];
        },

        get columns() {
            return {
                formattedTime: { label: t('Thời gian') },
                userName: { label: t('Người dùng') },
                userEmail: { label: t('Email') },
                action: { label: t('Hành động') },
                module: { label: t('Chức năng chính') },
                details: { label: t('Chi tiết') },
                ipAddress: { label: t('Địa chỉ IP') },
            };
        },

        actionBadge(action, actionLabel = '', actionRaw = '') {
            const mapped = ACTION_MAP[action];
            const display = (actionLabel || '').trim() || (actionRaw || '').trim();

            if (display && (!mapped || display !== t(mapped.label))) {
                const label = hasTranslation(display) ? t(display) : display;

                return {
                    label,
                    color: mapped?.color || 'bg-slate-100 text-slate-800 border-slate-300',
                };
            }

            if (mapped) {
                return {
                    ...mapped,
                    label: t(mapped.label),
                };
            }

            const fallback = (actionRaw || action || '').trim();
            const label = fallback && hasTranslation(fallback) ? t(fallback) : fallback;

            return {
                label: label || '—',
                color: 'bg-slate-100 text-slate-800 border-slate-300',
            };
        },

        sortStateFor(key) {
            return this.sortConfig.find((entry) => entry.key === key) || null;
        },

        sortIconClass(key) {
            return this.isFiltered(key) ? 'nttu-sort-filtered' : '';
        },

        isFiltered(key) {
            return !!this.filters[key];
        },

        clearColumnFilter(key) {
            this.setFilter(key, '');
            this.headerPopover = null;
        },

        toggleColumn(key) {
            this.columnVisibility = { ...this.columnVisibility, [key]: !this.columnVisibility[key] };
            saveJson('nttu_accesslog_colvis_v1', this.columnVisibility);
        },

        selectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), true);
            saveJson('nttu_accesslog_colvis_v1', this.columnVisibility);
        },

        deselectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), false);
            saveJson('nttu_accesslog_colvis_v1', this.columnVisibility);
        },

        get hasActiveFilters() {
            const adv = this.advancedFilters;
            return Object.values(this.filters).some((v) => !!v)
                || !!adv.fromDate
                || !!adv.toDate
                || (adv.users?.length ?? 0) > 0
                || (adv.action && adv.action !== 'ALL');
        },

        clearFilters() {
            this.filters = {};
            this.advancedFilters = { fromDate: '', toDate: '', users: [], action: 'ALL' };
            saveJson('nttu_accesslog_filters_v1', this.filters);
            saveJson('nttu_accesslog_adv_v1', this.advancedFilters);
            this.currentPage = 1;
        },

        switchMainTab(tabId) {
            this.activeMainTab = tabId;
        },

        statsSection() {
            if (!this.statsData) {
                return null;
            }
            if (this.activeMainTab === 'overview') {
                return this.statsData.overview;
            }
            if (this.activeMainTab === 'module') {
                return this.statsData.byModule;
            }
            if (this.activeMainTab === 'action') {
                return this.statsData.byAction;
            }
            if (this.activeMainTab === 'employee') {
                return this.statsData.byEmployee;
            }
            return null;
        },

        async fetchStatistics() {
            if (!this.routes.statistics) {
                this.statsData = this.buildLocalStatistics();
                this.$nextTick(() => this.renderStatsCharts());
                return;
            }

            this.statsLoading = true;
            this.statsError = null;
            try {
                const params = buildActivityLogFilterParams(this.advancedFilters, this.filters);
                const qs = params.toString();
                const url = qs ? `${this.routes.statistics}?${qs}` : this.routes.statistics;
                const res = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const body = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(body.message || 'Không tải được thống kê');
                }
                this.statsData = body;
                this.$nextTick(() => this.renderStatsCharts());
            } catch (err) {
                this.statsError = err.message || 'Không tải được thống kê';
                this.statsData = null;
                destroyActivityLogCharts();
            } finally {
                this.statsLoading = false;
            }
        },

        buildLocalStatistics() {
            const items = this.filteredItems;
            const actionCounts = {};
            items.forEach((item) => {
                const key = item.action || 'VIEW';
                actionCounts[key] = (actionCounts[key] || 0) + 1;
            });
            const total = items.length;
            const core = ['VIEW', 'CREATE', 'UPDATE', 'DELETE'];
            return {
                total,
                overview: {
                    series: core.map((key) => ({
                        key,
                        label: (ACTION_MAP[key] || { label: key }).label,
                        count: actionCounts[key] || 0,
                        percent: total > 0 ? Math.round((actionCounts[key] || 0) / total * 1000) / 10 : 0,
                    })),
                    commentary: total
                        ? [
                            `Thống kê từ ${total} bản ghi đang hiển thị trên trang (phạm vi tải trang, tối đa 250 bản ghi).`,
                            'Để có nhận xét và kiến nghị đầy đủ theo toàn bộ dữ liệu, hãy dùng tab thống kê khi máy chủ tải xong (tối đa 10.000 bản ghi theo bộ lọc).',
                        ]
                        : ['Chưa có dữ liệu trong phạm vi hiển thị.'],
                    recommendations: total
                        ? ['Áp dụng bộ lọc phù hợp và chờ tải thống kê từ máy chủ để xem phân tích chi tiết theo chức năng chính.']
                        : [],
                },
                byModule: { items: [], commentary: [], recommendations: [] },
                byAction: { items: [], commentary: [], recommendations: [] },
                byEmployee: { items: [], commentary: [], recommendations: [] },
            };
        },

        renderStatsCharts() {
            const root = this.$refs.statsPanel;
            if (!root || this.activeMainTab === 'history') {
                return;
            }
            this.$nextTick(() => {
                this.$nextTick(() => {
                    renderActivityLogCharts(this.activeMainTab, this.statsData, root);
                });
            });
        },

        get filteredItems() {
            return this.logs.filter((item) => {
                const matchesColumns = Object.entries(this.filters).every(([key, value]) => {
                    if (!value) return true;
                    return String(item[key] ?? '').toLowerCase().includes(String(value).toLowerCase());
                });
                if (!matchesColumns) return false;

                const adv = this.advancedFilters;
                if (adv.action !== 'ALL' && item.action !== adv.action) return false;

                if (adv.users?.length > 0) {
                    const matchUser = adv.users.some((filterId) =>
                        item.userId === filterId
                        || item.employeeRefId === filterId
                        || (item.userEmail && item.userEmail.toLowerCase() === String(filterId).toLowerCase()),
                    );
                    if (!matchUser) return false;
                }

                if (adv.fromDate && item.isoDate < adv.fromDate) return false;
                if (adv.toDate && item.isoDate > adv.toDate) return false;

                return true;
            });
        },

        get sortedItems() {
            const items = [...this.filteredItems];
            if (this.sortConfig.length === 0) return items;

            const { key, direction } = this.sortConfig[0];
            items.sort((a, b) => {
                const aVal = a[key];
                const bVal = b[key];
                if (aVal === null || aVal === undefined) return 1;
                if (bVal === null || bVal === undefined) return -1;
                if (String(aVal) < String(bVal)) return direction === 'ascending' ? -1 : 1;
                if (String(aVal) > String(bVal)) return direction === 'ascending' ? 1 : -1;
                return 0;
            });
            return items;
        },

        get normalizedRowsPerPage() {
            return normalizeRowsPerPage(this.rowsPerPage, 20);
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.sortedItems.length / this.normalizedRowsPerPage));
        },

        get safePage() {
            return normalizeCurrentPage(this.currentPage, this.totalPages);
        },

        get startIndex() {
            return paginateSlice(this.sortedItems, this.currentPage, this.normalizedRowsPerPage).startIndex;
        },

        get currentItems() {
            return paginateSlice(this.sortedItems, this.currentPage, this.normalizedRowsPerPage).items;
        },

        get filteredUserOptions() {
            const q = (this.userSearch || '').toLowerCase();
            if (!q) return this.userOptions;
            return this.userOptions.filter((u) =>
                String(u.name).toLowerCase().includes(q) || String(u.email).toLowerCase().includes(q),
            );
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => {
                if (this.toast?.message === message) this.toast = null;
            }, 2800);
        },

        setFilter(key, value) {
            this.filters = { ...this.filters, [key]: value };
            this.currentPage = 1;
            saveJson('nttu_accesslog_filters_v1', this.filters);
        },

        requestSort(key, direction) {
            this.sortConfig = [{ key, direction }];
            saveJson('nttu_accesslog_sort_v1', this.sortConfig);
            this.headerPopover = null;
        },

        clearSort() {
            this.sortConfig = [];
            saveJson('nttu_accesslog_sort_v1', []);
            this.headerPopover = null;
        },

        goPage(page) {
            this.currentPage = normalizeCurrentPage(page, this.totalPages);
        },

        setRowsPerPage(value) {
            this.rowsPerPage = normalizeRowsPerPage(value, 20);
            this.currentPage = 1;
            saveJson('nttu_accesslog_rows_v1', this.rowsPerPage);
            localStorage.setItem('nttu_accesslog_page_v1', '1');
        },

        toggleRow(id) {
            const set = new Set(this.selectedIds);
            if (set.has(id)) set.delete(id);
            else set.add(id);
            this.selectedIds = Array.from(set);
        },

        isSelected(id) {
            return this.selectedSet.has(id);
        },

        toggleAdvancedUser(userId) {
            const current = this.advancedFilters.users || [];
            const next = current.includes(userId)
                ? current.filter((id) => id !== userId)
                : [...current, userId];
            this.advancedFilters = { ...this.advancedFilters, users: next };
            saveJson('nttu_accesslog_adv_v1', this.advancedFilters);
            this.currentPage = 1;
        },

        clearAdvancedFilters() {
            this.advancedFilters = { fromDate: '', toDate: '', users: [], action: 'ALL' };
            saveJson('nttu_accesslog_adv_v1', this.advancedFilters);
            this.currentPage = 1;
        },

        applyAdvancedFilters() {
            saveJson('nttu_accesslog_adv_v1', this.advancedFilters);
            this.currentPage = 1;
            this.advancedOpen = false;
            if (this.activeMainTab !== 'history') {
                void this.fetchStatistics();
            }
        },

        setAdvancedDate(field, value) {
            this.advancedFilters = { ...this.advancedFilters, [field]: value };
        },

        setAdvancedAction(value) {
            this.advancedFilters = { ...this.advancedFilters, action: value };
        },

        openView(item) {
            this.selectedLog = item;
            this.rowMenuOpen = null;
            this.viewOpen = true;
        },

        confirmDelete(item) {
            if (!this.canDelete) return;
            this.logToDelete = item;
            this.rowMenuOpen = null;
            this.deleteOpen = true;
        },

        async deleteLog() {
            if (!this.logToDelete || !this.routes.destroyUrl) return;
            this.deleting = true;
            try {
                const res = await fetch(this.routes.destroyUrl.replace('__ID__', this.logToDelete.id), {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(body.message || 'Xóa thất bại');

                this.logs = this.logs.filter((log) => log.id !== this.logToDelete.id);
                this.selectedIds = this.selectedIds.filter((id) => id !== this.logToDelete.id);
                this.deleteOpen = false;
                this.logToDelete = null;
                this.showToast(body.message || 'Đã xóa nhật ký truy cập.');
            } catch (err) {
                this.showToast(err.message || 'Không thể xóa nhật ký.', 'error');
            } finally {
                this.deleting = false;
            }
        },

        async bulkDelete() {
            if (this.selectedIds.length === 0 || !this.routes.bulkDestroyUrl) return;
            this.deleting = true;
            try {
                const res = await fetch(this.routes.bulkDestroyUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ ids: this.selectedIds }),
                });
                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(body.message || 'Xóa thất bại');

                const removed = new Set(this.selectedIds);
                this.logs = this.logs.filter((log) => !removed.has(log.id));
                this.selectedIds = [];
                this.bulkDeleteOpen = false;
                this.showToast(body.message || 'Đã xóa các nhật ký đã chọn.');
            } catch (err) {
                this.showToast(err.message || 'Không thể xóa các nhật ký đã chọn.', 'error');
            } finally {
                this.deleting = false;
            }
        },

        async exportExcel() {
            if (!this.canExport) return;
            if (this.routes.export) {
                const params = new URLSearchParams();
                const adv = this.advancedFilters;
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
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) {
                        params.set(`filters[${key}]`, value);
                    }
                });
                const qs = params.toString();
                window.location.href = qs ? `${this.routes.export}?${qs}` : this.routes.export;
                return;
            }

            const XLSX = await import('xlsx');
            const rows = this.sortedItems.map((log, index) => ({
                STT: index + 1,
                'Thời gian': log.formattedTime,
                'Người dùng': log.userName,
                Email: log.userEmail,
                'Hành động': this.actionBadge(log.action, log.actionLabel, log.actionRaw).label,
                'Chức năng': log.module,
                'Chi tiết': log.details,
                IP: log.ipAddress || '',
            }));
            const ws = XLSX.utils.json_to_sheet(rows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'NhatKyTruyCap');
            const stamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            XLSX.writeFile(wb, `DS_NhatKy_${stamp}.xlsx`);
        },

        userLabel(userId) {
            const user = this.userOptions.find((u) => u.id === userId);
            return user?.name || userId;
        },

        formatJson,
    }));
}
