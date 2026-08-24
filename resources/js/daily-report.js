import { crosstabHeaderIconClassForKey, headerIconClassForKey, headerIconPathForColumn } from './nttu-icons.js';
import { TABLE_EXPORT_EMPTY } from './nttu-table-messages.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeRowsPerPage } from './nttu-pagination.js';
import { incidentBadgeClass } from './nttu-incident-colors.js';

const DAILY_REPORT_TAB_PALETTE = {
    active: 'bg-red-600 text-white border-red-600 shadow-md',
    inactive: 'bg-green-600 text-white border-green-600 hover:bg-green-700',
    badgeActive: 'bg-white/25 text-white',
    badgeInactive: 'bg-white/20 text-white',
};

function slugify(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[đĐ]/g, 'd')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
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

function parseNotificationValue(value) {
    if (typeof value === 'boolean') {
        return value;
    }

    const normalized = String(value ?? '').trim().toUpperCase();
    if (['TRUE', '1', 'YES', 'CÓ', 'CO', 'X', 'ON'].includes(normalized)) {
        return true;
    }

    return false;
}

function parseFilenameFromDisposition(header) {
    if (!header) return null;
    const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
        return decodeURIComponent(utfMatch[1]);
    }
    const match = header.match(/filename="?([^";]+)"?/i);
    return match?.[1] || null;
}

function defaultTableState(columns) {
    const columnVisibility = {};
    columns.forEach((col) => {
        columnVisibility[col.key] = true;
    });

    return {
        sortConfig: [],
        filters: {},
        currentPage: 1,
        rowsPerPage: 15,
        columnVisibility,
        openPopover: null,
        colMenuOpen: false,
        selectedIds: [],
    };
}

function normalizeTableState(state, columns) {
    const base = defaultTableState(columns);

    return {
        ...base,
        ...state,
        currentPage: Math.max(1, parseInt(state.currentPage, 10) || 1),
        rowsPerPage: normalizeRowsPerPage(state.rowsPerPage, base.rowsPerPage),
        openPopover: null,
        colMenuOpen: false,
    };
}

export function registerDailyReport(Alpine) {
    Alpine.data('dailyReportPage', (config) => ({
            text(key) { return window.t ? window.t(key) : key; },
        tab: 'in-person',
        dateIso: config.dateIso,
        displayDate: config.displayDate,
        officer: config.officer,
        tabDefinitions: config.tabDefinitions || {},
        datasets: config.datasets || {},
        exportUrl: config.exportUrl || '',
        googleSheetsConfigured: config.googleSheetsConfigured || false,
        defaultSheetTab: config.defaultSheetTab || 'Báo cáo Tổng hợp',
        googleSheetTabsUrl: config.googleSheetTabsUrl || '',
        googleSheetPushUrl: config.googleSheetPushUrl || '',
        canExport: config.canExport !== false,
        canEdit: config.canEdit !== false,
        isExportingAll: false,
        pushDialogOpen: false,
        pushTabKey: '',
        availableTabs: [],
        targetTabName: '',
        isLoadingTabs: false,
        isPushing: false,
        toast: null,
        tableStates: {},

        init() {
            Object.entries(this.tabDefinitions).forEach(([key, def]) => {
                const storageKey = `daily_report_${slugify(def.title)}`;
                const saved = loadJson(`${storageKey}_state`, null);
                this.tableStates[key] = saved
                    ? normalizeTableState(saved, def.columns)
                    : defaultTableState(def.columns);
            });

            const savedTab = loadJson('daily_report_active_tab_v1', null);
            if (savedTab && this.tabList().includes(savedTab)) {
                this.tab = savedTab;
            }
            this.$watch('tab', (value) => {
                if (this.tabList().includes(value)) {
                    saveJson('daily_report_active_tab_v1', value);
                }
            });
        },

        tabList() {
            return ['in-person', 'online', 'homeroom', 'violations', 'exams', 'external'];
        },

        tabCount(key) {
            return (this.datasets[key] || []).length;
        },

        tabToneClass(key) {
            return this.tab === key
                ? DAILY_REPORT_TAB_PALETTE.active
                : DAILY_REPORT_TAB_PALETTE.inactive;
        },

        tabBadgeClass(key) {
            return this.tab === key
                ? DAILY_REPORT_TAB_PALETTE.badgeActive
                : DAILY_REPORT_TAB_PALETTE.badgeInactive;
        },

        tabPanelHeaderClass(tabKey) {
            return this.tab === tabKey
                ? 'bg-red-50/90 border-red-100'
                : 'bg-green-50/90 border-green-100';
        },

        tabPanelTitleClass(tabKey) {
            return this.tab === tabKey ? 'text-red-700' : 'text-green-800';
        },

        isCrosstabTab(tabKey) {
            return this.tabDefinitions[tabKey]?.layout === 'crosstab';
        },

        crosstabConfig(tabKey) {
            return this.tabDefinitions[tabKey]?.crosstab || {
                fixedColumns: [],
                violationGroupLabel: '',
                violationColumns: [],
            };
        },

        crosstabColumnCount(tabKey) {
            return 1
                + this.visibleCrosstabFixedColumns(tabKey).length
                + this.visibleCrosstabViolationColumns(tabKey).length
                + 1;
        },

        visibleCrosstabFixedColumns(tabKey) {
            const state = this.stateFor(tabKey);

            return this.crosstabConfig(tabKey).fixedColumns.filter(
                (col) => state.columnVisibility[col.key] !== false,
            );
        },

        visibleCrosstabViolationColumns(tabKey) {
            const state = this.stateFor(tabKey);

            return this.crosstabConfig(tabKey).violationColumns.filter(
                (col) => state.columnVisibility[col.key] !== false,
            );
        },

        crosstabCount(item, key) {
            const value = Number(item?.[key] ?? 0);
            return Number.isFinite(value) && value > 0 ? String(value) : '';
        },

        stateFor(tabKey) {
            if (!this.tableStates[tabKey]) {
                this.tableStates[tabKey] = defaultTableState(this.tabDefinitions[tabKey]?.columns || []);
            }
            return this.tableStates[tabKey];
        },

        rowsPerPageFor(tabKey) {
            return normalizeRowsPerPage(this.stateFor(tabKey).rowsPerPage);
        },

        persistState(tabKey) {
            const def = this.tabDefinitions[tabKey];
            if (!def) return;
            const state = this.stateFor(tabKey);
            saveJson(`daily_report_${slugify(def.title)}_state`, {
                sortConfig: state.sortConfig,
                filters: state.filters,
                currentPage: state.currentPage,
                rowsPerPage: state.rowsPerPage,
                columnVisibility: state.columnVisibility,
            });
        },

        visibleColumns(tabKey) {
            const def = this.tabDefinitions[tabKey];
            const state = this.stateFor(tabKey);
            return (def?.columns || []).filter((col) => state.columnVisibility[col.key] !== false);
        },

        columnHeaderIconPath(col) {
            return headerIconPathForColumn(col.key, col);
        },

        columnHeaderIconClass(col) {
            return headerIconClassForKey(col.key, col);
        },

        crosstabColumnHeaderIconClass(col) {
            return crosstabHeaderIconClassForKey(col.key, col);
        },

        sortStateFor(tabKey, colKey) {
            const state = this.stateFor(tabKey);
            return state.sortConfig.find((entry) => entry.key === colKey) || null;
        },

        isFiltered(tabKey, colKey) {
            return !!this.stateFor(tabKey).filters[colKey];
        },

        sortIconClass(tabKey, colKey) {
            return this.isFiltered(tabKey, colKey) ? 'nttu-sort-filtered' : '';
        },

        processedItems(tabKey) {
            let items = [...(this.datasets[tabKey] || [])];
            const state = this.stateFor(tabKey);
            const columns = this.tabDefinitions[tabKey]?.columns || [];

            if (state.sortConfig.length > 0) {
                const { key, direction } = state.sortConfig[0];
                const colDef = columns.find((col) => col.key === key);
                items.sort((a, b) => {
                    if (colDef?.type === 'count') {
                        const cmp = (Number(a[key]) || 0) - (Number(b[key]) || 0);
                        return direction === 'ascending' ? cmp : -cmp;
                    }

                    const av = String(a[key] ?? '');
                    const bv = String(b[key] ?? '');
                    const cmp = av.localeCompare(bv, 'vi', { numeric: true });
                    return direction === 'ascending' ? cmp : -cmp;
                });
            }

            Object.entries(state.filters).forEach(([key, value]) => {
                if (!value) return;
                const needle = String(value).toLowerCase();
                const colDef = columns.find((col) => col.key === key);
                items = items.filter((item) => {
                    const raw = item[key] ?? '';
                    if (colDef?.type === 'count') {
                        const display = Number(raw) > 0 ? String(raw) : '';
                        return display.toLowerCase().includes(needle);
                    }

                    return String(raw).toLowerCase().includes(needle);
                });
            });

            return items;
        },

        totalPages(tabKey) {
            const state = this.stateFor(tabKey);
            const rowsPerPage = this.rowsPerPageFor(tabKey);
            return Math.max(1, Math.ceil(this.processedItems(tabKey).length / rowsPerPage));
        },

        currentItems(tabKey) {
            const state = this.stateFor(tabKey);
            const rowsPerPage = this.rowsPerPageFor(tabKey);
            const start = (state.currentPage - 1) * rowsPerPage;
            return this.processedItems(tabKey).slice(start, start + rowsPerPage);
        },

        setFilter(tabKey, key, value) {
            const state = this.stateFor(tabKey);
            state.filters = { ...state.filters, [key]: value };
            state.currentPage = 1;
            this.persistState(tabKey);
        },

        clearFilter(tabKey, key) {
            this.setFilter(tabKey, key, '');
            this.stateFor(tabKey).openPopover = null;
        },

        hasActiveFilters(tabKey) {
            const state = this.stateFor(tabKey);

            return Object.keys(state.filters).some((key) => state.filters[key]);
        },

        clearAllFilters(tabKey) {
            const state = this.stateFor(tabKey);
            state.filters = {};
            state.sortConfig = [];
            state.currentPage = 1;
            state.openPopover = null;
            this.persistState(tabKey);
        },

        requestSort(tabKey, key, direction) {
            const state = this.stateFor(tabKey);
            state.sortConfig = [{ key, direction }];
            state.openPopover = null;
            this.persistState(tabKey);
        },

        clearSort(tabKey) {
            const state = this.stateFor(tabKey);
            state.sortConfig = [];
            state.openPopover = null;
            this.persistState(tabKey);
        },

        toggleColumn(tabKey, key) {
            const state = this.stateFor(tabKey);
            state.columnVisibility = {
                ...state.columnVisibility,
                [key]: state.columnVisibility[key] === false,
            };
            this.persistState(tabKey);
        },

        selectAllColumns(tabKey) {
            const def = this.tabDefinitions[tabKey];
            const state = this.stateFor(tabKey);
            state.columnVisibility = allColumnsVisibleMap(
                (def?.columns || []).map((col) => col.key),
                true,
            );
            this.persistState(tabKey);
        },

        deselectAllColumns(tabKey) {
            const def = this.tabDefinitions[tabKey];
            const state = this.stateFor(tabKey);
            state.columnVisibility = allColumnsVisibleMap(
                (def?.columns || []).map((col) => col.key),
                false,
            );
            this.persistState(tabKey);
        },

        goPage(tabKey, page) {
            const state = this.stateFor(tabKey);
            const next = Math.min(Math.max(1, parseInt(page, 10) || 1), this.totalPages(tabKey));
            state.currentPage = next;
            this.persistState(tabKey);
        },

        setRowsPerPage(tabKey, value) {
            const state = this.stateFor(tabKey);
            state.rowsPerPage = normalizeRowsPerPage(value);
            state.currentPage = 1;
            this.persistState(tabKey);
        },

        toggleRow(tabKey, id) {
            const state = this.stateFor(tabKey);
            const set = new Set(state.selectedIds);
            if (set.has(id)) set.delete(id);
            else set.add(id);
            state.selectedIds = [...set];
        },

        isSelected(tabKey, id) {
            return this.stateFor(tabKey).selectedIds.includes(id);
        },

        cellTdClass(col) {
            if (col.type === 'notification' || col.type === 'incident') {
                return 'text-center align-middle';
            }
            if (col.key === 'student_count' || col.key === 'studentCount'
                || col.key === 'attending_students' || col.key === 'attendingStudents') {
                return 'text-center align-middle';
            }

            return 'text-left align-middle';
        },

        cellValue(item, col) {
            const val = item[col.key];
            if (col.type === 'incident') {
                if (!val) return { kind: 'normal' };
                return { kind: 'badge', text: val, class: incidentBadgeClass(val) };
            }
            if (col.type === 'notification') {
                return { kind: 'checkbox', checked: parseNotificationValue(val) };
            }
            if (col.type === 'type') {
                return { kind: 'badge', text: val || 'TH', class: 'border border-blue-200 text-blue-700 bg-blue-50' };
            }
            if (col.type === 'class') {
                return { kind: 'class', text: val || '---' };
            }
            if (col.type === 'count') {
                const count = Number(val ?? 0);
                return { kind: 'text', text: Number.isFinite(count) && count > 0 ? String(count) : '' };
            }
            if (typeof val === 'boolean') {
                return { kind: 'text', text: val ? 'Có' : 'Không' };
            }
            return { kind: 'text', text: val ?? '---' };
        },

        isNotableIncident(incident) {
            const value = String(incident ?? '').trim();
            if (!value || value === '---') return false;

            const lower = value.toLowerCase();
            const normalPhrases = [
                'bình thường',
                'học bình thường',
                'không có',
                'khong co',
                'binh thuong',
                'hoc binh thuong',
                '--- không có ---',
            ];

            return !normalPhrases.includes(lower);
        },

        googleSheetPushFields(tabKey) {
            const sheetCols = this.tabDefinitions[tabKey]?.googleSheetPushCols;
            if (Array.isArray(sheetCols) && sheetCols.length > 0) {
                return sheetCols;
            }

            if (this.isCrosstabTab(tabKey)) {
                const spreadsheetCols = this.tabDefinitions[tabKey]?.exportSpreadsheetCols;
                if (Array.isArray(spreadsheetCols) && spreadsheetCols.length > 0) {
                    return spreadsheetCols;
                }
            }

            const exportCols = this.tabDefinitions[tabKey]?.exportCols;
            if (Array.isArray(exportCols) && exportCols.length > 0) {
                return exportCols;
            }

            return this.visibleColumns(tabKey).map((col) => col.key);
        },

        googleSheetPushColumnLabels(tabKey) {
            const fields = this.googleSheetPushFields(tabKey);
            const labelByKey = Object.fromEntries(
                (this.tabDefinitions[tabKey]?.columns || []).map((col) => [col.key, col.label]),
            );

            return fields.map((key) => {
                if (key === '__gap__') {
                    return '(cột trống)';
                }

                return labelByKey[key] || key;
            });
        },

        serializeGoogleSheetRow(tabKey, item) {
            const fields = this.googleSheetPushFields(tabKey);
            const row = {};

            fields.forEach((key) => {
                if (key === '__gap__') {
                    row[key] = '';
                } else if (key === 'is_notification') {
                    row[key] = parseNotificationValue(item[key]);
                } else if (this.isCrosstabTab(tabKey) && this.isCrosstabCountField(tabKey, key)) {
                    const count = Number(item[key] ?? 0);
                    row[key] = Number.isFinite(count) && count > 0 ? count : '';
                } else {
                    row[key] = item[key] ?? '';
                }
            });

            return row;
        },

        isCrosstabCountField(tabKey, key) {
            if (!this.isCrosstabTab(tabKey)) {
                return false;
            }

            const config = this.crosstabConfig(tabKey);
            const countKeys = config.violationColumns.map((col) => col.key);

            return countKeys.includes(key);
        },

        googleSheetPushRows(tabKey) {
            let rows = this.processedItems(tabKey);
            if (tabKey === 'online') {
                rows = rows.filter((item) => this.isNotableIncident(item.incident));
            }

            return rows.map((item) => this.serializeGoogleSheetRow(tabKey, item));
        },

        googleSheetPushRowCount(tabKey) {
            return this.googleSheetPushRows(tabKey).length;
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = null; }, 30000);
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        resolveTargetTabName() {
            if (this.availableTabs.length === 0) {
                return;
            }

            if (this.availableTabs.includes(this.targetTabName)) {
                return;
            }

            const normalizedTarget = this.targetTabName.trim().toLowerCase();
            const caseMatch = this.availableTabs.find(
                (tab) => tab.trim().toLowerCase() === normalizedTarget,
            );
            if (caseMatch) {
                this.targetTabName = caseMatch;
                return;
            }

            this.targetTabName = this.availableTabs[0];
        },

        async openPushDialog(tabKey) {
            if (!this.canEdit && !this.canExport) return;
            if (!this.googleSheetsConfigured) {
                this.showToast('Chưa cấu hình Google Sheets trong Tham số hệ thống.', 'error');
                return;
            }

            if (this.googleSheetPushRows(tabKey).length === 0) {
                const message = tabKey === 'online'
                    ? 'Không có dòng nào có việc phát sinh khác bình thường để đẩy.'
                    : 'Bảng hiện tại không có dữ liệu để đẩy.';
                this.showToast(message, 'error');
                return;
            }

            this.pushTabKey = tabKey;
            this.pushDialogOpen = true;
            this.targetTabName = this.defaultSheetTab;
            this.availableTabs = [];
            this.isLoadingTabs = true;

            try {
                const tabsUrl = new URL(this.googleSheetTabsUrl, window.location.origin);
                tabsUrl.searchParams.set('sheet_profile', 'daily');
                if (tabKey) {
                    tabsUrl.searchParams.set('tab_key', tabKey);
                }
                const response = await fetch(tabsUrl.toString(), {
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || 'Không thể tải danh sách tab.');
                }
                this.availableTabs = payload.tabs || [];
                if (payload.suggested_tab) {
                    this.targetTabName = payload.suggested_tab;
                } else if (payload.default_tab) {
                    this.targetTabName = payload.default_tab;
                }
                this.resolveTargetTabName();
            } catch (error) {
                this.showToast(error.message || 'Lỗi tải danh sách tab.', 'error');
                this.pushDialogOpen = false;
            } finally {
                this.isLoadingTabs = false;
            }
        },

        async confirmPushToGoogleSheet() {
            if (this.isPushing || !this.pushTabKey) return;

            const fields = this.googleSheetPushFields(this.pushTabKey);
            const rows = this.googleSheetPushRows(this.pushTabKey);
            if (rows.length === 0) {
                const message = this.pushTabKey === 'online'
                    ? 'Không có dòng nào có việc phát sinh khác bình thường để đẩy.'
                    : 'Bảng hiện tại không có dữ liệu để đẩy.';
                this.showToast(message, 'error');
                return;
            }

            this.isPushing = true;

            try {
                const response = await fetch(this.googleSheetPushUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        tab_name: this.targetTabName,
                        tab_key: this.pushTabKey,
                        sheet_profile: 'daily',
                        fields,
                        rows,
                    }),
                });
                const result = await response.json();
                this.showToast(result.message || 'Hoàn tất.', result.success ? 'success' : 'error');
                if (result.success) {
                    this.pushDialogOpen = false;
                }
            } catch (error) {
                this.showToast(error.message || 'Lỗi khi đẩy dữ liệu.', 'error');
            } finally {
                this.isPushing = false;
            }
        },

        async exportAll() {
            if (!this.canExport) return;
            if (this.isExportingAll) return;

            const hasData = this.tabList().some((key) => (this.datasets[key] || []).length > 0);
            if (!hasData) {
                this.showToast(TABLE_EXPORT_EMPTY, 'error');
                return;
            }

            if (!this.exportUrl) {
                this.showToast('Chưa cấu hình đường dẫn xuất Excel.', 'error');
                return;
            }

            this.isExportingAll = true;
            try {
                const url = new URL(this.exportUrl, window.location.origin);
                url.searchParams.set('date', this.dateIso);

                const exportDatasets = {};
                this.tabList().forEach((tabKey) => {
                    exportDatasets[tabKey] = this.processedItems(tabKey);
                });

                const response = await fetch(url.toString(), {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        date: this.dateIso,
                        datasets: exportDatasets
                    }),
                });

                if (!response.ok) {
                    let message = 'Lỗi xuất file.';
                    const text = await response.text();
                    try {
                        const payload = JSON.parse(text);
                        message = payload.message || message;
                    } catch {
                        const plain = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                        if (plain) {
                            message = plain.slice(0, 300);
                        }
                    }
                    throw new Error(message);
                }

                const blob = await response.blob();
                const filename = parseFilenameFromDisposition(response.headers.get('Content-Disposition'))
                    || `${slugify(this.officer || 'bao-cao')}_${this.dateIso}.xlsx`;
                const objectUrl = window.URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = objectUrl;
                anchor.download = filename;
                anchor.click();
                window.URL.revokeObjectURL(objectUrl);
                this.showToast('Đã xuất báo cáo Excel.');
            } catch (error) {
                this.showToast(error.message || 'Lỗi xuất file.', 'error');
            } finally {
                this.isExportingAll = false;
            }
        },
    }));
}
