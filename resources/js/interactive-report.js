import { getLanguage, t, translateMessage } from './language.js';
import { TABLE_EXPORT_EMPTY } from './nttu-table-messages.js';
import { createNttuMultiSelectMixin } from './nttu-multi-select.js';
import { headerIconPathForColumn, iconPathForName } from './nttu-icons.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, ROWS_PER_PAGE_OPTIONS } from './nttu-pagination.js';

function loadJson(key, fallback) {
    try {
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch {
        return fallback;
    }
}

function saveJson(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // ignore quota errors
    }
}

const DEFAULT_ADVANCED = {
    buildings: [],
    departments: [],
    employees: [],
    lecturers: [],
    recipients: [],
    officers: [],
    violationTypes: [],
    periodSession: 'all',
    periodStart: '1',
    periodEnd: '5',
};

function prefsStorageKey(config) {
    return `nttu_interactive_report_prefs_${config?.variant || 'default'}`;
}

function loadUserPrefs(config) {
    return loadJson(prefsStorageKey(config), {});
}

function reportTabKey(config, activeTab) {
    if (!config.tabs?.length) {
        return 'default';
    }
    return activeTab || config.tabs[0]?.key || 'default';
}

function defaultTabPrefs() {
    return {
        columnFilters: {},
        sortKey: '',
        sortDir: 'asc',
        currentPage: 1,
    };
}

function loadTabPrefs(prefs, tabKey) {
    const stored = prefs.tabs?.[tabKey];
    return {
        ...defaultTabPrefs(),
        ...(stored && typeof stored === 'object' ? stored : {}),
    };
}

function resolveInitialTab(config, prefs) {
    const tabKeys = (config.tabs || []).map((tab) => tab.key);
    if (prefs.activeTab && tabKeys.includes(prefs.activeTab)) {
        return prefs.activeTab;
    }
    return config.tabs?.[0]?.key || 'default';
}

function isValidIsoDate(value) {
    return /^\d{4}-\d{2}-\d{2}$/.test(String(value || ''));
}

function resolveInitialDateRange(config, prefs) {
    const fallbackFrom = config.initialFrom || new Date().toISOString().slice(0, 10);
    const fallbackTo = config.initialTo || new Date().toISOString().slice(0, 10);

    return {
        fromDate: isValidIsoDate(prefs.fromDate) ? prefs.fromDate : fallbackFrom,
        toDate: isValidIsoDate(prefs.toDate) ? prefs.toDate : fallbackTo,
    };
}

function uniqueOptions(rows, field) {
    const values = new Set();
    rows.forEach((row) => {
        const value = String(row[field] || '').trim();
        if (value && value !== '---') {
            values.add(value);
        }
    });
    return [...values].sort((a, b) => a.localeCompare(b, 'vi')).map((value) => ({ value, label: value }));
}

function parseDisplayDate(str) {
    if (!str || str === '---') return null;
    if (String(str).includes('/')) {
        const [d, m, y] = String(str).split('/');
        if (!d || !m || !y) return null;
        return new Date(`${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}T12:00:00`);
    }
    const parsed = new Date(str);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function parseIsoDate(str) {
    if (!str) {
        return null;
    }

    const parts = String(str).split('-').map(Number);
    if (parts.length < 3 || parts.some((part) => Number.isNaN(part))) {
        return null;
    }

    const [y, m, d] = parts;
    return new Date(y, m - 1, d, 12, 0, 0, 0);
}

function rowInDateRange(row, dateField, dateFields, fromDate, toDate) {
    const fields = dateFields?.length ? dateFields : [dateField];
    const start = parseIsoDate(fromDate);
    const end = parseIsoDate(toDate);
    if (!start || !end) {
        return true;
    }
    start.setHours(0, 0, 0, 0);
    end.setHours(23, 59, 59, 999);

    return fields.some((field) => {
        const d = parseDisplayDate(row[field]);
        return d && d >= start && d <= end;
    });
}

function periodOverlaps(periodStr, session, customStart, customEnd) {
    if (!session || session === 'all') return true;

    let start = 1;
    let end = 20;
    if (session === 'ca1') {
        start = 1;
        end = 5;
    } else if (session === 'ca2') {
        start = 6;
        end = 10;
    } else if (session === 'ca3') {
        start = 11;
        end = 16;
    } else if (session === 'custom') {
        start = Number(customStart) || 1;
        end = Number(customEnd) || 20;
    }

    const pStr = String(periodStr || '').toLowerCase();
    const pNums = pStr.match(/(\d+)/g);
    if (pNums && pNums.length > 0) {
        const minS = Math.min(...pNums.map(Number));
        const maxS = Math.max(...pNums.map(Number));
        return !(maxS < start || minS > end);
    }

    const isMorning = pStr.includes('sáng');
    const isAfternoon = pStr.includes('chiều');
    const isEvening = pStr.includes('tối');
    if (session === 'ca1') return isMorning;
    if (session === 'ca2') return isAfternoon;
    if (session === 'ca3') return isEvening;

    return true;
}

function mergeOptions(configOptions, rowOptions) {
    const map = new Map();
    [...(configOptions || []), ...(rowOptions || [])].forEach((opt) => {
        if (opt?.value) {
            map.set(opt.value, opt);
        }
    });
    return [...map.values()].sort((a, b) => a.label.localeCompare(b.label, 'vi'));
}

function normalizeRowsPerPage(value, fallback = 15) {
    const parsed = parseInt(String(value ?? ''), 10);
    if (!Number.isFinite(parsed) || parsed < 1) {
        return fallback;
    }

    return ROWS_PER_PAGE_OPTIONS.includes(parsed) ? parsed : fallback;
}

function rowsPerPageStorageKey(config) {
    return `nttu_interactive_report_rpp_${config?.variant || 'default'}`;
}

function advancedFilterOptionsKey(field) {
    const map = {
        buildings: 'buildingOptions',
        departments: 'departmentOptions',
        employees: 'employeeOptions',
        lecturers: 'lecturerOptions',
        recipients: 'recipientOptions',
        officers: 'officerOptions',
        violationTypes: 'violationTypeOptions',
    };

    return map[field] || `${field}Options`;
}

const GOOD_DEEDS_CENTER_KEYS = new Set([
    'code', 'campus', 'receptionDate', 'finderId', 'returnDate',
    'ownerId', 'ownerClass', 'ownerPhone',
    'appreciationCode', 'appreciationCampus', 'appreciationRecDate',
    'appreciationGiveDate', 'appreciationId',
]);

const TABLE_CENTER_DATA_COLS = new Set([
    'period', 'student_count', 'studentCount', 'type',
]);

export function registerInteractiveReport(Alpine) {
    const multiSelectMixin = createNttuMultiSelectMixin({
        valuesKey: 'advanced',
        resolveOptions(field) {
            const key = advancedFilterOptionsKey(field);
            return this[key] || [];
        },
        onChange() {
            this.currentPage = 1;
        },
    });

    Alpine.data('interactiveReportPage', (config) => {
        const prefs = loadUserPrefs(config);
        const initialTab = resolveInitialTab(config, prefs);
        const initialTabPrefs = loadTabPrefs(prefs, reportTabKey(config, initialTab));
        const initialDates = resolveInitialDateRange(config, prefs);

        return {
        ...multiSelectMixin,
        config,
        canExport: config.canExport !== false,
        canEdit: config.canEdit !== false,
        activeTab: initialTab,
        rows: config.rows || [],
        fromDate: initialDates.fromDate,
        toDate: initialDates.toDate,
        filtersExpanded: !!prefs.filtersExpanded,
        advanced: { ...DEFAULT_ADVANCED, ...(prefs.advanced || {}) },
        columnFilters: { ...(initialTabPrefs.columnFilters || {}) },
        sortKey: initialTabPrefs.sortKey || '',
        sortDir: initialTabPrefs.sortDir || 'asc',
        headerPopover: null,
        settingsOpen: false,
        selectedRowId: null,
        currentPage: normalizeCurrentPage(initialTabPrefs.currentPage || 1, 1),
        pageInput: 1,
        rowsPerPage: normalizeRowsPerPage(loadJson(rowsPerPageStorageKey(config), 15)),
        rowsPerPageOptions: ROWS_PER_PAGE_OPTIONS,
        columnVisibility: {},
        currentLang: getLanguage(),
        pushDialogOpen: false,
        isPushing: false,
        isLoadingTabs: false,
        availableTabs: [],
        targetTabName: config.defaultSheetTab || '',
        toast: null,
        loadingRows: false,
        _dateFetchTimer: null,
        _skipDateFetch: true,

        init() {
            const defaults = {};
            this.currentColumns.forEach((col) => {
                defaults[col.key] = col.defaultVisible !== false;
            });
            this.columnVisibility = loadJson(this.tabStorageKey, defaults);
            this.pageInput = this.safeCurrentPage;

            this.$watch('currentPage', (value) => {
                this.pageInput = value;
                this.persistUserPrefs();
            });
            this.$watch('fromDate', () => {
                this.currentPage = 1;
                this.persistUserPrefs();
                this.scheduleDateFetch();
            });
            this.$watch('toDate', () => {
                this.currentPage = 1;
                this.persistUserPrefs();
                this.scheduleDateFetch();
            });
            this.$watch('filtersExpanded', () => this.persistUserPrefs());
            this.$watch('advanced', () => this.persistUserPrefs(), { deep: true });
            this.$watch('columnFilters', () => this.persistUserPrefs(), { deep: true });
            this.$watch('sortKey', () => this.persistUserPrefs());
            this.$watch('sortDir', () => this.persistUserPrefs());
            window.addEventListener('nttu-language-changed', (event) => {
                this.currentLang = event.detail?.language || getLanguage();
            });
            this.$nextTick(() => {
                this._skipDateFetch = false;
                if (this.config.dataUrl) {
                    this.fetchReportRows();
                }
            });
        },

        scheduleDateFetch() {
            if (this._skipDateFetch || !this.config.dataUrl) {
                return;
            }
            clearTimeout(this._dateFetchTimer);
            this._dateFetchTimer = setTimeout(() => this.fetchReportRows(), 350);
        },

        async fetchReportRows() {
            if (!this.config.dataUrl || this.loadingRows) {
                return;
            }
            this.loadingRows = true;
            try {
                const params = new URLSearchParams({
                    from: this.fromDate,
                    to: this.toDate,
                });
                const response = await fetch(`${this.config.dataUrl}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!response.ok) {
                    throw new Error('Không tải được dữ liệu báo cáo');
                }
                const payload = await response.json();
                if (payload.tabs) {
                    this.config.tabs = (this.config.tabs || []).map((tab) => {
                        const updated = payload.tabs[tab.key];
                        return updated ? { ...tab, rows: updated.rows || [] } : tab;
                    });
                } else {
                    this.rows = payload.rows || [];
                    this.config.rows = this.rows;
                    if (this.config.tabs?.length === 1) {
                        this.config.tabs[0].rows = this.rows;
                    }
                }
                if (payload.filterOptions) {
                    this.config.filterOptions = payload.filterOptions;
                }
            } catch (error) {
                this.toast = { message: error.message || 'Lỗi tải dữ liệu', type: 'error' };
            } finally {
                this.loadingRows = false;
            }
        },

        labelText(text) {
            return t(text, this.currentLang);
        },

        columnLabel(column) {
            const label = column?.headerLabel || column?.label || '';
            return t(label, this.currentLang);
        },

        columnHeaderAlignClass(column) {
            const align = column?.align
                || (this.config.columnHeaderAlign === 'center' ? 'center' : 'start');
            return align === 'center' ? 'justify-center text-center' : 'justify-start text-left';
        },

        columnHeaderTextClass(column) {
            const align = column?.align
                || (this.config.columnHeaderAlign === 'center' ? 'center' : 'start');
            return align === 'center' ? '' : 'flex-1';
        },

        isColVisible(key) {
            return this.columnVisibility[key] !== false;
        },

        columnByKey(key) {
            return this.currentColumns.find((col) => col.key === key) || null;
        },

        settingsColumnLabel(column) {
            const label = column?.settingsLabel || column?.label || '';
            return t(label, this.currentLang);
        },

        exportColumnLabel(column) {
            const label = column?.exportLabel || column?.label || '';
            return t(label, this.currentLang);
        },

        settingsGroupLabel(group) {
            return group ? t(group, this.currentLang) : '';
        },

        groupLabelText(group) {
            const label = this.config.groupLabels?.[group] || group;
            return t(label, this.currentLang);
        },

        get activeExportFields() {
            if (this.currentTabConfig?.exportFields?.length) {
                return this.currentTabConfig.exportFields;
            }

            return this.config.exportFields || [];
        },

        get currentTabConfig() {
            if (!this.config.tabs?.length) {
                return { key: 'default', label: '', rows: this.config.rows, columns: this.config.columns };
            }
            return this.config.tabs.find((tab) => tab.key === this.activeTab) || this.config.tabs[0];
        },

        get currentTableTitle() {
            const title = this.currentTabConfig.tableTitle || this.config.tableTitle || 'Bảng báo cáo';
            return t(title, this.currentLang);
        },

        get currentEmptyMessage() {
            const custom = this.currentTabConfig.emptyMessage || this.config.emptyMessage;
            return custom ? t(custom, this.currentLang) : null;
        },

        get hasActiveFilters() {
            const adv = this.advanced || {};
            const hasAdvanced = Object.values(adv).some((value) => {
                if (Array.isArray(value)) {
                    return value.length > 0;
                }
                if (typeof value === 'string') {
                    return value.trim() !== '';
                }

                return value !== null && value !== undefined && value !== false;
            });
            const hasColumn = Object.values(this.columnFilters || {}).some(
                (value) => String(value ?? '').trim() !== '',
            );

            return hasAdvanced || hasColumn;
        },

        clearTableFilters() {
            this.clearAdvanced();
            this.columnFilters = {};
            this.currentPage = 1;
            this.persistUserPrefs();
        },

        get currentSettingsTitle() {
            const title = this.currentTabConfig.settingsTitle || this.config.settingsTitle || 'Hiển thị cột';
            return t(title, this.currentLang);
        },

        get reportTheme() {
            return this.config.theme || 'blue';
        },

        get currentRows() {
            return this.currentTabConfig.rows || this.rows;
        },

        get currentColumns() {
            return this.currentTabConfig.columns || this.config.columns || [];
        },

        get visibleColumns() {
            return this.currentColumns.filter((col) => this.columnVisibility[col.key] !== false);
        },

        get headerMode() {
            return this.currentTabConfig.headerMode || this.config.headerMode || 'flat';
        },

        get tableCardTheme() {
            return this.currentTabConfig.tableCardTheme
                || this.config.tableCardTheme
                || this.config.theme
                || 'blue';
        },

        get tableHeadTheme() {
            return this.config.tableHeadTheme || null;
        },

        get cardIcon() {
            return this.currentTabConfig.cardIcon || this.config.cardIcon || null;
        },

        get handlingHeaderParts() {
            if (this.headerMode !== 'handling') {
                return null;
            }
            const reception = [];
            const handling = [];
            const after = [];
            let seenHandling = false;
            this.visibleColumns.forEach((col) => {
                if (col.group === 'Hướng xử lý') {
                    seenHandling = true;
                    handling.push(col);
                } else if (seenHandling) {
                    after.push(col);
                } else if (col.group === 'Tiếp nhận') {
                    reception.push(col);
                } else {
                    reception.push(col);
                }
            });

            return { reception, before: reception, handling, after };
        },

        get useReceptionGroupHeader() {
            if (this.config.receptionGroupHeader === false) {
                return false;
            }
            return this.headerMode === 'handling'
                && this.visibleColumns.some((col) => col.group === 'Tiếp nhận');
        },

        get handlingColSpan() {
            return this.visibleColumns.filter((col) => col.group === 'Hướng xử lý').length;
        },

        get spanTwoColumns() {
            if (this.headerMode === 'grouped') {
                return this.visibleColumns.filter((col) => col.group === 'Chung');
            }
            return [];
        },

        get groupedSections() {
            if (this.headerMode === 'grouped') {
                return ['Tiếp nhận', 'Giao trả', 'Tri ân']
                    .map((group) => ({
                        group,
                        label: this.groupLabelText(group),
                        shade: this.groupShade(group),
                        columns: this.visibleColumns.filter((col) => col.group === group),
                    }))
                    .filter((section) => section.columns.length > 0);
            }
            return [];
        },

        get visibleGroupedSubColumns() {
            if (this.headerMode !== 'grouped') {
                return [];
            }
            return ['Tiếp nhận', 'Giao trả', 'Tri ân'].flatMap((group) =>
                this.visibleColumns.filter((col) => col.group === group),
            );
        },

        groupColSpan(group) {
            return this.visibleColumns.filter((col) => col.group === group).length;
        },

        groupShade(group) {
            if (group === 'Giao trả') return 'return';
            if (group === 'Tiếp nhận') return 'reception';
            if (group === 'Tri ân') return 'gratitude';
            return '';
        },

        groupHeaderClass(shade) {
            if (shade === 'return') return 'report-th-group-return';
            if (shade === 'reception') return 'report-th-group-reception';
            if (shade === 'gratitude') return 'report-th-group-gratitude';
            return '';
        },

        get settingsGroups() {
            if (this.config.settingsFlatList) {
                return [null];
            }
            if (this.headerMode === 'grouped') {
                return ['Chung', 'Tiếp nhận', 'Giao trả', 'Tri ân'];
            }
            if (this.headerMode === 'handling') {
                if (this.useReceptionGroupHeader) {
                    const groups = ['Tiếp nhận', 'Hướng xử lý'];
                    if ((this.handlingHeaderParts?.after || []).length) {
                        groups.push(null);
                    }
                    return groups;
                }
                return [null, 'Hướng xử lý'];
            }
            return [null];
        },

        columnsForSettingsGroup(group) {
            if (this.config.settingsFlatList && group === null) {
                return this.currentColumns;
            }
            if (this.headerMode === 'handling' && group === null) {
                const parts = this.handlingHeaderParts;
                if (!parts) {
                    return this.currentColumns.filter((col) => !col.group);
                }
                if (this.useReceptionGroupHeader) {
                    return parts.after;
                }
                return [...parts.before, ...parts.after];
            }
            if (group === null) {
                return this.currentColumns.filter((col) => !col.group);
            }
            return this.currentColumns.filter((col) => col.group === group);
        },

        applyRowFilters(includeColumnFilters = true) {
            let result = [...this.currentRows];
            const adv = this.advanced;

            if ((this.config.dateField || this.config.dateFields) && !this.config.dataUrl) {
                const dateField = this.currentTabConfig.dateField || this.config.dateField;
                const dateFields = this.currentTabConfig.dateFields || this.config.dateFields;
                result = result.filter((row) => rowInDateRange(
                    row,
                    dateField,
                    dateFields,
                    this.fromDate,
                    this.toDate,
                ));
            }

            if (this.config.advancedFilters?.includes('buildings') && adv.buildings.length) {
                result = result.filter((row) => adv.buildings.includes(row._building));
            }
            if (this.config.advancedFilters?.includes('departments') && adv.departments.length) {
                result = result.filter((row) => adv.departments.includes(row.department));
            }
            if (this.config.advancedFilters?.includes('employees') && adv.employees.length) {
                result = result.filter((row) => adv.employees.includes(row.employee));
            }
            if (this.config.advancedFilters?.includes('lecturers') && adv.lecturers.length) {
                result = result.filter((row) => {
                    const proctors = row._proctors || [];
                    return adv.lecturers.includes(row._lecturerRaw)
                        || adv.lecturers.some((v) => String(row.lecturer || '').includes(v))
                        || adv.lecturers.some((v) => proctors.includes(v));
                });
            }
            if (this.config.advancedFilters?.includes('recipients') && adv.recipients.length) {
                result = result.filter((row) => adv.recipients.includes(row._recipient));
            }
            if (this.config.advancedFilters?.includes('officers') && adv.officers.length) {
                result = result.filter((row) => adv.officers.includes(row.officer));
            }
            if (this.config.advancedFilters?.includes('violationTypes') && adv.violationTypes.length) {
                result = result.filter((row) => adv.violationTypes.includes(row.violationType));
            }
            if (this.config.advancedFilters?.includes('period')) {
                result = result.filter((row) => periodOverlaps(
                    row._periodRaw || row.period,
                    adv.periodSession,
                    adv.periodStart,
                    adv.periodEnd,
                ));
            }

            if (includeColumnFilters) {
                Object.entries(this.columnFilters).forEach(([key, value]) => {
                    if (!value) return;
                    const q = String(value).toLowerCase();
                    result = result.filter((row) => String(row[key] ?? '').toLowerCase().includes(q));
                });
            }

            if (this.sortKey) {
                const dir = this.sortDir === 'asc' ? 1 : -1;
                result.sort((a, b) => {
                    const av = String(a[this.sortKey] ?? '');
                    const bv = String(b[this.sortKey] ?? '');
                    if (av < bv) return -1 * dir;
                    if (av > bv) return 1 * dir;
                    return 0;
                });
            }

            return result;
        },

        get filteredRows() {
            return this.applyRowFilters(true);
        },

        get advancedFilteredRows() {
            return this.applyRowFilters(false);
        },

        get normalizedRowsPerPage() {
            return normalizeRowsPerPage(this.rowsPerPage);
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.normalizedRowsPerPage));
        },

        get safeCurrentPage() {
            return normalizeCurrentPage(this.currentPage, this.totalPages);
        },

        get pagedRows() {
            const perPage = this.normalizedRowsPerPage;
            const start = (this.safeCurrentPage - 1) * perPage;
            return this.filteredRows.slice(start, start + perPage);
        },

        get buildingOptions() {
            return mergeOptions(this.config.filterOptions?.buildings, uniqueOptions(this.currentRows, '_building'));
        },

        get departmentOptions() {
            return mergeOptions(this.config.filterOptions?.departments, uniqueOptions(this.currentRows, 'department'));
        },

        get employeeOptions() {
            return mergeOptions(this.config.filterOptions?.employees, uniqueOptions(this.currentRows, 'employee'));
        },

        get lecturerOptions() {
            return mergeOptions(this.config.filterOptions?.lecturers, uniqueOptions(this.currentRows, 'lecturer'));
        },

        get recipientOptions() {
            return mergeOptions(this.config.filterOptions?.recipients, uniqueOptions(this.currentRows, '_recipient'));
        },

        get officerOptions() {
            return mergeOptions(this.config.filterOptions?.officers, uniqueOptions(this.currentRows, 'officer'));
        },

        get violationTypeOptions() {
            return mergeOptions(this.config.filterOptions?.violationTypes, uniqueOptions(this.currentRows, 'violationType'));
        },

        switchTab(key) {
            if (key === this.activeTab) {
                return;
            }
            this.persistUserPrefs();
            this.activeTab = key;
            if (this.config.variant === 'good-deeds' && key !== 'property') {
                this.pushDialogOpen = false;
            }
            this.selectedRowId = null;
            const tabPrefs = loadTabPrefs(loadUserPrefs(this.config), reportTabKey(this.config, key));
            this.columnFilters = { ...(tabPrefs.columnFilters || {}) };
            this.sortKey = tabPrefs.sortKey || '';
            this.sortDir = tabPrefs.sortDir || 'asc';
            this.currentPage = normalizeCurrentPage(tabPrefs.currentPage || 1, 1);
            this.pageInput = this.currentPage;
            const defaults = {};
            this.currentColumns.forEach((col) => {
                defaults[col.key] = col.defaultVisible !== false;
            });
            this.columnVisibility = loadJson(this.tabStorageKey, defaults);
            this.persistUserPrefs();
        },

        get tabStorageKey() {
            if (!this.config.tabs?.length) return this.config.storageKey;
            if (this.config.variant === 'good-deeds') {
                return `gooddeeds-${this.activeTab}-cols`;
            }
            return `${this.config.storageKey}_${this.activeTab}`;
        },

        optionLabel(field, value) {
            return this.nttuMultiLabel(field, value);
        },

        clearMultiField(field) {
            this.nttuMultiClear(field);
        },

        removeMultiValue(field, value, event) {
            this.nttuMultiRemove(field, value, event);
        },

        clearAdvanced() {
            this.advanced = { ...DEFAULT_ADVANCED };
            this.currentPage = 1;
            this.persistUserPrefs();
        },

        persistUserPrefs() {
            const tabKey = reportTabKey(this.config, this.activeTab);
            const existing = loadUserPrefs(this.config);
            saveJson(prefsStorageKey(this.config), {
                filtersExpanded: this.filtersExpanded,
                activeTab: this.activeTab,
                fromDate: this.fromDate,
                toDate: this.toDate,
                advanced: this.advanced,
                tabs: {
                    ...(existing.tabs || {}),
                    [tabKey]: {
                        columnFilters: this.columnFilters,
                        sortKey: this.sortKey,
                        sortDir: this.sortDir,
                        currentPage: this.currentPage,
                    },
                },
            });
        },

        setColumnFilter(key, value) {
            this.columnFilters = { ...this.columnFilters, [key]: value };
            this.currentPage = 1;
        },

        clearColumnFilter(key) {
            this.setColumnFilter(key, '');
            this.headerPopover = null;
        },

        requestSort(key, dir) {
            this.sortKey = key;
            this.sortDir = dir;
            this.headerPopover = null;
        },

        clearSort() {
            this.sortKey = '';
            this.sortDir = 'asc';
            this.headerPopover = null;
        },

        sortDirection(key) {
            if (this.sortKey !== key) return null;
            return this.sortDir;
        },

        isColumnFiltered(key) {
            return !!this.columnFilters[key];
        },

        isSortable(column) {
            return column.sortable !== false && column.type !== 'signature';
        },

        filterPlaceholder(field) {
            return this.config.filterPlaceholders?.[field] || 'Chọn...';
        },

        toggleColumn(key) {
            const current = this.columnVisibility[key] !== false;
            this.columnVisibility = { ...this.columnVisibility, [key]: !current };
            localStorage.setItem(this.tabStorageKey, JSON.stringify(this.columnVisibility));
            this.scheduleColumnResize();
        },

        selectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(
                this.currentColumns.map((col) => col.key),
                true,
            );
            localStorage.setItem(this.tabStorageKey, JSON.stringify(this.columnVisibility));
            this.scheduleColumnResize();
        },

        deselectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(
                this.currentColumns.map((col) => col.key),
                false,
            );
            localStorage.setItem(this.tabStorageKey, JSON.stringify(this.columnVisibility));
            this.scheduleColumnResize();
        },

        scheduleColumnResize() {
            this.$nextTick(() => {
                const table = this.$root?.querySelector?.('table[data-col-resize]');
                if (table && typeof window.nttuScheduleColumnResize === 'function') {
                    window.nttuScheduleColumnResize(table, [0, 50, 150]);
                }
            });
        },

        selectRow(row) {
            this.selectedRowId = this.selectedRowId === row.id ? null : row.id;
        },

        setRowsPerPage(value) {
            this.rowsPerPage = normalizeRowsPerPage(value, 15);
            this.currentPage = 1;
            this.pageInput = 1;
            localStorage.setItem(rowsPerPageStorageKey(this.config), JSON.stringify(this.rowsPerPage));
        },

        goToPage(page) {
            const p = Number(page);
            if (p >= 1 && p <= this.totalPages) {
                this.currentPage = p;
            } else {
                this.pageInput = this.safeCurrentPage;
            }
        },

        columnHeaderIconPath(col) {
            if (!col) {
                return null;
            }
            return headerIconPathForColumn(col.key, col);
        },

        columnIconHtml(iconOrCol) {
            const path = typeof iconOrCol === 'object' && iconOrCol !== null
                ? this.columnHeaderIconPath(iconOrCol)
                : iconPathForName(iconOrCol);
            if (!path) {
                return '';
            }
            return `<svg class="h-3.5 w-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${path}"></path></svg>`;
        },

        cellClass(column, row) {
            const isGoodDeeds = this.config.variant === 'good-deeds';
            const isIncidentReports = this.config.variant === 'incident-reports';
            const classes = ['border-r', 'border-gray-200', 'align-middle', 'min-w-0', 'break-words'];
            if (isGoodDeeds || isIncidentReports) {
                classes.push('p-2');
                if (isGoodDeeds && GOOD_DEEDS_CENTER_KEYS.has(column.key)) {
                    classes.push('text-center');
                }
                if (TABLE_CENTER_DATA_COLS.has(column.key)) {
                    classes.push('text-center');
                }
                if (isIncidentReports && column.align === 'center') {
                    classes.push('text-center');
                }
                if (isIncidentReports && column.align === 'left') {
                    classes.push('text-left');
                }
                if (isGoodDeeds && column.key === 'code') {
                    classes.push('font-medium');
                }
                if (isGoodDeeds && column.group === 'Giao trả') {
                    classes.push('report-td-return');
                }
            } else {
                classes.push('px-3', 'py-3');
                if (column.align === 'center' || TABLE_CENTER_DATA_COLS.has(column.key)) {
                    classes.push('text-center');
                }
                if (column.group === 'Giao trả') {
                    classes.push('report-td-return');
                }
            }
            if (column.tone === 'danger') classes.push('text-red-600 font-semibold');
            if (column.tone === 'primary') classes.push('text-blue-700 font-bold text-xs');
            if (column.key === 'status' && row.status === 'Đã giải quyết') classes.push('text-green-600 font-medium');
            if (column.key === 'status' && row.status === 'Đang xử lý') classes.push('text-orange-600 font-medium');
            return classes.join(' ');
        },

        cellTextClass(column) {
            const wrap = 'break-words whitespace-normal';
            if (this.config.variant === 'good-deeds') {
                if (column.key === 'property') {
                    return `text-xs text-gray-800 ${wrap}`;
                }
                return `text-gray-800 ${wrap}`;
            }
            if (this.config.variant === 'incident-reports') {
                if (column.key === 'content') {
                    return `text-sm leading-relaxed text-gray-800 ${wrap}`;
                }
                if (column.key === 'note') {
                    return `text-gray-500 ${wrap}`;
                }
                return `text-gray-800 ${wrap}`;
            }
            const styles = {
                medium: `text-gray-900 font-medium ${wrap}`,
                'mono-xs': `font-mono text-[10px] text-gray-800 ${wrap}`,
                bold: `font-bold text-gray-900 ${wrap}`,
                'bold-blue-xs': `font-bold text-blue-700 text-xs ${wrap}`,
                'xs-medium': `text-gray-900 font-medium text-xs ${wrap}`,
                xs: `text-xs text-gray-800 leading-snug ${wrap}`,
                'italic-gray': `text-xs text-gray-500 italic ${wrap}`,
                leading: `text-sm leading-relaxed ${wrap}`,
                center: 'text-center',
            };
            return styles[column.style] || `text-xs text-gray-800 ${wrap}`;
        },

        cellInnerClass(column) {
            if (column.tone === 'badge-danger') {
                return 'inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700';
            }
            if (column.tone === 'badge-info') {
                return 'inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700';
            }
            return '';
        },

        renderCell(row, column) {
            if (column.type === 'signature') {
                return row.signature
                    ? `<img src="${row.signature}" alt="Chữ ký" class="mx-auto h-8 max-w-[120px] object-contain" />`
                    : '<span class="text-gray-400 text-xs italic">Chưa ký</span>';
            }
            if (column.type === 'citizen') {
                return `
                    <div class="text-[13px] leading-relaxed">
                        <div class="font-semibold mb-1">- Họ và tên: ${row.senderName || '---'}</div>
                        <div class="mb-1">- MSSV/CCCD: ${row.senderId || '---'}</div>
                        <div class="mb-1">- Địa chỉ: ${row.address || '---'}</div>
                        <div>- Điện thoại: ${row.phone || '---'}</div>
                    </div>`;
            }
            if (column.type === 'multiline') {
                return String(row[column.key] || '---').replace(/\n/g, '<br>');
            }
            const value = row[column.key] ?? '---';
            const innerClass = this.cellInnerClass(column);
            if (innerClass) {
                return `<span class="${innerClass}">${value}</span>`;
            }
            return value;
        },

        isHtmlCell(column) {
            return ['signature', 'citizen', 'multiline'].includes(column.type)
                || ['badge-danger', 'badge-info'].includes(column.tone);
        },

        async exportExcel() {
            if (!this.canExport) return;
            if (this.config.exportUrl) {
                const url = new URL(this.config.exportUrl, window.location.origin);
                url.searchParams.set('from', this.fromDate);
                url.searchParams.set('to', this.toDate);
                if (this.config.variant === 'good-deeds' && this.activeTab) {
                    url.searchParams.set('tab', this.activeTab);
                }
                window.location.href = url.toString();
                return;
            }

            const isGoodDeeds = this.config.variant === 'good-deeds';
            const isIncidentReports = this.config.variant === 'incident-reports';
            const rows = this.config.exportAdvancedOnly ? this.advancedFilteredRows : this.filteredRows;
            if (!rows.length) {
                const emptyMsg = this.currentTabConfig.exportEmptyMessage
                    || this.config.exportEmptyMessage
                    || TABLE_EXPORT_EMPTY;
                window.alert(translateMessage(emptyMsg, this.currentLang));
                return;
            }

            const XLSX = await import('xlsx');

            let headers;
            let keys;
            if (this.activeExportFields.length) {
                headers = this.activeExportFields.map((field) => this.labelText(field.label));
                keys = this.activeExportFields.map((field) => field.key);
            } else {
                const exportColumns = (isGoodDeeds ? this.currentColumns : this.visibleColumns)
                    .filter((col) => col.type !== 'signature');
                headers = exportColumns.map((col) => (
                    isGoodDeeds ? this.exportColumnLabel(col) : col.label
                ));
                keys = exportColumns.map((col) => col.key);
            }

            const data = rows.map((row, index) => {
                const entry = { STT: index + 1 };
                keys.forEach((key, idx) => {
                    let value = row[key] ?? '';
                    if (key === 'senderName' && !this.activeExportFields.length) {
                        value = [row.senderName, row.senderId, row.address, row.phone].filter(Boolean).join(' | ');
                    }
                    entry[headers[idx]] = value;
                });
                return entry;
            });

            let fileName;
            if (this.config.variant === 'good-deeds') {
                const base = this.activeTab === 'deed' ? 'TriAnNguoiViecTot' : 'TiepNhanTaiSan';
                fileName = `${base}_${this.fromDate}_to_${this.toDate}.xlsx`;
            } else if (isIncidentReports) {
                const baseName = (this.config.exportFileName || 'BaoCaoTiepNhanDonThu.xlsx').replace(/\.xlsx$/i, '');
                fileName = `${baseName}_${this.fromDate}_to_${this.toDate}.xlsx`;
            } else {
                const tabSuffix = this.config.tabs?.length ? `_${this.activeTab}` : '';
                const baseName = (this.config.exportFileName || 'BaoCao.xlsx').replace(/\.xlsx$/i, '');
                fileName = `${baseName}_${this.fromDate}_${this.toDate}${tabSuffix}.xlsx`;
            }
            const sheet = XLSX.utils.json_to_sheet(data);
            const book = XLSX.utils.book_new();
            let sheetName = this.config.exportSheetName || 'Báo cáo';
            if (this.activeTab === 'deed') {
                sheetName = 'Tri ân người việc tốt';
            } else if (this.activeTab === 'property') {
                sheetName = 'Tiếp nhận tài sản';
            }
            XLSX.utils.book_append_sheet(book, sheet, sheetName);
            XLSX.writeFile(book, fileName);
        },

        printReport() {
            window.print();
        },

        get googleSheetsEnabled() {
            return !!this.config.googleSheets;
        },

        get googleSheetsConfigured() {
            return !!this.config.googleSheetsConfigured;
        },

        get showGoogleSheetsPush() {
            if (!this.googleSheetsEnabled || !this.googleSheetsConfigured) {
                return false;
            }
            if (this.config.variant === 'student-violations') {
                return false;
            }
            if (this.config.variant === 'incident-reports') {
                return false;
            }
            if (this.config.variant === 'good-deeds') {
                return this.activeTab === 'property' || this.activeTab === 'deed';
            }

            return true;
        },

        googleSheetsPushRows() {
            return this.config.exportAdvancedOnly ? this.advancedFilteredRows : this.filteredRows;
        },

        googleSheetsPushColumns() {
            if (this.activeExportFields.length > 0) {
                return this.activeExportFields;
            }

            return this.visibleColumns.filter((col) => col.type !== 'signature');
        },

        googleSheetColumnLabel(column) {
            if (this.activeExportFields.length > 0) {
                return column?.label || this.columnLabel(column);
            }
            if (this.config.variant === 'good-deeds') {
                return this.exportColumnLabel(column);
            }
            return this.columnLabel(column);
        },

        serializeGoogleSheetPushRow(row, fields) {
            const out = {};
            fields.forEach((key) => {
                const value = row[key];
                out[key] = value === undefined || value === null || value === '---' ? '' : value;
            });
            return out;
        },

        resolveGoogleSheetTabKey() {
            if (this.currentTabConfig?.googleSheetTabKey) {
                return this.currentTabConfig.googleSheetTabKey;
            }
            if (this.config.googleSheetTabKey) {
                return this.config.googleSheetTabKey;
            }
            if (this.config.variant === 'good-deeds') {
                return this.activeTab === 'deed' ? 'good-deeds-deed' : 'good-deeds-property';
            }

            return this.activeTab || '';
        },

        resolveGoogleSheetProfile() {
            return this.config.googleSheetProfile || 'summary';
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => {
                if (this.toast?.message === message) {
                    this.toast = null;
                }
            }, 4000);
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

        async openGoogleSheetsDialog() {
            if (!this.canEdit && !this.canExport) return;
            if (!this.googleSheetsConfigured) {
                this.showToast(this.labelText('Chưa cấu hình Google Sheet báo cáo tổng hợp học kỳ trong Tham số hệ thống (tab Tích hợp).'), 'error');
                return;
            }

            if (this.googleSheetsPushRows().length === 0) {
                this.showToast(this.labelText('Bảng hiện tại không có dữ liệu để đẩy.'), 'error');
                return;
            }

            this.pushDialogOpen = true;
            this.targetTabName = this.config.defaultSheetTab || '';
            this.availableTabs = [];
            this.isLoadingTabs = true;

            try {
                const tabsUrl = new URL(this.config.googleSheetTabsUrl, window.location.origin);
                const tabKey = this.resolveGoogleSheetTabKey();
                tabsUrl.searchParams.set('sheet_profile', this.resolveGoogleSheetProfile());
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

        async confirmGoogleSheetsPush() {
            if (this.isPushing) {
                return;
            }

            const fields = this.googleSheetsPushColumns().map((col) => col.key);
            const sourceRows = this.googleSheetsPushRows();
            if (sourceRows.length === 0) {
                this.showToast(this.labelText('Bảng hiện tại không có dữ liệu để đẩy.'), 'error');
                return;
            }

            const rows = sourceRows.map((row) => this.serializeGoogleSheetPushRow(row, fields));
            this.isPushing = true;

            try {
                const response = await fetch(this.config.googleSheetPushUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        tab_name: this.targetTabName,
                        tab_key: this.resolveGoogleSheetTabKey(),
                        sheet_profile: this.resolveGoogleSheetProfile(),
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
    };
    });
}
