import { columnKeyIconPaths as COLUMN_ICON_PATHS, headerIconClassForKey } from './nttu-icons.js';
import { alertMessage, getLanguage } from './language.js';
import { createNttuMultiSelectMixin } from './nttu-multi-select.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice, readStoredRowsPerPage, ROWS_PER_PAGE_OPTIONS, writeStoredRowsPerPage } from './nttu-pagination.js';

const COLUMN_DEFS = {
    date: 'Ngày',
    building: 'Dãy nhà',
    room: 'Phòng',
    period: 'Tiết',
    type: 'LT/TH',
    department: 'Khoa sử dụng',
    class: 'Lớp',
    student_count: 'Sĩ số',
    content: 'Nội dung',
    status: 'Trạng thái',
    note: 'Ghi chú',
};

const DEFAULT_VISIBILITY = {
    date: true,
    building: true,
    room: true,
    period: true,
    type: false,
    department: true,
    class: true,
    student_count: true,
    content: false,
    status: true,
    note: false,
};

const colVisKey = 'nttu_dash_schedules_colvis_v2';

const STATUS_OPTIONS = ['Phòng học', 'Phòng thi', 'Phòng tự do'];

const advKey = 'nttu_dashboard_schedule_adv_filters';
const presetKey = 'nttu_dashboard_schedule_filter_presets';

function readStorage(key, fallback) {
    try {
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch (_) {
        return fallback;
    }
}

function writeStorage(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (_) {}
}

function dmyToIso(dmy) {
    if (!dmy || !dmy.includes('/')) return dmy || '';
    const [d, m, y] = dmy.split('/');
    return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
}

function isoToDmy(iso) {
    if (!iso || !iso.includes('-')) return iso || '';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

function periodStart(period) {
    const raw = String(period ?? '').split(/→|->/)[0]?.trim() ?? '';
    const num = parseInt(raw, 10);
    return Number.isNaN(num) ? null : num;
}

function normalizeDashboardAdvancedFilters(raw, fallbackDateIso = '') {
    const f = raw || {};
    let date = f.date || fallbackDateIso || '';
    if (date.includes('/')) {
        date = dmyToIso(date);
    }
    return {
        date,
        buildings: Array.isArray(f.buildings) ? [...f.buildings] : [],
        periodSession: f.periodSession || 'all',
        periodStart: f.periodStart ?? '',
        periodEnd: f.periodEnd ?? '',
        statuses: Array.isArray(f.statuses) ? [...f.statuses] : [],
    };
}

export function dashboardScheduleLookup(config) {
    const columnKeys = Object.keys(COLUMN_DEFS);
    const initialDateIso = dmyToIso(config.initialDate || '');
    let savedAdvanced = readStorage(advKey, null);
    let savedPresets = readStorage(presetKey, []);

    const multiSelectMixin = createNttuMultiSelectMixin({
        resolveOptions(field) {
            if (field === 'buildings') {
                const fromMaster = this.masterData.buildings || [];
                const fromRows = [...new Set(
                    (this.rows || [])
                        .map((row) => String(row.building ?? '').trim())
                        .filter(Boolean),
                )];
                const seen = new Set();
                const merged = [];

                [...fromMaster, ...fromRows].forEach((value) => {
                    const normalized = String(value).trim();
                    if (!normalized || seen.has(normalized)) {
                        return;
                    }
                    seen.add(normalized);
                    merged.push(normalized);
                });

                return merged.sort((a, b) => a.localeCompare(b, 'vi'));
            }
            if (field === 'statuses') {
                return STATUS_OPTIONS;
            }

            return [];
        },
        onChange() {
            this.currentPage = 1;
        },
    });

    return {
        ...multiSelectMixin,
        rows: config.rows || [],
        masterData: config.masterData || {},
        lookupUrl: config.lookupUrl,
        exportUrl: config.exportUrl || '',
        presetsUrl: config.presetsUrl || null,
        canExport: config.canExport !== false,
        initialDateIso,
        loading: false,
        filterOpen: false,
        columnsOpen: false,
        headerPopover: null,
        presetMenuOpen: false,
        presetNaming: false,
        presetName: '',
        presetRenaming: null,
        renamingPresetValue: '',
        savingPresets: false,
        filterPresets: savedPresets,
        sortConfig: readStorage('nttu_dash_schedules_sort', null),
        columnVisibility: { ...DEFAULT_VISIBILITY, ...readStorage(colVisKey, {}) },
        filters: readStorage('nttu_dash_schedules_filters', {}),
        selectedRowIds: readStorage('nttu_dash_schedules_selected', []),
        currentPage: normalizeCurrentPage(readStorage('nttu_dash_schedules_page', 1), 1),
        rowsPerPage: readStoredRowsPerPage('nttu_dash_schedules_rpp', 10),
        rowsPerPageOptions: ROWS_PER_PAGE_OPTIONS,
        advancedFilters: normalizeDashboardAdvancedFilters(savedAdvanced, initialDateIso),

        get columns() {
            return COLUMN_DEFS;
        },

        get columnOrder() {
            return columnKeys;
        },

        get visibleColumnKeys() {
            return columnKeys.filter((key) => this.columnVisibility[key]);
        },

        columnIcon(key) {
            return COLUMN_ICON_PATHS[key] || null;
        },

        columnIconColor(key) {
            return headerIconClassForKey(key);
        },

        get selectedSet() {
            return new Set(this.selectedRowIds);
        },

        get filteredRows() {
            let items = this.rows.filter((row) => {
                const matchesColumn = Object.entries(this.filters).every(([key, value]) => {
                    if (!value) return true;
                    return String(row[key] ?? '').toLowerCase().includes(String(value).toLowerCase());
                });
                if (!matchesColumn) return false;

                if (this.advancedFilters.buildings.length && !this.advancedFilters.buildings.includes(row.building)) {
                    return false;
                }
                if (this.advancedFilters.statuses.length && !this.advancedFilters.statuses.includes(row.status)) {
                    return false;
                }

                if (this.advancedFilters.periodSession !== 'all') {
                    const start = periodStart(row.period);
                    if (start !== null) {
                        const session = this.advancedFilters.periodSession;
                        if (session === 'morning' && !(start >= 1 && start <= 6)) return false;
                        if (session === 'afternoon' && !(start >= 7 && start <= 12)) return false;
                        if (session === 'evening' && !(start >= 13 && start <= 17)) return false;
                        if (session === 'custom') {
                            const from = parseInt(this.advancedFilters.periodStart, 10);
                            const to = parseInt(this.advancedFilters.periodEnd, 10);
                            if (!Number.isNaN(from) && start < from) return false;
                            if (!Number.isNaN(to) && start > to) return false;
                        }
                    }
                }

                return true;
            });

            if (this.sortConfig) {
                const { key, direction } = this.sortConfig;
                items = [...items].sort((a, b) => {
                    const av = a[key];
                    const bv = b[key];
                    if (av == null) return 1;
                    if (bv == null) return -1;
                    const cmp = String(av).localeCompare(String(bv), 'vi', { numeric: true });
                    return direction === 'asc' ? cmp : -cmp;
                });
            }

            return items;
        },

        get normalizedRowsPerPage() {
            return normalizeRowsPerPage(this.rowsPerPage, 10);
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.normalizedRowsPerPage));
        },

        get safeCurrentPage() {
            return normalizeCurrentPage(this.currentPage, this.totalPages);
        },

        get currentItems() {
            const { items } = paginateSlice(
                this.filteredRows,
                this.safeCurrentPage,
                this.normalizedRowsPerPage,
            );
            return items;
        },

        get startIndex() {
            return paginateSlice(
                this.filteredRows,
                this.safeCurrentPage,
                this.normalizedRowsPerPage,
            ).startIndex;
        },

        init() {
            this.rowsPerPage = this.normalizedRowsPerPage;
            this.currentPage = this.safeCurrentPage;
            this.$watch('columnVisibility', (value) => writeStorage(colVisKey, value));
            this.$watch('filters', (value) => writeStorage('nttu_dash_schedules_filters', value));
            this.$watch('sortConfig', (value) => writeStorage('nttu_dash_schedules_sort', value));
            this.$watch('selectedRowIds', (value) => writeStorage('nttu_dash_schedules_selected', value));
            this.$watch('currentPage', (value) => writeStorage('nttu_dash_schedules_page', value));
            this.$watch('rowsPerPage', (value) => writeStoredRowsPerPage('nttu_dash_schedules_rpp', value));
            this.loadFilterPresets();
            if (this.advancedFilters.date && this.advancedFilters.date !== this.initialDateIso) {
                this.onAdvancedDateChange(this.advancedFilters.date);
            }
        },

        persistAdvancedFilters() {
            writeStorage(advKey, this.advancedFilters);
        },

        rowKey(row, index) {
            return `${row.id}-${index}`;
        },

        toggleRow(rowKey) {
            const next = new Set(this.selectedRowIds);
            if (next.has(rowKey)) next.delete(rowKey);
            else next.add(rowKey);
            this.selectedRowIds = Array.from(next);
        },

        isRowSelected(rowKey) {
            return this.selectedSet.has(rowKey);
        },

        toggleColumn(key) {
            this.columnVisibility[key] = !this.columnVisibility[key];
        },

        selectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(COLUMN_DEFS), true);
        },

        deselectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(COLUMN_DEFS), false);
        },

        setFilter(key, value) {
            this.filters = { ...this.filters, [key]: value };
            this.currentPage = 1;
        },

        clearFilter(key) {
            this.setFilter(key, '');
            this.headerPopover = null;
        },

        get hasActiveFilters() {
            if (Object.keys(this.filters).some((key) => this.filters[key])) {
                return true;
            }

            const adv = this.advancedFilters;

            return !!(adv.buildings?.length || adv.statuses?.length
                || (adv.date && adv.date !== this.initialDateIso));
        },

        clearAllFilters() {
            this.filters = {};
            this.sortConfig = null;
            this.headerPopover = null;
            this.resetAdvanced();
        },

        requestSort(key, direction) {
            this.sortConfig = { key, direction: direction === 'ascending' ? 'asc' : 'desc' };
            this.headerPopover = null;
        },

        clearSort() {
            this.sortConfig = null;
            this.headerPopover = null;
        },

        sortState(key) {
            if (!this.sortConfig || this.sortConfig.key !== key) return 'none';
            return this.sortConfig.direction;
        },

        isColumnFiltered(key) {
            return !!this.filters[key];
        },

        sortIconClass(key) {
            return this.isColumnFiltered(key) ? 'nttu-sort-filtered' : '';
        },

        async onAdvancedDateChange(isoDate) {
            this.advancedFilters = { ...this.advancedFilters, date: isoDate || '' };
            if (!isoDate || !this.lookupUrl) return;

            this.loading = true;
            try {
                const response = await fetch(`${this.lookupUrl}?date=${encodeURIComponent(isoDate)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('fetch failed');
                const data = await response.json();
                this.rows = data.rows || [];
                if (Array.isArray(data.buildings)) {
                    this.masterData = { ...this.masterData, buildings: data.buildings };
                }
                this.currentPage = 1;
                this.selectedRowIds = [];
            } catch (_) {
                alertMessage('Không tải được lịch học cho ngày đã chọn.', getLanguage());
            } finally {
                this.loading = false;
            }
        },

        resetAdvanced() {
            this.advancedFilters = normalizeDashboardAdvancedFilters({}, this.initialDateIso);
            this.onAdvancedDateChange(this.initialDateIso);
            this.persistAdvancedFilters();
        },

        applyAdvanced() {
            this.persistAdvancedFilters();
            this.filterOpen = false;
            this.currentPage = 1;
        },

        loadFilterPresetsLocal() {
            this.filterPresets = readStorage(presetKey, []);
        },

        async loadFilterPresets() {
            if (!this.presetsUrl) {
                this.loadFilterPresetsLocal();
                return;
            }
            try {
                const res = await fetch(this.presetsUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) {
                    const payload = await res.json();
                    this.filterPresets = Array.isArray(payload.presets) ? payload.presets : [];
                    writeStorage(presetKey, this.filterPresets);
                    return;
                }
            } catch (_) {}
            this.loadFilterPresetsLocal();
        },

        async persistPresetsToCloud() {
            if (!this.presetsUrl) {
                writeStorage(presetKey, this.filterPresets);
                return;
            }
            this.savingPresets = true;
            try {
                const res = await fetch(this.presetsUrl, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ presets: this.filterPresets }),
                });
                const payload = await res.json();
                if (!res.ok) {
                    throw new Error(payload.message || 'Không lưu được bộ lọc');
                }
                this.filterPresets = payload.presets || this.filterPresets;
                writeStorage(presetKey, this.filterPresets);
            } catch (err) {
                writeStorage(presetKey, this.filterPresets);
                throw err;
            } finally {
                this.savingPresets = false;
            }
        },

        async saveFilterPreset(name) {
            const trimmed = String(name || '').trim();
            if (!trimmed || this.savingPresets) return;
            const preset = {
                name: trimmed,
                filters: normalizeDashboardAdvancedFilters(this.advancedFilters, this.initialDateIso),
                createdAt: new Date().toISOString(),
            };
            this.filterPresets = [...this.filterPresets.filter((p) => p.name !== trimmed), preset];
            try {
                await this.persistPresetsToCloud();
                this.presetNaming = false;
                this.presetName = '';
            } catch (err) {
                alertMessage(err.message || 'Lỗi khi lưu bộ lọc', getLanguage());
            }
        },

        async deleteFilterPreset(name) {
            if (this.savingPresets) return;
            this.filterPresets = this.filterPresets.filter((p) => p.name !== name);
            try {
                await this.persistPresetsToCloud();
            } catch (err) {
                alertMessage(err.message || 'Lỗi khi xóa bộ lọc', getLanguage());
            }
        },

        startRenameFilterPreset(name) {
            this.presetRenaming = name;
            this.renamingPresetValue = name;
        },

        cancelRenameFilterPreset() {
            this.presetRenaming = null;
            this.renamingPresetValue = '';
        },

        async renameFilterPreset() {
            if (this.savingPresets) return;
            const oldName = this.presetRenaming;
            const trimmed = String(this.renamingPresetValue || '').trim();
            if (!oldName || !trimmed) {
                return;
            }
            if (trimmed !== oldName && this.filterPresets.some((p) => p.name === trimmed)) {
                alertMessage('Tên bộ lọc đã tồn tại', getLanguage());
                return;
            }
            this.filterPresets = this.filterPresets.map((p) =>
                p.name === oldName ? { ...p, name: trimmed } : p
            );
            try {
                await this.persistPresetsToCloud();
                this.cancelRenameFilterPreset();
            } catch (err) {
                alertMessage(err.message || 'Lỗi khi đổi tên bộ lọc', getLanguage());
            }
        },

        async applyFilterPreset(preset) {
            const filters = normalizeDashboardAdvancedFilters(preset?.filters || {}, this.initialDateIso);
            this.advancedFilters = filters;
            this.presetMenuOpen = false;
            this.currentPage = 1;
            this.persistAdvancedFilters();
            await this.onAdvancedDateChange(filters.date);
        },

        get exportHref() {
            if (!this.exportUrl) return '#';

            const params = new URLSearchParams();
            const dateIso = this.advancedFilters.date || this.initialDateIso;
            if (dateIso) {
                params.set('date', dateIso);
            }

            const f = this.advancedFilters;
            (f.buildings || []).forEach((building) => {
                if (building) params.append('buildings[]', building);
            });

            if (f.periodSession && f.periodSession !== 'all') {
                params.set('period_session', f.periodSession);
                if (f.periodSession === 'custom') {
                    if (f.periodStart !== '' && f.periodStart != null) {
                        params.set('period_start', String(f.periodStart));
                    }
                    if (f.periodEnd !== '' && f.periodEnd != null) {
                        params.set('period_end', String(f.periodEnd));
                    }
                }
            }

            const ids = this.filteredRows.map((row) => row.id).filter(Boolean);
            if (ids.length) {
                params.set('ids', ids.join(','));
            }

            const qs = params.toString();
            return qs ? `${this.exportUrl}?${qs}` : this.exportUrl;
        },

        cellValue(row, key) {
            return row[key] ?? '';
        },

        setRowsPerPage(value) {
            this.rowsPerPage = normalizeRowsPerPage(value, 10);
            this.currentPage = 1;
        },

        goToPage(page) {
            this.currentPage = normalizeCurrentPage(page, this.totalPages);
        },
    };
}

window.dashboardScheduleLookup = dashboardScheduleLookup;

function isAllowedShiftScheduleFile(file) {
    if (!file) {
        return false;
    }

    if (file.type.startsWith('image/') || file.type === 'application/pdf' || file.type === 'application/x-pdf') {
        return true;
    }

    return /\.(pdf|jpe?g|png|gif|webp)$/i.test(file.name || '');
}

export function registerShiftScheduleWidget(Alpine) {
    Alpine.data('shiftScheduleWidget', (config = {}) => ({
        expanded: Boolean(config.hasSchedule),
        uploading: false,
        hasSchedule: Boolean(config.hasSchedule),
        uploadUrl: config.uploadUrl || '',
        canEdit: config.canEdit !== false,

        pickFile() {
            if (!this.canEdit) return;
            const input = this.$refs.fileInput;
            if (!input) {
                return;
            }

            input.value = '';
            input.click();
        },

        async onFileSelected(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            if (!isAllowedShiftScheduleFile(file)) {
                alertMessage('Chỉ hỗ trợ file ảnh hoặc PDF.', getLanguage());
                event.target.value = '';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alertMessage('Kích thước file quá lớn (tối đa 10MB).', getLanguage());
                event.target.value = '';
                return;
            }

            this.uploading = true;
            this.expanded = true;

            const formData = new FormData();
            formData.append('shift_schedule', file);

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf) {
                formData.append('_token', csrf);
            }

            try {
                const response = await fetch(this.uploadUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: formData,
                });

                const contentType = response.headers.get('content-type') || '';
                const payload = contentType.includes('application/json')
                    ? await response.json()
                    : {};

                if (!response.ok) {
                    if (response.status === 419) {
                        throw new Error('Phiên đăng nhập hết hạn. Vui lòng tải lại trang và thử lại.');
                    }

                    const message = payload.message
                        || Object.values(payload.errors || {}).flat().join('\n')
                        || `Không thể tải lên lịch trực (mã ${response.status}).`;
                    throw new Error(message);
                }

                window.location.reload();
            } catch (error) {
                alertMessage(error.message || 'Không thể tải lên lịch trực.', getLanguage());
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
    }));

    Alpine.data('periodScheduleWidget', (config = {}) => ({
        uploading: false,
        uploadUrl: config.uploadUrl || '',
        canEdit: config.canEdit !== false,

        pickFile() {
            if (!this.canEdit) return;
            const input = this.$refs.fileInput;
            if (!input) {
                return;
            }

            input.value = '';
            input.click();
        },

        async onFileSelected(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/') && !/\.(jpe?g|png|gif|webp)$/i.test(file.name || '')) {
                alertMessage('Chỉ hỗ trợ file ảnh (JPG, PNG, WEBP, GIF).', getLanguage());
                event.target.value = '';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alertMessage('Kích thước file quá lớn (tối đa 10MB).', getLanguage());
                event.target.value = '';
                return;
            }

            this.uploading = true;

            const formData = new FormData();
            formData.append('period_schedule', file);

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf) {
                formData.append('_token', csrf);
            }

            try {
                const response = await fetch(this.uploadUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: formData,
                });

                const contentType = response.headers.get('content-type') || '';
                const payload = contentType.includes('application/json')
                    ? await response.json()
                    : {};

                if (!response.ok) {
                    if (response.status === 419) {
                        throw new Error('Phiên đăng nhập hết hạn. Vui lòng tải lại trang và thử lại.');
                    }

                    const message = payload.message
                        || Object.values(payload.errors || {}).flat().join('\n')
                        || `Không thể tải lên bảng tiết học (mã ${response.status}).`;
                    throw new Error(message);
                }

                window.location.reload();
            } catch (error) {
                alertMessage(error.message || 'Không thể tải lên bảng tiết học.', getLanguage());
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
    }));
}