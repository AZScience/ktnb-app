import { columnKeyIconPaths as columnIconPaths, headerIconClassForKey } from './nttu-icons.js';
import { toImportPayload } from './schedule-import-payload.js';
import { t, getLanguage } from './language.js';
import { createNttuMultiSelectMixin } from './nttu-multi-select.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice } from './nttu-pagination.js';

const COLUMNS = {
    date: 'Ngày',
    building: 'Dãy nhà',
    room: 'Phòng',
    period: 'Tiết',
    type: 'LT/TH',
    student_count: 'Sĩ số',
    department: 'Khoa sử dụng',
    class: 'Lớp',
    lecturer: 'Giảng viên',
    proctor1: 'CBCT 01',
    proctor2: 'CBCT 02',
    proctor3: 'CBCT 03',
    content: 'Nội dung',
    status: 'Trạng thái',
    note: 'Ghi chú',
};

const COLUMN_ORDER = Object.keys(COLUMNS);

const DEFAULT_VISIBILITY = {
    date: true,
    building: true,
    room: true,
    period: true,
    type: true,
    student_count: true,
    department: true,
    class: true,
    lecturer: true,
    proctor1: true,
    proctor2: false,
    proctor3: false,
    content: true,
    status: true,
    note: false,
};

function normalizeAdvancedFilters(raw, fallbackDate = '') {
    const f = raw || {};
    const hasDate = Object.prototype.hasOwnProperty.call(f, 'date');

    return {
        date: hasDate ? (f.date || '') : (fallbackDate || ''),
        buildings: Array.isArray(f.buildings) ? [...f.buildings] : [],
        departments: Array.isArray(f.departments) ? [...f.departments] : [],
        rooms: Array.isArray(f.rooms) ? [...f.rooms] : [],
        lecturers: Array.isArray(f.lecturers) ? [...f.lecturers] : [],
        periodSession: f.periodSession || 'all',
        periodStart: f.periodStart ?? '',
        periodEnd: f.periodEnd ?? '',
    };
}

function formatFilterDate(d) {
    if (!d) return '';
    if (d.includes('-')) {
        const [y, m, day] = d.split('-');
        return `${day}/${m}/${y}`;
    }
    return d;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export function registerDailyScheduleSettings(Alpine) {
    Alpine.data('dailyScheduleSettingsPage', (config) => {
        const storageKey = 'nttu_schedule_settings_colvis';
        const pageKey = 'nttu_schedule_settings_page';
        const rowsKey = 'nttu_schedule_settings_rows';
        const selKey = 'nttu_schedule_settings_selected';
        const advKey = 'nttu_schedule_settings_adv_filters';
        const presetKey = 'nttu_schedule_settings_filter_presets';
        const sortKey = 'nttu_schedule_settings_sort';
        const colFiltersKey = 'nttu_schedule_settings_col_filters';

        let savedVisibility = DEFAULT_VISIBILITY;
        let savedPage = 1;
        let savedRows = 10;
        let savedSelected = [];
        let savedAdvanced = null;
        let savedPresets = [];
        let savedSort = null;
        let savedColumnFilters = {};

        try {
            savedVisibility = { ...DEFAULT_VISIBILITY, ...JSON.parse(localStorage.getItem(storageKey) || '{}') };
            savedPage = normalizeCurrentPage(localStorage.getItem(pageKey) || '1', 1);
            savedRows = normalizeRowsPerPage(localStorage.getItem(rowsKey) || '10', 10);
            savedSelected = JSON.parse(localStorage.getItem(selKey) || '[]');
            savedAdvanced = JSON.parse(localStorage.getItem(advKey) || 'null');
            savedPresets = JSON.parse(localStorage.getItem(presetKey) || '[]');
            savedSort = JSON.parse(localStorage.getItem(sortKey) || 'null');
            savedColumnFilters = JSON.parse(localStorage.getItem(colFiltersKey) || '{}');
        } catch (_) {}

        const multiSelectMixin = createNttuMultiSelectMixin({
            resolveOptions(field) {
                const map = {
                    buildings: this.masterData.buildings || [],
                    departments: this.masterData.departments || [],
                    rooms: (this.masterData.rooms || []).map((room) => (typeof room === 'string' ? room : room.name)),
                    lecturers: this.masterData.lecturers || [],
                };
                const merged = [...new Set([...(map[field] || []), ...this.valuesFromItems(field)])];
                return merged.filter(Boolean).sort((a, b) => String(a).localeCompare(String(b), 'vi'));
            },
            setSelectedValues(field, values) {
                this.patchAdvancedFilters({ [field]: values });
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },
        });

        return {
            ...multiSelectMixin,
            items: config.initialItems || [],
            masterData: config.masterData || {},
            todayDate: config.todayDate || '',
            dataUrl: config.dataUrl || '',
            presetsUrl: config.presetsUrl || '',
            storeUrl: config.storeUrl || '',
            exportUrl: config.exportUrl || '',
            importUrl: config.importUrl || '',
            importPreviewUrl: config.importPreviewUrl || '',
            importBatchUrl: config.importBatchUrl || '',
            importTemplateUrl: config.importTemplateUrl || '',
            bulkDeleteUrl: config.bulkDeleteUrl || '',
            deleteByDateUrl: config.deleteByDateUrl || '',
            canAdd: config.canAdd !== false,
            canEdit: config.canEdit !== false,
            canDelete: config.canDelete !== false,
            canImport: config.canImport !== false,
            canExport: config.canExport !== false,
            loading: false,
            saving: false,
            exporting: false,
            toast: null,
            modalOpen: false,
            modalMode: 'add',
            advancedOpen: false,
            settingsOpen: false,
            headerPopover: null,
            rowMenuOpen: null,
            deleteOpen: false,
            bulkDeleteOpen: false,
            deleteByDateOpen: false,
            deleteTarget: null,
            deleteDate: '',
            showDeleteAction: false,
            currentLang: getLanguage(),
            columnFilters: savedColumnFilters,
            sortConfig: savedSort,
            columnVisibility: savedVisibility,
            currentPage: savedPage,
            rowsPerPage: savedRows,
            selectedRowIds: savedSelected,
            filterPresets: savedPresets,
            presetNaming: false,
            presetName: '',
            presetRenaming: null,
            renamingPresetValue: '',
            savingPresets: false,
            presetMenuOpen: false,
            comboOpen: {},
            comboSearch: {},
            importPreviewOpen: false,
            importPreviewRows: [],
            importNewItems: [],
            importStats: { rawCount: 0, mergedCount: 0, newCount: 0 },
            processingImport: false,
            advancedFilters: normalizeAdvancedFilters(
                savedAdvanced ?? {
                    date: config.todayDate,
                    buildings: [],
                    departments: [],
                    rooms: [],
                    lecturers: [],
                    periodSession: 'all',
                    periodStart: '',
                    periodEnd: '',
                },
                config.todayDate,
            ),
            form: {},
            initialForm: {},
            columns: COLUMNS,
            columnOrder: COLUMN_ORDER,
            columnIconPaths,

            async init() {
                this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 10);
                this.currentPage = normalizeCurrentPage(this.currentPage, this.totalPages);
                window.addEventListener('nttu-language-changed', (event) => {
                    this.currentLang = event.detail?.language || getLanguage();
                });
                void this.loadFilterPresets();
                await this.fetchData();
            },

            buildDataUrl() {
                if (!this.dataUrl) {
                    return '';
                }

                const params = new URLSearchParams();
                const date = this.advancedFilters.date;
                if (date) {
                    let normalized = date;
                    if (date.includes('-')) {
                        const [y, m, d] = date.split('-');
                        normalized = `${d}/${m}/${y}`;
                    }
                    params.set('date', normalized);
                } else {
                    params.set('date', this.todayDate || new Date().toLocaleDateString('vi-VN'));
                }

                return `${this.dataUrl}?${params.toString()}`;
            },

            mergeImportedItems(items) {
                if (!Array.isArray(items) || !items.length) {
                    return;
                }

                const existingIds = new Set(this.items.map((item) => item.id));
                const merged = items.filter((item) => item?.id && !existingIds.has(item.id));
                if (merged.length) {
                    this.items = [...merged, ...this.items];
                }
            },

            async fetchData() {
                const url = this.buildDataUrl();
                if (!url) return;
                this.loading = true;
                try {
                    const res = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('Không tải được lịch học');
                    const payload = await res.json();
                    this.items = payload.items || [];
                } catch (err) {
                    this.showToast(err.message || 'Lỗi tải dữ liệu', 'error');
                } finally {
                    this.loading = false;
                }
            },

            showToast(message, type = 'success') {
                this.toast = { message, type };
                setTimeout(() => { this.toast = null; }, 30000);
            },

            get visibleColumnKeys() {
                return this.columnOrder.filter((k) => this.columns[k] && this.columnVisibility[k]);
            },

            get filteredItems() {
                let result = this.items.filter((item) => this.matchesAdvanced(item) && this.matchesColumnFilters(item));
                if (this.sortConfig) {
                    const { key, direction } = this.sortConfig;
                    result = [...result].sort((a, b) => {
                        const av = a[key] ?? '';
                        const bv = b[key] ?? '';
                        if (av === null || av === undefined || av === '') return 1;
                        if (bv === null || bv === undefined || bv === '') return -1;
                        const cmp = String(av).localeCompare(String(bv), 'vi', { numeric: true });
                        return direction === 'asc' ? cmp : -cmp;
                    });
                }
                return result;
            },

            get normalizedRowsPerPage() {
                return normalizeRowsPerPage(this.rowsPerPage, 10);
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredItems.length / this.normalizedRowsPerPage));
            },

            get safeCurrentPage() {
                return normalizeCurrentPage(this.currentPage, this.totalPages);
            },

            get paginatedItems() {
                return paginateSlice(
                    this.filteredItems,
                    this.currentPage,
                    this.normalizedRowsPerPage,
                ).items;
            },

            get startIndex() {
                return paginateSlice(
                    this.filteredItems,
                    this.currentPage,
                    this.normalizedRowsPerPage,
                ).startIndex;
            },

            get visibleSelectedCount() {
                const ids = new Set(this.selectedRowIds);
                return this.filteredItems.filter((item) => ids.has(item.id)).length;
            },

            get hasActiveAdvancedFilters() {
                const f = this.advancedFilters;
                return !!(f.date || f.buildings?.length || f.departments?.length || f.rooms?.length
                    || f.lecturers?.length || (f.periodSession && f.periodSession !== 'all'));
            },

            get modalTitle() {
                void this.currentLang;
                const lang = this.currentLang;
                if (this.modalMode === 'view') return t('Chi tiết Lịch học', lang);
                if (this.modalMode === 'edit') return t('Chỉnh sửa Lịch học', lang);
                if (this.modalMode === 'copy') return t('Sao chép Lịch học', lang);
                return t('Thêm mới Lịch học', lang);
            },

            columnLabel(key) {
                void this.currentLang;
                const label = this.columns[key];
                return label ? (t(label, this.currentLang) || label) : key;
            },

            get isViewMode() {
                return this.modalMode === 'view';
            },

            get isNoteFieldEditable() {
                return !this.isViewMode;
            },

            get isClassFieldEditable() {
                return !this.isViewMode;
            },

            get isChanged() {
                return JSON.stringify(this.form) !== JSON.stringify(this.initialForm);
            },

            get filteredRoomNames() {
                const rooms = this.masterData.rooms || [];
                const building = this.form.building;
                const blockMap = this.masterData.blockMap || {};
                if (!building) {
                    return rooms.map((r) => (typeof r === 'string' ? r : r.name));
                }
                const blockId = blockMap[building];
                const filtered = blockId
                    ? rooms.filter((r) => typeof r === 'string' || r.building_block_id === blockId)
                    : rooms;
                return filtered.map((r) => (typeof r === 'string' ? r : r.name));
            },

            get exportHref() {
                if (!this.exportUrl) return '#';

                const params = new URLSearchParams();
                const f = this.advancedFilters;

                if (f.date) {
                    params.set(
                        'date',
                        f.date.includes('/') ? f.date.split('/').reverse().join('-') : f.date,
                    );
                }

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

                const ids = this.filteredItems.map((item) => item.id);
                if (ids.length) {
                    params.set('ids', ids.join(','));
                }

                const qs = params.toString();
                return qs ? `${this.exportUrl}?${qs}` : this.exportUrl;
            },

            columnIcon(key) {
                return columnIconPaths[key] || null;
            },

            columnIconColor(key) {
                return headerIconClassForKey(key);
            },

            sortDirection(key) {
                if (this.sortConfig?.key !== key) return null;
                return this.sortConfig.direction;
            },

            isColumnFiltered(key) {
                return !!this.columnFilters[key];
            },

            sortIconClass(key) {
                return this.isColumnFiltered(key) ? 'nttu-sort-filtered' : '';
            },

            buildingMatchesFilter(item, filterValue) {
                const candidates = [item?.building, item?.building_label]
                    .filter((value) => value !== null && value !== undefined && String(value).trim() !== '')
                    .map((value) => String(value));
                const filter = String(filterValue ?? '').trim();
                if (!filter) return true;
                return candidates.some((value) => value === filter);
            },

            cellValue(item, key) {
                const val = item?.[key];
                if (key === 'building') {
                    const label = item?.building_label;
                    if (label !== null && label !== undefined && label !== '') {
                        return label;
                    }
                }
                if (key === 'room') {
                    const label = item?.room_label;
                    if (label !== null && label !== undefined && label !== '') {
                        return label;
                    }
                }
                if (val === null || val === undefined || val === '') return '';
                return val;
            },

            openAdvancedFilter() {
                this.multiSelectOpenField = null;
                this.multiSelectSearch = { buildings: '', departments: '', rooms: '', lecturers: '' };
                this.advancedOpen = true;
            },

            closeAdvancedFilter() {
                this.advancedOpen = false;
            },

            persistAdvancedFilters() {
                localStorage.setItem(advKey, JSON.stringify(this.advancedFilters));
            },

            patchAdvancedFilters(patch) {
                this.advancedFilters = normalizeAdvancedFilters(
                    { ...this.advancedFilters, ...patch },
                    this.todayDate,
                );
                this.persistAdvancedFilters();
            },

            onPeriodSessionChange(value) {
                this.patchAdvancedFilters({ periodSession: value });
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },

            onPeriodRangeChange(field, value) {
                this.patchAdvancedFilters({ [field]: value });
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },

            onAdvancedDateChange(isoDate) {
                let date = '';
                if (isoDate) {
                    const [y, m, day] = isoDate.split('-');
                    date = `${day}/${m}/${y}`;
                }
                this.patchAdvancedFilters({ date });
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },

            async applyAdvanced() {
                this.persistAdvancedFilters();
                this.advancedOpen = false;
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
                await this.fetchData();
                this.showToast(`Đã áp dụng bộ lọc (${this.filteredItems.length} bản ghi)`);
            },

            matchesColumnFilters(item) {
                return Object.entries(this.columnFilters).every(([key, value]) => {
                    if (!value) return true;
                    return String(item[key] ?? '').toLowerCase().includes(String(value).toLowerCase());
                });
            },

            matchesAdvanced(item) {
                const f = this.advancedFilters;
                if (f.date) {
                    let filterDate = f.date;
                    if (filterDate.includes('-')) {
                        const [y, m, d] = filterDate.split('-');
                        filterDate = `${d}/${m}/${y}`;
                    }
                    if (item.date !== filterDate) return false;
                }
                if (f.buildings?.length && !f.buildings.some((building) => this.buildingMatchesFilter(item, building))) return false;
                if (f.departments?.length && !f.departments.includes(item.department)) return false;
                if (f.rooms?.length && !f.rooms.includes(item.room)) return false;
                if (f.lecturers?.length) {
                    const lecs = [item.lecturer, item.proctor1, item.proctor2, item.proctor3].filter(Boolean);
                    if (!f.lecturers.some((l) => lecs.includes(l))) return false;
                }
                if (f.periodSession !== 'all') {
                    const parts = String(item.period || '').split(/→|->/).map((p) => parseInt(p.trim(), 10));
                    const start = parts[0];
                    if (Number.isNaN(start)) return false;
                    if (f.periodSession === 'morning' && (start < 1 || start > 6)) return false;
                    if (f.periodSession === 'afternoon' && (start < 7 || start > 12)) return false;
                    if (f.periodSession === 'evening' && (start < 13 || start > 17)) return false;
                    if (f.periodSession === 'custom') {
                        const pStart = parseInt(f.periodStart, 10);
                        const pEnd = parseInt(f.periodEnd, 10);
                        if (!Number.isNaN(pStart) && start < pStart) return false;
                        if (!Number.isNaN(pEnd) && start > pEnd) return false;
                    }
                }
                return true;
            },

            setColumnFilter(key, value) {
                this.columnFilters = { ...this.columnFilters, [key]: value };
                this.currentPage = 1;
                localStorage.setItem(colFiltersKey, JSON.stringify(this.columnFilters));
                localStorage.setItem(pageKey, '1');
            },

            clearColumnFilter(key) {
                this.setColumnFilter(key, '');
                this.headerPopover = null;
            },

            requestSort(key, direction) {
                this.sortConfig = { key, direction };
                localStorage.setItem(sortKey, JSON.stringify(this.sortConfig));
                this.headerPopover = null;
            },

            clearSort() {
                this.sortConfig = null;
                localStorage.removeItem(sortKey);
                this.headerPopover = null;
            },

            toggleColumn(key) {
                this.columnVisibility[key] = !this.columnVisibility[key];
                localStorage.setItem(storageKey, JSON.stringify(this.columnVisibility));
            },

            selectAllColumns() {
                this.columnVisibility = allColumnsVisibleMap(this.columnOrder, true);
                localStorage.setItem(storageKey, JSON.stringify(this.columnVisibility));
            },

            deselectAllColumns() {
                this.columnVisibility = allColumnsVisibleMap(this.columnOrder, false);
                localStorage.setItem(storageKey, JSON.stringify(this.columnVisibility));
            },

            setRowsPerPage(value) {
                this.rowsPerPage = normalizeRowsPerPage(value, 10);
                this.currentPage = 1;
                localStorage.setItem(rowsKey, String(this.rowsPerPage));
                localStorage.setItem(pageKey, '1');
            },

            goToPage(page) {
                this.currentPage = normalizeCurrentPage(page, this.totalPages);
                localStorage.setItem(pageKey, String(this.currentPage));
            },

            handleRowClick(item) {
                const idx = this.selectedRowIds.indexOf(item.id);
                if (idx >= 0) this.selectedRowIds.splice(idx, 1);
                else this.selectedRowIds.push(item.id);
                localStorage.setItem(selKey, JSON.stringify(this.selectedRowIds));
            },

            isRowSelected(id) {
                return this.selectedRowIds.includes(id);
            },

            async clearAllFilters() {
                this.columnFilters = {};
                this.sortConfig = null;
                localStorage.removeItem(colFiltersKey);
                localStorage.removeItem(sortKey);
                this.resetAdvanced();
                await this.fetchData();
                this.showToast('Đã xóa tất cả bộ lọc');
            },

            async resetAdvanced() {
                this.advancedFilters = normalizeAdvancedFilters({ date: '' }, this.todayDate);
                localStorage.removeItem(advKey);
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },

            valuesFromItems(field) {
                const keyMap = {
                    buildings: 'building',
                    departments: 'department',
                    rooms: 'room',
                    lecturers: 'lecturer',
                };
                const key = keyMap[field];
                if (!key) return [];
                const set = new Set();
                (this.items || []).forEach((item) => {
                    if (key === 'lecturer') {
                        [item.lecturer, item.proctor1, item.proctor2, item.proctor3].filter(Boolean).forEach((value) => set.add(String(value)));
                    } else {
                        const val = item?.[key];
                        if (val) set.add(String(val));
                    }
                });
                return [...set];
            },

            openCombo(field) {
                this.comboOpen = { ...this.comboOpen, [field]: true };
            },

            closeCombo(field) {
                this.comboOpen = { ...this.comboOpen, [field]: false };
                this.comboSearch = { ...this.comboSearch, [field]: '' };
            },

            setCombo(field, value) {
                this.form[field] = value;
                if (field === 'building') this.form.room = '';
                this.closeCombo(field);
            },

            getComboOptions(field) {
                const search = (this.comboSearch[field] || '').toLowerCase();
                let opts = [];
                if (field === 'building') opts = this.masterData.buildings || [];
                else if (field === 'room') opts = this.filteredRoomNames;
                else if (field === 'department') opts = this.masterData.departments || [];
                else if (['lecturer', 'proctor1', 'proctor2', 'proctor3'].includes(field)) opts = this.masterData.lecturers || [];
                if (!search) return opts;
                return opts.filter((o) => String(o).toLowerCase().includes(search));
            },

            confirmComboNew(field) {
                const value = (this.comboSearch[field] || '').trim();
                if (value) this.setCombo(field, value);
            },

            buildFormFromItem(item, mode) {
                const base = {
                    id: mode === 'copy' || mode === 'add' ? null : item?.id,
                    date: item?.date || this.todayDate,
                    building: item?.building || '',
                    room: item?.room || '',
                    period: item?.period || '',
                    type: item?.type || '',
                    department: item?.department || '',
                    class: item?.class || '',
                    student_count: item?.student_count ?? '',
                    lecturer: item?.lecturer || '',
                    proctor1: item?.proctor1 || '',
                    proctor2: item?.proctor2 || '',
                    proctor3: item?.proctor3 || '',
                    content: item?.content || '',
                    status: item?.status || 'Phòng học',
                    incident: mode === 'copy' ? '' : (item?.incident || ''),
                    incident_detail: mode === 'copy' ? '' : (item?.incident_detail || ''),
                    note: mode === 'copy' ? '' : (item?.note || ''),
                };
                return base;
            },

            openModal(mode, item) {
                if (mode === 'add' || mode === 'copy') {
                    if (!this.canAdd) return;
                } else if (mode === 'edit') {
                    if (!this.canEdit) return;
                }
                this.modalMode = mode;
                this.rowMenuOpen = null;
                this.form = { ...this.buildFormFromItem(item, mode) };
                this.initialForm = JSON.parse(JSON.stringify(this.form));
                this.modalOpen = true;
            },

            openAddModal() {
                this.openModal('add', null);
            },

            closeModal() {
                this.modalOpen = false;
            },

            undoForm() {
                this.form = JSON.parse(JSON.stringify(this.initialForm));
                this.showToast('Đã hoàn tác dữ liệu');
            },

            confirmDelete(item) {
                if (!this.canDelete) return;
                this.deleteTarget = item;
                this.deleteOpen = true;
                this.rowMenuOpen = null;
            },

            confirmBulkDelete() {
                if (!this.selectedRowIds.length) return;
                this.bulkDeleteOpen = true;
            },

            confirmDeleteByDate() {
                this.deleteDate = this.advancedFilters.date || this.todayDate;
                this.deleteByDateOpen = true;
            },

            async deleteItem() {
                if (!this.deleteTarget) return;
                this.saving = true;
                try {
                    const res = await fetch(`${this.storeUrl}/${this.deleteTarget.id}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await res.json();
                    if (!res.ok) throw new Error(payload.message || 'Không xóa được');
                    this.items = this.items.filter((i) => i.id !== this.deleteTarget.id);
                    this.selectedRowIds = this.selectedRowIds.filter((id) => id !== this.deleteTarget.id);
                    localStorage.setItem(selKey, JSON.stringify(this.selectedRowIds));
                    this.deleteOpen = false;
                    this.deleteTarget = null;
                    this.showToast(payload.message || 'Đã xóa');
                } catch (err) {
                    this.showToast(err.message || 'Lỗi xóa', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async bulkDelete() {
                if (!this.selectedRowIds.length || !this.bulkDeleteUrl) return;
                this.saving = true;
                try {
                    const res = await fetch(this.bulkDeleteUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ ids: [...this.selectedRowIds] }),
                    });
                    const payload = await res.json();
                    if (!res.ok) throw new Error(payload.message || 'Không xóa được');
                    const removed = new Set(this.selectedRowIds);
                    this.items = this.items.filter((i) => !removed.has(i.id));
                    this.selectedRowIds = [];
                    localStorage.setItem(selKey, '[]');
                    this.bulkDeleteOpen = false;
                    this.showToast(payload.message || 'Đã xóa');
                } catch (err) {
                    this.showToast(err.message || 'Lỗi xóa', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async deleteByDate() {
                if (!this.deleteByDateUrl || !this.deleteDate) return;
                this.saving = true;
                try {
                    const res = await fetch(this.deleteByDateUrl, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ date: formatFilterDate(this.deleteDate) || this.deleteDate }),
                    });
                    const payload = await res.json();
                    if (!res.ok) throw new Error(payload.message || 'Không xóa được');
                    const date = formatFilterDate(this.deleteDate) || this.deleteDate;
                    this.items = this.items.filter((i) => i.date !== date);
                    this.selectedRowIds = [];
                    localStorage.setItem(selKey, '[]');
                    this.deleteByDateOpen = false;
                    this.showToast(payload.message || 'Đã xóa');
                } catch (err) {
                    this.showToast(err.message || 'Lỗi xóa', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async saveForm() {
                this.saving = true;
                try {
                    const isEdit = this.modalMode === 'edit' && this.form.id;
                    const url = isEdit ? `${this.storeUrl}/${this.form.id}` : this.storeUrl;
                    const method = isEdit ? 'PUT' : 'POST';
                    const res = await fetch(url, {
                        method,
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(this.form),
                    });
                    const payload = await res.json();
                    if (!res.ok) {
                        const msg = payload.message
                            || (payload.errors && Object.values(payload.errors).flat()[0])
                            || 'Không lưu được';
                        throw new Error(msg);
                    }
                    const item = payload.item;
                    if (isEdit) {
                        const idx = this.items.findIndex((i) => i.id === item.id);
                        if (idx >= 0) this.items[idx] = item;
                    } else {
                        this.items.unshift(item);
                    }
                    this.modalOpen = false;
                    this.showToast(payload.message || 'Đã lưu');
                } catch (err) {
                    this.showToast(err.message || 'Lỗi lưu', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async loadFilterPresets() {
                if (!this.presetsUrl) {
                    try {
                        this.filterPresets = JSON.parse(localStorage.getItem(presetKey) || '[]');
                    } catch (_) {
                        this.filterPresets = [];
                    }
                    return;
                }
                try {
                    const res = await fetch(this.presetsUrl, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (res.ok) {
                        const payload = await res.json();
                        this.filterPresets = Array.isArray(payload.presets) ? payload.presets : [];
                        localStorage.setItem(presetKey, JSON.stringify(this.filterPresets));
                    }
                } catch (_) {}
            },

            async persistPresetsToCloud() {
                if (!this.presetsUrl) {
                    localStorage.setItem(presetKey, JSON.stringify(this.filterPresets));
                    return;
                }
                this.savingPresets = true;
                try {
                    const res = await fetch(this.presetsUrl, {
                        method: 'PUT',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ presets: this.filterPresets }),
                    });
                    const payload = await res.json();
                    if (!res.ok) throw new Error(payload.message || 'Không lưu được bộ lọc');
                    this.filterPresets = payload.presets || this.filterPresets;
                    localStorage.setItem(presetKey, JSON.stringify(this.filterPresets));
                } finally {
                    this.savingPresets = false;
                }
            },

            async saveFilterPreset(name) {
                const trimmed = String(name || '').trim();
                if (!trimmed || this.savingPresets) return;
                const preset = {
                    name: trimmed,
                    filters: normalizeAdvancedFilters(this.advancedFilters, this.todayDate),
                    createdAt: new Date().toISOString(),
                };
                this.filterPresets = [...this.filterPresets.filter((p) => p.name !== trimmed), preset];
                try {
                    await this.persistPresetsToCloud();
                    this.presetNaming = false;
                    this.presetName = '';
                    this.showToast(`Đã lưu bộ lọc "${trimmed}"`);
                } catch (err) {
                    this.showToast(err.message || 'Lỗi khi lưu bộ lọc', 'error');
                }
            },

            async deleteFilterPreset(name) {
                if (this.savingPresets) return;
                this.filterPresets = this.filterPresets.filter((p) => p.name !== name);
                try {
                    await this.persistPresetsToCloud();
                    this.showToast('Đã xóa bộ lọc');
                } catch (err) {
                    this.showToast(err.message || 'Lỗi khi xóa bộ lọc', 'error');
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
                    this.showToast('Tên bộ lọc đã tồn tại', 'error');
                    return;
                }
                this.filterPresets = this.filterPresets.map((p) =>
                    p.name === oldName ? { ...p, name: trimmed } : p
                );
                try {
                    await this.persistPresetsToCloud();
                    this.cancelRenameFilterPreset();
                    this.showToast(`Đã đổi tên thành "${trimmed}"`);
                } catch (err) {
                    this.showToast(err.message || 'Lỗi khi đổi tên bộ lọc', 'error');
                }
            },

            applyFilterPreset(preset) {
                const filters = normalizeAdvancedFilters(preset?.filters || {}, this.todayDate);
                if (filters.date && filters.date.includes('-')) {
                    const [y, m, day] = filters.date.split('-');
                    filters.date = `${day}/${m}/${y}`;
                }
                this.advancedFilters = filters;
                this.persistAdvancedFilters();
                this.presetMenuOpen = false;
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
                this.showToast(`Đã tải bộ lọc "${preset.name}" (${this.filteredItems.length} bản ghi)`);
            },

            async handleImportFile(event) {
                const file = event.target.files?.[0];
                event.target.value = '';
                if (!file || !this.importPreviewUrl) return;

                this.loading = true;
                try {
                    const formData = new FormData();
                    formData.append('file', file);

                    const res = await fetch(this.importPreviewUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    let payload = {};
                    try {
                        payload = await res.json();
                    } catch (_) {
                        throw new Error('Phản hồi xem trước import không hợp lệ.');
                    }

                    if (!res.ok) {
                        throw new Error(payload.message || 'Không đọc được dữ liệu từ file Excel.');
                    }

                    this.importPreviewRows = payload.preview || [];
                    this.importNewItems = (payload.newItems || [])
                        .map((row) => toImportPayload({
                            ...row,
                            studentCount: row.student_count ?? row.studentCount,
                        }))
                        .filter(Boolean);
                    this.importStats = {
                        rawCount: payload.rawCount || 0,
                        mergedCount: payload.mergedCount || 0,
                        newCount: payload.newCount || this.importNewItems.length,
                    };
                    this.importPreviewOpen = true;
                } catch (err) {
                    this.showToast(err.message || 'Không đọc được dữ liệu từ file Excel.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            closeImportPreview() {
                this.importPreviewOpen = false;
                this.importPreviewRows = [];
                this.importNewItems = [];
                this.importStats = { rawCount: 0, mergedCount: 0, newCount: 0 };
            },

            async processImport() {
                if (!this.importBatchUrl) {
                    return;
                }

                if (!this.importNewItems.length) {
                    this.showToast(
                        this.importStats.mergedCount
                            ? `Không có bản ghi mới (Trùng ${this.importStats.mergedCount} bản ghi).`
                            : 'Không có dữ liệu để import.',
                        'error',
                    );
                    this.closeImportPreview();
                    return;
                }

                this.processingImport = true;
                try {
                    const res = await fetch(this.importBatchUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ items: this.importNewItems }),
                    });

                    let payload = {};
                    try {
                        payload = await res.json();
                    } catch (_) {
                        throw new Error('Phản hồi lưu import không hợp lệ.');
                    }

                    if (!res.ok) {
                        throw new Error(payload.message || 'Import thất bại');
                    }

                    this.mergeImportedItems(payload.items || []);
                    this.closeImportPreview();
                    this.showToast(payload.message || `Đã thêm ${payload.imported || this.importNewItems.length} bản ghi mới.`);
                } catch (err) {
                    this.showToast(err.message || 'Lỗi import', 'error');
                } finally {
                    this.processingImport = false;
                }
            },
        };
    });
}
