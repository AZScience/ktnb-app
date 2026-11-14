import { columnKeyIconPaths as columnIconPaths, headerIconClassForKey } from './nttu-icons.js';
import { toImportPayload } from './schedule-import-payload.js';
import { t, getLanguage } from './language.js';
import { createNttuMultiSelectMixin } from './nttu-multi-select.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice } from './nttu-pagination.js';
import { submitExportPost } from './export-form-helper.js';
import { multiSortRows, normalizeSortRules, applySortRule, removeSortRule } from './nttu-table-sort.js';

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

function toDisplayDate(val) {
    if (!val) return '';
    const s = String(val).trim().split(' ')[0];
    if (s.includes('-')) {
        const parts = s.split('-');
        if (parts.length === 3) {
            const y = parts[0];
            const m = parts[1].padStart(2, '0');
            const d = parts[2].padStart(2, '0');
            return `${d}/${m}/${y}`;
        }
    }
    return s;
}

function toIsoDate(val) {
    if (!val) return '';
    const s = String(val).trim().split(' ')[0];
    if (s.includes('/')) {
        const parts = s.split('/');
        if (parts.length === 3) {
            const d = parts[0].padStart(2, '0');
            const m = parts[1].padStart(2, '0');
            const y = parts[2];
            return `${y}-${m}-${d}`;
        }
    }
    return s;
}

function dateToNumber(dStr) {
    if (!dStr) return 0;
    const s = String(dStr).trim().split(' ')[0];
    if (s.includes('/')) {
        const [d, m, y] = s.split('/');
        return parseInt(`${y}${m.padStart(2, '0')}${d.padStart(2, '0')}`, 10) || 0;
    }
    if (s.includes('-')) {
        const [y, m, d] = s.split('-');
        return parseInt(`${y}${m.padStart(2, '0')}${d.padStart(2, '0')}`, 10) || 0;
    }
    return 0;
}

function normalizeAdvancedFilters(raw, fallbackDate = '') {
    const f = raw || {};
    const fallback = toDisplayDate(fallbackDate) || '';
    const rawDate = toDisplayDate(f.date);
    const rawFrom = toDisplayDate(f.fromDate);
    const rawTo = toDisplayDate(f.toDate);

    const fromDate = rawFrom || rawDate || fallback;
    const toDate = rawTo || rawDate || fallback;

    return {
        date: fromDate,
        fromDate,
        toDate,
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
        const selFilterKey = 'nttu_schedule_settings_sel_filter';

        let savedVisibility = DEFAULT_VISIBILITY;
        let savedPage = 1;
        let savedRows = 10;
        let savedSelected = [];
        let savedSelectionFilter = 'all';
        let savedAdvanced = null;
        let savedPresets = [];
        let savedSort = null;
        let savedColumnFilters = {};

        try {
            savedVisibility = { ...DEFAULT_VISIBILITY, ...JSON.parse(localStorage.getItem(storageKey) || '{}') };
            savedPage = normalizeCurrentPage(localStorage.getItem(pageKey) || '1', 1);
            savedRows = normalizeRowsPerPage(localStorage.getItem(rowsKey) || '10', 10);
            savedSelected = JSON.parse(localStorage.getItem(selKey) || '[]');
            savedSelectionFilter = localStorage.getItem(selFilterKey) || 'all';
            savedAdvanced = JSON.parse(localStorage.getItem(advKey) || 'null');
            savedPresets = JSON.parse(localStorage.getItem(presetKey) || '[]');
            savedSort = normalizeSortRules(JSON.parse(localStorage.getItem(sortKey) || 'null'));
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
            deleteToDate: '',
            showDeleteAction: false,
            currentLang: getLanguage(),
            columnFilters: savedColumnFilters,
            sortConfig: savedSort,
            columnVisibility: savedVisibility,
            currentPage: savedPage,
            rowsPerPage: savedRows,
            selectedRowIds: savedSelected,
            selectionFilter: savedSelectionFilter,
            selectionPopover: false,
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
                this.$watch('advancedOpen', (v) => {
                    if (v) this.$nextTick(() => this.decorateFilterModal());
                });
                this.$nextTick(() => {
                    this.decorateQuickDateBar();
                    this.ensureSelectionFilterHeader();
                });
                void this.loadFilterPresets();
                await this.fetchData();
            },

            decorateQuickDateBar() {
                document.querySelectorAll('button').forEach((btn) => {
                    const text = btn.textContent.trim().toLowerCase();
                    if (text.includes('lọc dữ liệu') && !btn.querySelector('svg')) {
                        btn.classList.add('inline-flex', 'items-center', 'gap-1');
                        btn.insertAdjacentHTML('afterbegin', `<svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>`);
                    }
                });
            },

            buildDataUrl() {
                if (!this.dataUrl) {
                    return '';
                }

                const params = new URLSearchParams();
                const f = this.advancedFilters;
                const from = toDisplayDate(f.fromDate || f.date);
                const to = toDisplayDate(f.toDate || f.fromDate || f.date);

                if (from && to) {
                    params.set('from', from);
                    params.set('to', to);
                    if (from === to) {
                        params.set('date', from);
                    } else {
                        params.set('all', '1');
                    }
                } else if (from) {
                    params.set('date', from);
                    params.set('from', from);
                } else {
                    const today = this.todayDate || new Date().toLocaleDateString('vi-VN');
                    params.set('date', today);
                    params.set('from', today);
                    params.set('to', today);
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

            toIsoDate(d) {
                return toIsoDate(d);
            },

            toDisplayDate(d) {
                return toDisplayDate(d);
            },

            get dateRangeLabel() {
                const f = this.advancedFilters;
                const from = toDisplayDate(f.fromDate || f.date);
                const to = toDisplayDate(f.toDate || f.fromDate || f.date);
                if (from && to && from !== to) {
                    return `${from} – ${to}`;
                }
                return from || to || this.todayDate || '';
            },

            async applyQuickDate() {
                this.persistAdvancedFilters();
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
                await this.fetchData();
                this.showToast(`Đã lọc lịch học: ${this.dateRangeLabel} (${this.filteredItems.length} bản ghi)`);
            },

            get filteredItems() {
                let result = this.items.filter((item) => {
                    if (this.selectionFilter === 'selected' && !this.selectedRowIds.includes(item.id)) {
                        return false;
                    }
                    if (this.selectionFilter === 'unselected' && this.selectedRowIds.includes(item.id)) {
                        return false;
                    }
                    return this.matchesAdvanced(item) && this.matchesColumnFilters(item);
                });
                return multiSortRows(result, this.activeSortRules, ['period', 'room', 'id']);
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
                if (this.selectionFilter && this.selectionFilter !== 'all') return true;
                const f = this.advancedFilters;
                return !!(f.fromDate || f.toDate || f.date || f.buildings?.length || f.departments?.length || f.rooms?.length
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
                const from = toDisplayDate(f.fromDate || f.date);
                const to = toDisplayDate(f.toDate || f.fromDate || f.date);

                if (from && to) {
                    params.set('from', from);
                    params.set('to', to);
                    params.set('date', from);
                } else if (from) {
                    params.set('date', from);
                    params.set('from', from);
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

                const qs = params.toString();
                return qs ? `${this.exportUrl}?${qs}` : this.exportUrl;
            },

            exportExcel(event) {
                if (!this.exportUrl) return;
                if (event) event.preventDefault();

                const isCustomFiltered = this.selectionFilter === 'selected' || (this.items && this.filteredItems.length < this.items.length);
                const ids = isCustomFiltered ? this.filteredItems.map((item) => item.id).filter(Boolean) : [];

                const f = this.advancedFilters;
                const from = toDisplayDate(f.fromDate || f.date);
                const to = toDisplayDate(f.toDate || f.fromDate || f.date);
                const payload = {
                    from: from || '',
                    to: to || from || '',
                    date: from || '',
                    buildings: f.buildings || [],
                };
                if (ids.length > 0) {
                    payload.ids = ids.join(',');
                }
                if (f.periodSession && f.periodSession !== 'all') {
                    payload.period_session = f.periodSession;
                    if (f.periodSession === 'custom') {
                        if (f.periodStart !== '' && f.periodStart != null) payload.period_start = String(f.periodStart);
                        if (f.periodEnd !== '' && f.periodEnd != null) payload.period_end = String(f.periodEnd);
                    }
                }
                submitExportPost(this.exportUrl, payload);
            },

            columnIcon(key) {
                return columnIconPaths[key] || null;
            },

            columnIconColor(key) {
                return headerIconClassForKey(key);
            },

            get activeSortRules() {
                return normalizeSortRules(this.sortConfig);
            },

            sortDirection(key) {
                const rule = this.activeSortRules.find((r) => r.key === key);
                return rule ? rule.direction : null;
            },

            sortPriority(key) {
                return null;
            },

            sortPriorityBadge(key) {
                return '';
            },

            isPrimarySort(key) {
                return this.activeSortRules[0]?.key === key;
            },

            isSortRule(key, direction, priority = 1) {
                const rule = this.activeSortRules[priority - 1];
                return rule && rule.key === key && rule.direction === direction;
            },

            isSortActive(key, direction) {
                return this.sortDirection(key) === direction;
            },

            isColumnFiltered(key) {
                return !!this.columnFilters[key];
            },

            sortIconClass(key) {
                const hasSort = !!this.sortDirection(key);
                const hasFilter = this.isColumnFiltered(key);
                if (hasSort) return 'nttu-sort-active';
                if (hasFilter) return 'nttu-sort-filtered';
                return '';
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
                this.$nextTick(() => {
                    this.decorateFilterModal();
                });
            },

            decorateFilterModal() {
                const modal = document.querySelector('div[x-show="advancedOpen"]');
                if (!modal) return;

                // 1. Ensure "Đến ngày" input exists if server Blade only rendered 1 date input
                const dateInputs = modal.querySelectorAll('input[type="date"]');
                if (dateInputs.length === 1) {
                    const firstContainer = dateInputs[0].closest('.space-y-2');
                    if (firstContainer) {
                        const lbl = firstContainer.querySelector('label');
                        if (lbl) {
                            lbl.innerHTML = `<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Từ ngày`;
                        }
                        dateInputs[0].value = this.toIsoDate(this.advancedFilters.fromDate || this.advancedFilters.date);
                        dateInputs[0].onchange = (e) => this.onAdvancedFromDateChange(e.target.value);
                        dateInputs[0].oninput = (e) => this.onAdvancedFromDateChange(e.target.value);

                        const toContainer = document.createElement('div');
                        toContainer.className = 'space-y-2';
                        toContainer.innerHTML = `
                            <label class="font-medium flex items-center gap-2 text-gray-700">
                                <svg class="h-4 w-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Đến ngày
                            </label>
                            <input type="date" class="nttu-form-control" value="${this.toIsoDate(this.advancedFilters.toDate || this.advancedFilters.fromDate || this.advancedFilters.date)}">
                        `;
                        firstContainer.after(toContainer);
                        const toInp = toContainer.querySelector('input');
                        if (toInp) {
                            toInp.onchange = (e) => this.onAdvancedToDateChange(e.target.value);
                            toInp.oninput = (e) => this.onAdvancedToDateChange(e.target.value);
                        }
                    }
                } else if (dateInputs.length >= 2) {
                    dateInputs[0].value = this.toIsoDate(this.advancedFilters.fromDate || this.advancedFilters.date);
                    dateInputs[1].value = this.toIsoDate(this.advancedFilters.toDate || this.advancedFilters.fromDate || this.advancedFilters.date);
                }

                // 2. Decorate ALL Labels in modal with SVG icons if missing
                const labelIconMap = [
                    { keywords: ['từ ngày', 'ngày lọc'], icon: '<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' },
                    { keywords: ['đến ngày'], icon: '<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' },
                    { keywords: ['ca học', 'ca trực'], icon: '<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
                    { keywords: ['dãy nhà'], icon: '<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' },
                    { keywords: ['khoa', 'đơn vị'], icon: '<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>' },
                    { keywords: ['phòng'], icon: '<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>' },
                    { keywords: ['giảng viên', 'cbct'], icon: '<svg class="h-4 w-4 text-orange-500 mr-2 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>' },
                    { keywords: ['từ tiết', 'đến tiết'], icon: '<svg class="h-3.5 w-3.5 text-orange-500 mr-1.5 inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>' },
                ];

                modal.querySelectorAll('label').forEach((lbl) => {
                    if (lbl.querySelector('svg')) return;
                    const text = lbl.textContent.toLowerCase().trim();
                    for (const item of labelIconMap) {
                        if (item.keywords.some((k) => text.includes(k))) {
                            lbl.classList.add('flex', 'items-center', 'gap-1');
                            lbl.insertAdjacentHTML('afterbegin', item.icon);
                            break;
                        }
                    }
                });

                // 3. Decorate ALL Buttons in modal with SVG icons if missing
                modal.querySelectorAll('button').forEach((btn) => {
                    const text = btn.textContent.trim().toLowerCase();
                    const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click') || '';
                    if (!btn.querySelector('svg')) {
                        if (text.includes('xóa bộ lọc') || text.includes('xóa') || clickAttr.includes('resetAdvanced')) {
                            btn.classList.add('inline-flex', 'items-center', 'gap-1.5');
                            btn.insertAdjacentHTML('afterbegin', `<svg class="h-4 w-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>`);
                        } else if (text.includes('áp dụng') || clickAttr.includes('applyAdvanced')) {
                            btn.classList.add('inline-flex', 'items-center', 'gap-1.5');
                            btn.insertAdjacentHTML('afterbegin', `<svg class="h-4 w-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`);
                        } else if (text.includes('lưu hiện tại') || clickAttr.includes('saveFilterPreset')) {
                            btn.classList.add('inline-flex', 'items-center', 'gap-1.5');
                            btn.insertAdjacentHTML('afterbegin', `<svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>`);
                        }
                    }
                });

                // 4. Ensure Ca học select options
                try {
                    const selects = modal.querySelectorAll('select');
                    selects.forEach((sel) => {
                        const mOpt = sel.querySelector('option[value="morning"], option[value="ca1"]');
                        if (mOpt) mOpt.textContent = 'Ca 1 (Tiết 1 - 5)';
                        const aOpt = sel.querySelector('option[value="afternoon"], option[value="ca2"]');
                        if (aOpt) aOpt.textContent = 'Ca 2 (Tiết 6 - 10)';
                        const eOpt = sel.querySelector('option[value="evening"], option[value="ca3"]');
                        if (eOpt) eOpt.textContent = 'Ca 3 (Tiết 11 - 16)';
                    });
                } catch (_) {}
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
                const date = toDisplayDate(isoDate);
                this.patchAdvancedFilters({ date, fromDate: date, toDate: date });
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },

            onAdvancedFromDateChange(isoDate) {
                const fromDate = toDisplayDate(isoDate);
                this.patchAdvancedFilters({ fromDate, date: fromDate });
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },

            onAdvancedToDateChange(isoDate) {
                const toDate = toDisplayDate(isoDate);
                this.patchAdvancedFilters({ toDate });
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
            },

            async applyAdvanced() {
                const modal = this.$root.querySelector('div[x-show="advancedOpen"]');
                if (modal) {
                    const dateInputs = modal.querySelectorAll('input[type="date"]');
                    if (dateInputs.length >= 2) {
                        const fromVal = dateInputs[0].value;
                        const toVal = dateInputs[1].value;
                        if (fromVal) {
                            const fromDate = toDisplayDate(fromVal);
                            this.advancedFilters.fromDate = fromDate;
                            this.advancedFilters.date = fromDate;
                        }
                        if (toVal) {
                            const toDate = toDisplayDate(toVal);
                            this.advancedFilters.toDate = toDate;
                        }
                    } else if (dateInputs.length === 1) {
                        const fromVal = dateInputs[0].value;
                        if (fromVal) {
                            const fromDate = toDisplayDate(fromVal);
                            this.advancedFilters.fromDate = fromDate;
                            this.advancedFilters.date = fromDate;
                            this.advancedFilters.toDate = fromDate;
                        }
                    }
                }
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
                const fromNum = dateToNumber(f.fromDate || f.date);
                const toNum = dateToNumber(f.toDate || f.fromDate || f.date);
                const itemDateNum = dateToNumber(item.date);

                if (fromNum && toNum) {
                    const min = Math.min(fromNum, toNum);
                    const max = Math.max(fromNum, toNum);
                    if (itemDateNum && (itemDateNum < min || itemDateNum > max)) return false;
                } else if (fromNum && itemDateNum && itemDateNum < fromNum) {
                    return false;
                } else if (toNum && itemDateNum && itemDateNum > toNum) {
                    return false;
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
                this.sortConfig = applySortRule(this.activeSortRules, key, direction);
                localStorage.setItem(sortKey, JSON.stringify(this.sortConfig));
                this.headerPopover = null;
            },

            requestSecondarySort(key, direction) {
                this.requestSort(key, direction);
            },

            removeSort(key) {
                const next = removeSortRule(this.activeSortRules, key);
                this.sortConfig = next.length > 0 ? next : null;
                if (this.sortConfig) {
                    localStorage.setItem(sortKey, JSON.stringify(this.sortConfig));
                } else {
                    localStorage.removeItem(sortKey);
                }
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
                this.selectionFilter = 'all';
                this.updateSelectionHeaderBadge();
                this.sortConfig = null;
                this.selectionFilter = 'all';
                localStorage.removeItem(colFiltersKey);
                localStorage.removeItem(sortKey);
                localStorage.removeItem(selFilterKey);
                this.updateSelectionHeaderBadge();
                this.resetAdvanced();
                await this.fetchData();
                this.showToast('Đã xóa tất cả bộ lọc');
            },

            setSelectionFilter(mode) {
                this.selectionFilter = mode;
                this.selectionPopover = false;
                this.currentPage = 1;
                localStorage.setItem(selFilterKey, mode);
                localStorage.setItem(pageKey, '1');
                this.updateSelectionHeaderBadge();
            },

            toggleSelectAllCurrentPage() {
                const allItems = (this.filteredItems && this.filteredItems.length > 0) ? this.filteredItems : (this.paginatedItems || []);
                const allIds = allItems.map((item) => item.id);
                const allSelected = allIds.length > 0 && allIds.every((id) => this.selectedRowIds.includes(id));
                if (allSelected) {
                    this.selectedRowIds = this.selectedRowIds.filter((id) => !allIds.includes(id));
                } else {
                    this.selectedRowIds = Array.from(new Set([...this.selectedRowIds, ...allIds]));
                }
                localStorage.setItem(selKey, JSON.stringify(this.selectedRowIds));
                this.updateSelectionHeaderBadge();
            },

            updateSelectionHeaderBadge() {
                const th = this.$root?.querySelector?.('.nttu-th-index');
                if (!th) return;
                const badge = th.querySelector('.selection-filter-badge');
                const funnelSvg = th.querySelector('.btn-selection-filter svg');
                if (badge) {
                    if (this.selectionFilter === 'selected') {
                        badge.textContent = `(${this.selectedRowIds.length})`;
                        badge.className = 'selection-filter-badge text-[10px] font-bold px-1 rounded bg-yellow-400 text-blue-900';
                        badge.classList.remove('hidden');
                    } else if (this.selectionFilter === 'unselected') {
                        badge.textContent = '(Chưa)';
                        badge.className = 'selection-filter-badge text-[10px] font-bold px-1 rounded bg-yellow-400 text-blue-900';
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
                if (funnelSvg) {
                    funnelSvg.classList.remove('nttu-header-badge-active');
                    if (this.selectionFilter !== 'all') {
                        funnelSvg.classList.add('!text-yellow-300', 'font-bold', 'drop-shadow');
                        funnelSvg.classList.remove('opacity-80');
                    } else {
                        funnelSvg.classList.remove('!text-yellow-300', 'font-bold', 'drop-shadow');
                        funnelSvg.classList.add('opacity-80');
                    }
                }
                if (this.selectionFilter !== 'all') {
                    th.classList.add('bg-blue-800');
                } else {
                    th.classList.remove('bg-blue-800');
                }
            },

            ensureSelectionFilterHeader() {
                const th = this.$root?.querySelector?.('.nttu-th-index');
                if (!th || th.querySelector('.btn-selection-filter')) return;

                th.classList.add('relative', 'p-0');
                th.innerHTML = `
                    <div class="relative flex items-center justify-center h-full">
                        <button type="button" class="btn-selection-filter flex items-center justify-center gap-1 w-full h-full py-2 px-1 hover:bg-black/20 text-white transition-colors" title="Lọc theo dòng đã chọn">
                            <span class="font-bold">#</span>
                            <svg class="h-3 w-3 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span class="selection-filter-badge hidden text-[10px] font-bold px-1 rounded bg-yellow-400 text-blue-900"></span>
                        </button>
                        <div class="selection-filter-popover hidden absolute top-full left-0 z-50 w-56 rounded-md border bg-white p-1 text-gray-800 shadow-xl text-left font-normal text-xs">
                            <div class="px-2 py-1.5 font-bold text-gray-700 border-b flex items-center justify-between">
                                <span>Lọc dòng:</span>
                                <span class="text-[10px] font-normal text-gray-500 sel-count"></span>
                            </div>
                            <div class="py-1 space-y-0.5">
                                <button type="button" class="btn-sel-all flex w-full items-center justify-between px-2 py-1.5 rounded hover:bg-gray-100">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                        Tất cả dòng
                                    </span>
                                </button>
                                <button type="button" class="btn-sel-selected flex w-full items-center justify-between px-2 py-1.5 rounded hover:bg-gray-100">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Chỉ dòng đã chọn
                                    </span>
                                    <span class="sel-num text-xs text-gray-500"></span>
                                </button>
                                <button type="button" class="btn-sel-unselected flex w-full items-center justify-between px-2 py-1.5 rounded hover:bg-gray-100">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        Chỉ dòng chưa chọn
                                    </span>
                                </button>
                            </div>
                            <div class="border-t pt-1 mt-1 space-y-0.5">
                                <button type="button" class="btn-toggle-page flex w-full items-center gap-2 px-2 py-1 rounded text-gray-600 hover:bg-gray-100">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Chọn/Bỏ chọn tất cả</span>
                                </button>
                                <button type="button" class="btn-clear-sel flex w-full items-center gap-2 px-2 py-1 rounded text-red-600 hover:bg-red-50">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span>Bỏ chọn tất cả</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                const btn = th.querySelector('.btn-selection-filter');
                const popover = th.querySelector('.selection-filter-popover');
                if (btn && popover) {
                    const getHeaderSafeTop = () => {
                        const headerEl = document.querySelector('header.sticky, .nttu-header, header');
                        return headerEl ? Math.max(64, Math.round(headerEl.getBoundingClientRect().bottom)) : 64;
                    };

                    const positionFixed = () => {
                        const PAD = 8;
                        const GAP = 4;
                        const safeTop = getHeaderSafeTop() + PAD;
                        const triggerRect = btn.getBoundingClientRect();

                        popover.style.position = 'fixed';
                        popover.style.zIndex = '10060';
                        popover.style.margin = '0';
                        popover.style.right = 'auto';
                        popover.style.bottom = 'auto';
                        popover.style.visibility = 'hidden';
                        popover.classList.remove('hidden');

                        const popoverWidth = popover.offsetWidth || 224;
                        const popoverHeight = popover.offsetHeight || 230;

                        let left = triggerRect.left;
                        if (left + popoverWidth > window.innerWidth - PAD) {
                            left = window.innerWidth - popoverWidth - PAD;
                        }
                        if (left < PAD) left = PAD;

                        const availableBelow = window.innerHeight - (triggerRect.bottom + GAP) - PAD;
                        const availableAbove = triggerRect.top - GAP - safeTop;

                        let top;
                        let maxHeight;

                        if (availableBelow >= popoverHeight || (availableBelow >= availableAbove && availableBelow >= 120)) {
                            top = triggerRect.bottom + GAP;
                            maxHeight = Math.max(120, availableBelow);
                        } else {
                            maxHeight = Math.max(120, availableAbove);
                            const actualH = Math.min(popoverHeight, maxHeight);
                            top = Math.max(safeTop, triggerRect.top - GAP - actualH);
                        }

                        popover.style.top = `${Math.round(top)}px`;
                        popover.style.left = `${Math.round(left)}px`;
                        popover.style.maxHeight = `${Math.floor(maxHeight)}px`;
                        popover.style.overflowY = 'auto';
                        popover.style.visibility = 'visible';
                    };

                    const hideFixed = () => {
                        popover.classList.add('hidden');
                        popover.style.position = '';
                        popover.style.top = '';
                        popover.style.left = '';
                        popover.style.maxHeight = '';
                        popover.style.overflowY = '';
                        popover.style.visibility = '';
                    };

                    btn.onclick = (e) => {
                        e.stopPropagation();
                        const isHidden = popover.classList.contains('hidden');
                        document.querySelectorAll('.selection-filter-popover').forEach((p) => {
                            p.classList.add('hidden');
                            p.style.position = '';
                        });
                        if (isHidden) {
                            popover.querySelector('.sel-count').textContent = `Đã chọn: ${this.selectedRowIds.length}`;
                            popover.querySelector('.sel-num').textContent = `(${this.selectedRowIds.length})`;
                            positionFixed();
                        }
                    };

                    popover.querySelector('.btn-sel-all').onclick = (e) => {
                        e.stopPropagation();
                        this.setSelectionFilter('all');
                        hideFixed();
                    };
                    popover.querySelector('.btn-sel-selected').onclick = (e) => {
                        e.stopPropagation();
                        this.setSelectionFilter('selected');
                        hideFixed();
                    };
                    popover.querySelector('.btn-sel-unselected').onclick = (e) => {
                        e.stopPropagation();
                        this.setSelectionFilter('unselected');
                        hideFixed();
                    };
                    popover.querySelector('.btn-toggle-page').onclick = (e) => {
                        e.stopPropagation();
                        this.toggleSelectAllCurrentPage();
                        hideFixed();
                    };
                    popover.querySelector('.btn-clear-sel').onclick = (e) => {
                        e.stopPropagation();
                        this.selectedRowIds = [];
                        localStorage.setItem(selKey, JSON.stringify([]));
                        this.setSelectionFilter('all');
                        hideFixed();
                    };

                    document.addEventListener('click', (e) => {
                        if (!th.contains(e.target) && !popover.contains(e.target)) hideFixed();
                    });

                    window.addEventListener('scroll', () => {
                        if (!popover.classList.contains('hidden')) hideFixed();
                    }, true);
                }
            },

            async resetAdvanced() {
                this.selectionFilter = 'all';
                this.updateSelectionHeaderBadge();
                const today = this.todayDate || toDisplayDate(new Date().toISOString().slice(0, 10));
                this.advancedFilters = {
                    date: today,
                    fromDate: today,
                    toDate: today,
                    buildings: [],
                    departments: [],
                    rooms: [],
                    lecturers: [],
                    periodSession: 'all',
                    periodStart: '',
                    periodEnd: '',
                };
                localStorage.removeItem(advKey);
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');

                this.$nextTick(() => {
                    const modal = document.querySelector('div[x-show="advancedOpen"]');
                    if (modal) {
                        const dateInputs = modal.querySelectorAll('input[type="date"]');
                        dateInputs.forEach((inp) => {
                            inp.value = toIsoDate(today);
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    }
                    document.querySelectorAll('.quick-date-range-bar input[type="date"]').forEach((inp) => {
                        inp.value = toIsoDate(today);
                        inp.dispatchEvent(new Event('input', { bubbles: true }));
                        inp.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
                await this.fetchData();
                this.showToast('Đã xóa bộ lọc về ngày mặc định');
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
                const f = this.advancedFilters;
                this.deleteDate = toDisplayDate(f.fromDate || f.date || this.todayDate);
                this.deleteToDate = toDisplayDate(f.toDate || f.fromDate || f.date || this.todayDate);
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
                    const from = toDisplayDate(this.deleteDate);
                    const to = toDisplayDate(this.deleteToDate || this.deleteDate);
                    const res = await fetch(this.deleteByDateUrl, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ from, to, date: from }),
                    });
                    const payload = await res.json();
                    if (!res.ok) throw new Error(payload.message || 'Không xóa được');
                    await this.fetchData();
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
