// Per-module column definitions — mirrors old Next.js app
import { evidencePanelMethods, evidencePanelState } from './evidence-input.js';
import { columnKeyIconPaths as columnIconPaths, headerIconClassForKey } from './nttu-icons.js';
import { t, getLanguage } from './language.js';
import { createNttuMultiSelectMixin } from './nttu-multi-select.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice } from './nttu-pagination.js';

/** Đã ghi nhận = có ngày ghi nhận và người ghi nhận (khác rỗng). */
export function isScheduleRowHandled(item) {
    if (!item) return false;
    const recognitionDate = item.recognition_date ?? item.recognitionDate ?? '';
    const employee = item.employee ?? '';
    return !!(
        String(recognitionDate).trim()
        && String(employee).trim()
    );
}
function getModuleColumns(module) {
    const base = {
        date: 'Ngày',
        building: 'Dãy nhà',
        room: 'Phòng',
        period: 'Tiết',
        type: 'LT/TH',
        department: 'Khoa sử dụng',
        class: 'Lớp',
        student_count: 'Sĩ số',
        lecturer: 'Giảng viên',
        content: 'Nội dung',
        status: 'Trạng thái',
        note: 'Ghi chú',
    };
    if (module === 'online') {
        return {
            ...base,
            content: 'Môn học',
        };
    }
    if (module === 'exams') {
        const { lecturer, ...rest } = base;
        return {
            ...rest,
            proctor1: 'CBCT 1',
            proctor2: 'CBCT 2',
            proctor3: 'CBCT 3',
        };
    }
    return base;
}

function getModuleColumnOrder(module) {
    const base = [
        'date', 'building', 'room', 'period', 'type', 'department', 'class',
        'student_count', 'lecturer', 'content', 'status', 'note',
    ];
    if (module === 'online') {
        return [
            'date', 'building', 'room', 'period', 'type', 'department', 'class',
            'student_count', 'lecturer', 'content', 'status', 'note',
        ];
    }
    if (module === 'exams') {
        return [
            'date', 'building', 'room', 'period', 'type', 'department', 'class',
            'student_count', 'proctor1', 'proctor2', 'proctor3', 'content', 'status', 'note',
        ];
    }
    return base;
}

function getModuleDefaultVisibility(module) {
    if (module === 'online') {
        return {
            date: true, building: true, room: true, period: true, class: true,
            student_count: true, lecturer: true, content: true,
            status: true, type: false, department: false, note: false,
        };
    }
    if (module === 'exams') {
        return {
            date: true, building: true, room: true, period: true, type: true,
            department: true, class: true, student_count: true,
            proctor1: true, proctor2: true, proctor3: true, content: true, status: true, note: false,
        };
    }
    // in-person, homeroom, external-practice
    return {
        date: true, building: true, room: true, period: true, type: true,
        department: true, class: true, student_count: true, lecturer: true,
        content: true, status: true, note: false,
    };
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

function notificationSheetValue(value) {
    return parseNotificationValue(value) ? 'TRUE' : 'FALSE';
}

function notificationRequestValue(value) {
    return parseNotificationValue(value) ? '1' : '0';
}

// Lucide-style SVG paths — re-exported from nttu-icons.js (columnIconPaths)

function normalizeAdvancedFilters(raw, fallbackDate = '') {
    const f = raw || {};
    return {
        date: f.date || fallbackDate || '',
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

export function monitoringSchedulesPage(config) {
    const storageKey = `nttu_monitoring_${config.module}_colvis`;
    const pageKey = `nttu_monitoring_${config.module}_page`;
    const rowsKey = `nttu_monitoring_${config.module}_rows`;
    const selKey = `nttu_monitoring_${config.module}_selected`;
    const advKey = `nttu_monitoring_${config.module}_adv_filters`;
    const presetKey = `nttu_monitoring_${config.module}_filter_presets`;
    const sortKey = `nttu_monitoring_${config.module}_sort`;
    const colFiltersKey = `nttu_monitoring_${config.module}_col_filters`;
    const moduleColumns = getModuleColumns(config.module);
    const moduleColumnOrder = getModuleColumnOrder(config.module);
    const defaultVisibility = getModuleDefaultVisibility(config.module);
    const uiConfig = {
        recordingOnlyOnEdit: false,
        noteInClassSection: false,
        attendingStudentsLabel: 'SV tham gia',
        rowClickSelects: false,
        hideEmployeeInEdit: false,
        hideRecognitionDateInEdit: false,
        advancedDateReloads: false,
        iconToolbar: false,
        ...config.uiConfig,
    };
    let savedVisibility = defaultVisibility;
    let savedPage = 1;
    let savedRows = 10;
    let savedSelected = [];
    let savedAdvanced = null;
    let savedPresets = [];
    let savedSort = null;
    let savedColumnFilters = {};
    try {
        savedVisibility = { ...defaultVisibility, ...JSON.parse(localStorage.getItem(storageKey) || '{}') };
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
        module: config.module,
        date: config.date,
        items: config.items,
        masterData: config.masterData,
        incidentCategories: config.incidentCategories,
        employeeDefault: config.employeeDefault,
        todayDate: config.todayDate,
        modalEntity: config.modalEntity || 'lớp học',
        currentLang: getLanguage(),
        showsAttendingStudents: !!config.showsAttendingStudents,
        uiConfig,
        modalOpen: false,
        modalMode: 'edit',
        advancedOpen: false,
        colVisOpen: false,
        settingsOpen: false,
        headerPopover: null,
        rowMenuOpen: null,
        deleteOpen: false,
        deleteTarget: null,
        saving: false,
        loading: false,
        toast: null,
        dateNotice: config.dateNotice || '',
        dataUrl: config.dataUrl || '',
        exportUrlBase: config.exportUrl || '',
        detailUrl: config.detailUrl || '',
        presetsUrl: config.presetsUrl || '',
        evidenceUploadUrl: config.evidenceUploadUrl || '',
        canAdd: config.canAdd !== false,
        canEdit: config.canEdit !== false,
        canDelete: config.canDelete !== false,
        canImport: config.canImport !== false,
        canExport: config.canExport !== false,
        openSections: {},
        initialForm: {},
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
        advancedFilters: normalizeAdvancedFilters(
            {
                date: config.date || config.todayDate,
                buildings: [],
                departments: [],
                rooms: [],
                lecturers: [],
                periodSession: 'all',
                periodStart: '',
                periodEnd: '',
                ...(savedAdvanced || {}),
            },
            config.date || config.todayDate,
        ),
        form: {},
        columns: moduleColumns,
        columnOrder: moduleColumnOrder,
        columnIconPaths,
        ...evidencePanelState(config.evidenceUploadUrl || ''),

        async init() {
            this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 10);
            this.currentPage = normalizeCurrentPage(this.currentPage, this.totalPages);
            window.addEventListener('nttu-language-changed', (event) => {
                this.currentLang = event.detail?.language || getLanguage();
            });
            void this.loadFilterPresets();
            if (this.dataUrl) {
                await this.fetchScheduleData(this.advancedFilters.date || this.date || this.todayDate);
                if (this.uiConfig.advancedDateReloads) {
                    const serverDate = formatFilterDate(this.date);
                    if (serverDate) {
                        this.advancedFilters = normalizeAdvancedFilters(
                            { ...this.advancedFilters, date: serverDate },
                            serverDate,
                        );
                        this.persistAdvancedFilters();
                    }
                }
            }
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

        async patchAdvancedFilters(patch) {
            const prevDate = this.advancedFilters.date;
            this.advancedFilters = normalizeAdvancedFilters(
                { ...this.advancedFilters, ...patch },
                this.todayDate,
            );
            this.persistAdvancedFilters();
            if (this.uiConfig.advancedDateReloads && patch.date !== undefined && this.advancedFilters.date !== prevDate) {
                await this.fetchScheduleData(this.advancedFilters.date);
            }
        },

        async fetchScheduleData(date) {
            const d = formatFilterDate(date) || this.date;
            if (!this.dataUrl || !d) return;
            this.loading = true;
            try {
                const res = await fetch(`${this.dataUrl}?date=${encodeURIComponent(d)}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('Không tải được lịch');
                const payload = await res.json();
                this.items = payload.items || [];
                this.date = payload.date || d;
                this.dateNotice = payload.dateNotice || '';
                this.advancedFilters = normalizeAdvancedFilters(
                    { ...this.advancedFilters, date: this.date },
                    this.date,
                );
                this.persistAdvancedFilters();
                this.currentPage = 1;
                localStorage.setItem(pageKey, '1');
                if (this.uiConfig.advancedDateReloads) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('date', this.date);
                    window.history.replaceState({}, '', url.toString());
                }
            } catch (err) {
                this.showToast(err.message || 'Lỗi tải dữ liệu', 'error');
            } finally {
                this.loading = false;
            }
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

        async onAdvancedDateChange(isoDate) {
            let date = '';
            if (isoDate) {
                const [y, m, day] = isoDate.split('-');
                date = `${day}/${m}/${y}`;
            }
            this.currentPage = 1;
            localStorage.setItem(pageKey, '1');
            await this.patchAdvancedFilters({ date });
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => {
                if (this.toast?.message === message) this.toast = null;
            }, 2500);
        },

        get visibleColumnKeys() {
            return this.columnOrder.filter((k) => this.columns[k] && this.columnVisibility[k]);
        },

        get filteredItems() {
            let result = this.items.filter((item) => this.matchesAdvanced(item) && this.matchesColumnFilters(item));
            if (this.sortConfig) {
                const { key, direction } = this.sortConfig;
                result = [...result].sort((a, b) => {
                    const av = String(a[key] ?? '');
                    const bv = String(b[key] ?? '');
                    const cmp = av.localeCompare(bv, 'vi', { numeric: true });
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

        get modalTitle() {
            void this.currentLang;
            const entity = t(this.modalEntity, this.currentLang) || this.modalEntity;
            if (this.modalMode === 'view') return `${t('Chi tiết', this.currentLang)} ${entity}`;
            if (this.modalMode === 'edit') return `${t('Ghi nhận', this.currentLang)} ${entity}`;
            if (this.modalMode === 'copy') return `${t('Sao chép', this.currentLang)} ${entity}`;
            return `${t('Thêm mới', this.currentLang)} ${entity}`;
        },

        get isViewMode() {
            return this.modalMode === 'view';
        },

        get isClassEditable() {
            return this.modalMode !== 'view';
        },

        get isNoteFieldEditable() {
            return this.modalMode !== 'view';
        },

        get isClassFieldEditable() {
            return this.modalMode === 'add' || this.modalMode === 'copy';
        },

        get isClassReadOnly() {
            return this.modalMode === 'edit' || this.modalMode === 'view';
        },

        get isStatusEditable() {
            if (this.uiConfig.statusOnAddCopyOnly) {
                return this.modalMode === 'add' || this.modalMode === 'copy';
            }
            return this.isClassEditable;
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

        get isRecordingEditable() {
            return this.modalMode !== 'view';
        },

        get showRecordingSection() {
            if (this.modalMode === 'view') return true;
            if (!this.uiConfig.recordingOnlyOnEdit) return this.modalMode !== 'view';
            return this.modalMode === 'edit';
        },

        get showEvidenceSection() {
            if (!this.uiConfig.recordingOnlyOnEdit) return this.modalMode !== 'view';
            return this.modalMode === 'edit';
        },

        get visibleSelectedCount() {
            const ids = new Set(this.selectedRowIds);
            return this.filteredItems.filter((item) => ids.has(item.id)).length;
        },

        get attendingStudentsLabel() {
            return this.uiConfig.attendingStudentsLabel || 'SV tham gia';
        },

        get requiresIncident() {
            return !!this.uiConfig.requiresIncident;
        },

        get hasActiveAdvancedFilters() {
            const f = this.advancedFilters;
            return !!(f.buildings?.length || f.departments?.length || f.rooms?.length || f.lecturers?.length
                || (f.periodSession && f.periodSession !== 'all'));
        },

        get activeFilterCount() {
            const f = this.advancedFilters;
            return (f.buildings?.length || 0) + (f.departments?.length || 0)
                + (f.rooms?.length || 0) + (f.lecturers?.length || 0)
                + (f.periodSession && f.periodSession !== 'all' ? 1 : 0);
        },

        get exportUrl() {
            const base = this.exportUrlBase || (this.dataUrl ? this.dataUrl.replace(/\/data$/, '/export') : '');
            if (!base) return '#';

            const params = new URLSearchParams();
            params.set('date', this.date || '');

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

            const ids = this.filteredItems.map((item) => item.id);
            if (ids.length) {
                params.set('ids', ids.join(','));
            }

            const qs = params.toString();

            return qs ? `${base}?${qs}` : base;
        },

        columnIcon(key) {
            return columnIconPaths[key] || null;
        },

        notificationChecked(value) {
            return parseNotificationValue(value);
        },

        notificationLabel(value) {
            return notificationSheetValue(value);
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

        clearColumnFilter(key) {
            this.setColumnFilter(key, '');
            this.headerPopover = null;
        },

        handleRowClick(item) {
            if (!this.uiConfig.rowClickSelects) {
                this.openModal('edit', item);
                return;
            }
            const idx = this.selectedRowIds.indexOf(item.id);
            if (idx >= 0) {
                this.selectedRowIds.splice(idx, 1);
            } else {
                this.selectedRowIds.push(item.id);
            }
            localStorage.setItem(selKey, JSON.stringify(this.selectedRowIds));
        },

        isRowSelected(id) {
            return this.selectedRowIds.includes(id);
        },

        applyAdvanced() {
            this.persistAdvancedFilters();
            this.advancedOpen = false;
            this.showToast(`Đã áp dụng bộ lọc (${this.filteredItems.length} bản ghi)`);
        },

        loadFilterPresetsLocal() {
            try {
                this.filterPresets = JSON.parse(localStorage.getItem(presetKey) || '[]');
            } catch (_) {
                this.filterPresets = [];
            }
        },

        async loadFilterPresets() {
            if (!this.presetsUrl) {
                this.loadFilterPresetsLocal();
                return;
            }
            try {
                const res = await fetch(this.presetsUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (res.ok) {
                    const payload = await res.json();
                    this.filterPresets = Array.isArray(payload.presets) ? payload.presets : [];
                    localStorage.setItem(presetKey, JSON.stringify(this.filterPresets));
                    return;
                }
            } catch (_) {}
            this.loadFilterPresetsLocal();
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ presets: this.filterPresets }),
                });
                const payload = await res.json();
                if (!res.ok) {
                    throw new Error(payload.message || 'Không lưu được bộ lọc');
                }
                this.filterPresets = payload.presets || this.filterPresets;
                localStorage.setItem(presetKey, JSON.stringify(this.filterPresets));
            } catch (err) {
                localStorage.setItem(presetKey, JSON.stringify(this.filterPresets));
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

        async applyFilterPreset(preset) {
            const filters = normalizeAdvancedFilters(preset?.filters || {}, this.todayDate);
            // App cũ lưu ngày dạng yyyy-MM-dd — chuẩn hóa sang dd/mm/yyyy
            if (filters.date && filters.date.includes('-')) {
                const [y, m, day] = filters.date.split('-');
                filters.date = `${day}/${m}/${y}`;
            }
            this.advancedFilters = filters;
            this.persistAdvancedFilters();
            this.presetMenuOpen = false;
            this.currentPage = 1;
            localStorage.setItem(pageKey, '1');

            if (this.uiConfig.advancedDateReloads) {
                await this.fetchScheduleData(this.advancedFilters.date);
            }

            this.showToast(`Đã tải bộ lọc "${preset.name}" (${this.filteredItems.length} bản ghi)`);
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
                const val = item?.[key];
                if (val) set.add(String(val));
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
            if (field === 'building') {
                this.form.room = '';
            }
            this.closeCombo(field);
        },

        getComboOptions(field) {
            const search = (this.comboSearch[field] || '').toLowerCase();
            let opts = [];
            if (field === 'building') opts = this.masterData.buildings || [];
            else if (field === 'room') opts = this.filteredRoomNames;
            else if (field === 'department') opts = this.masterData.departments || [];
            else if (field === 'lecturer' || field === 'proctor1' || field === 'proctor2' || field === 'proctor3') opts = this.masterData.lecturers || [];
            if (!search) return opts;
            return opts.filter((o) => String(o).toLowerCase().includes(search));
        },

        confirmComboNew(field) {
            const value = (this.comboSearch[field] || '').trim();
            if (value) this.setCombo(field, value);
        },

        removeEvidence(index) {
            this.evidenceRemoveItem(index);
        },

        syncEvidenceString() {
            this.evidenceEmitChange();
        },

        buildingMatchesFilter(item, filterValue) {
            const candidates = [item?.building, item?.building_label]
                .filter((value) => value !== null && value !== undefined && String(value).trim() !== '')
                .map((value) => String(value));
            const filter = String(filterValue ?? '').trim();
            if (!filter) return true;
            return candidates.some((value) => value === filter);
        },

        matchesColumnFilters(item) {
            return Object.entries(this.columnFilters).every(([key, value]) => {
                if (!value) return true;
                if (key === 'building') {
                    return [item.building, item.building_label]
                        .filter(Boolean)
                        .some((candidate) => String(candidate).toLowerCase().includes(String(value).toLowerCase()));
                }
                if (key === 'room') {
                    return [item.room, item.room_label]
                        .filter(Boolean)
                        .some((candidate) => String(candidate).toLowerCase().includes(String(value).toLowerCase()));
                }
                return String(item[key] ?? '').toLowerCase().includes(String(value).toLowerCase());
            });
        },

        matchesAdvanced(item) {
            const f = this.advancedFilters;
            const buildings = f.buildings || [];
            const departments = f.departments || [];
            const rooms = f.rooms || [];
            const lecturers = f.lecturers || [];
            if (buildings.length && !buildings.some((building) => this.buildingMatchesFilter(item, building))) return false;
            if (departments.length && !departments.includes(item.department)) return false;
            if (rooms.length && !rooms.some((room) => [item.room, item.room_label].filter(Boolean).includes(room))) {
                return false;
            }
            if (lecturers.length) {
                if (this.module === 'exams') {
                    const proctors = [item.proctor1, item.proctor2, item.proctor3].filter(Boolean);
                    if (!lecturers.some((l) => proctors.includes(l))) return false;
                } else if (!lecturers.includes(item.lecturer)) {
                    return false;
                }
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

        async clearAllFilters() {
            this.columnFilters = {};
            this.sortConfig = null;
            localStorage.removeItem(colFiltersKey);
            localStorage.removeItem(sortKey);
            await this.resetAdvanced();
        },

        async resetAdvanced() {
            const today = this.todayDate;
            this.advancedFilters = normalizeAdvancedFilters({ date: today }, today);
            localStorage.removeItem(advKey);
            this.currentPage = 1;
            localStorage.setItem(pageKey, '1');
            if (this.uiConfig.advancedDateReloads) {
                await this.fetchScheduleData(today);
            }
            this.showToast('Đã xóa tất cả bộ lọc');
        },

        isHandled(item) {
            return isScheduleRowHandled(item);
        },

        rowIndexBadgeClass(item) {
            if (!isScheduleRowHandled(item)) return '';
            return this.isRowSelected(item.id)
                ? 'inline-flex items-center justify-center min-w-[28px] h-7 px-1 rounded-full border-2 border-white text-white font-black text-sm'
                : 'inline-flex items-center justify-center min-w-[28px] h-7 px-1 rounded-full border-2 border-red-500 text-red-600 font-black text-sm';
        },

        statusBadgeClass(status) {
            if (!status) return 'badge-gray';
            const s = String(status).toLowerCase();
            if (s.includes('thi') || s.includes('kết thúc')) return 'badge-purple';
            if (s.includes('trực tuyến') || s.includes('online')) return 'badge-blue';
            if (s.includes('thực hành') || s.includes('th ')) return 'badge-green';
            if (s.includes('ngủ') || s.includes('nghỉ') || s.includes('vắng')) return 'badge-red';
            if (s.includes('học') || s.includes('lt')) return 'badge-cyan';
            return 'badge-gray';
        },

        parseEvidence(str) {
            if (!str) return [];
            return str.split('|').filter(Boolean).map((part) => {
                if (part.includes(':::')) {
                    const [name, url] = part.split(':::');
                    return { name, url };
                }
                return { name: part.split('/').pop(), url: part };
            });
        },

        onFilesSelected(event) {
            this.evidenceHandleFiles(event.target.files);
            event.target.value = '';
        },

        buildFormFromItem(item, mode) {
            const base = {
                id: mode === 'copy' || mode === 'add' ? null : item?.id,
                date: item?.date || this.date || this.todayDate,
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
                employee: item?.employee || this.employeeDefault,
                attending_students: item?.attending_students ?? '',
                incident: mode === 'copy' ? '' : (item?.incident || ''),
                incident_detail: mode === 'copy' ? '' : (item?.incident_detail || ''),
                evidence: mode === 'copy' ? '' : (item?.evidence || ''),
                recognition_date: mode === 'copy' ? this.todayDate : (item?.recognition_date || this.todayDate),
                note: mode === 'copy' ? '' : (item?.note || ''),
                is_notification: mode === 'copy' ? false : parseNotificationValue(item?.is_notification),
            };

            if (mode === 'add') {
                base.content = base.content || '';
                base.status = base.status || 'Phòng học';
                if (this.module === 'homeroom') {
                    base.content = 'SHCN - Sinh hoạt chủ nhiệm';
                }
                if (this.module === 'online') {
                    base.building = base.building || 'Học trực tuyến';
                }
                if (this.module === 'exams') {
                    base.status = 'Phòng thi';
                }
            }

            if ((mode === 'edit' || mode === 'add' || mode === 'copy') && !item?.employee) {
                base.employee = this.employeeDefault;
            }

            return base;
        },

        applyHiddenRecordingDefaults() {
            this.form.employee = this.employeeDefault || this.form.employee || '';
            this.form.recognition_date = this.form.recognition_date || this.todayDate;
        },

        openModal(mode, item) {
            void this.openModalWithDetail(mode, item);
        },

        async openModalWithDetail(mode, item) {
            this.modalMode = mode;
            this.rowMenuOpen = null;
            let source = item;
            if (item?.id && (mode === 'edit' || mode === 'view') && this.detailUrl) {
                try {
                    const url = this.detailUrl.replace('__ID__', encodeURIComponent(item.id));
                    const res = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (res.ok) {
                        const payload = await res.json();
                        source = { ...item, ...(payload.item || {}) };
                        const idx = this.items.findIndex((row) => row.id === item.id);
                        if (idx >= 0) {
                            this.items[idx] = { ...this.items[idx], ...source };
                        }
                    }
                } catch (_) {
                    // Fall back to list row without heavy fields.
                }
            }
            const data = this.buildFormFromItem(source, mode);
            this.form = { ...data };
            this.applyHiddenRecordingDefaults();
            this.initialForm = JSON.parse(JSON.stringify(this.form));
            if (mode === 'edit') {
                this.openSections = {
                    'class-info': false,
                    'recording-info': true,
                    'evidence-info': false,
                };
            } else if (mode === 'view') {
                this.openSections = {
                    'class-info': true,
                    'recording-info': true,
                    'evidence-info': false,
                };
            } else {
                this.openSections = {
                    'class-info': true,
                    'recording-info': false,
                    'evidence-info': false,
                };
            }
            this.modalOpen = true;
            this.toast = null;
            this.$nextTick(() => this.evidenceOnModalOpen());
        },

        openAddModal() {
            this.openModal('add', null);
        },

        confirmDelete(item) {
            this.deleteTarget = item;
            this.deleteOpen = true;
            this.rowMenuOpen = null;
        },

        get isChanged() {
            return JSON.stringify(this.form) !== JSON.stringify(this.initialForm);
        },

        undoForm() {
            this.form = JSON.parse(JSON.stringify(this.initialForm));
            this.evidenceInitFromForm();
        },

        toggleSection(id) {
            const wasOpen = this.openSections[id];
            const next = { ...this.openSections };
            Object.keys(next).forEach((key) => {
                next[key] = false;
            });
            next[id] = !wasOpen;
            this.openSections = next;
        },

        closeModal() {
            this.evidenceCleanupPanel();
            this.modalOpen = false;
        },

        mergeMonitoringItem(existing, saved) {
            if (!existing) {
                return saved;
            }
            const merged = { ...existing, ...saved };
            const preserve = [
                'date', 'building', 'room', 'building_label', 'room_label',
                'period', 'type', 'department', 'class', 'student_count',
                'lecturer', 'proctor1', 'proctor2', 'proctor3', 'content', 'status',
            ];
            for (const key of preserve) {
                const next = merged[key];
                if ((next === null || next === undefined || next === '') && existing[key]) {
                    merged[key] = existing[key];
                }
            }
            return merged;
        },

        appendClassFields(formData) {
            if (this.modalMode === 'add' || this.modalMode === 'copy') {
                formData.append('date', this.form.date ?? this.date);
                formData.append('building', this.form.building ?? '');
                formData.append('room', this.form.room ?? '');
                formData.append('period', this.form.period ?? '');
                formData.append('type', this.form.type ?? '');
                formData.append('department', this.form.department ?? '');
                formData.append('class', this.form.class ?? '');
                if (this.form.student_count !== '') {
                    formData.append('student_count', this.form.student_count);
                }
                formData.append('lecturer', this.form.lecturer ?? '');
                formData.append('proctor1', this.form.proctor1 ?? '');
                formData.append('proctor2', this.form.proctor2 ?? '');
                formData.append('proctor3', this.form.proctor3 ?? '');
                formData.append('content', this.form.content ?? '');
                formData.append('status', this.form.status ?? 'Phòng học');
                if (this.uiConfig.noteInClassSection) {
                    formData.append('note', this.form.note ?? '');
                }
            }
        },

        appendMonitoringFields(formData) {
            if (this.uiConfig.recordingOnlyOnEdit && (this.modalMode === 'add' || this.modalMode === 'copy')) {
                return;
            }
            this.applyHiddenRecordingDefaults();
            formData.append('employee', this.employeeDefault || this.form.employee || '');
            if (this.form.attending_students !== '') {
                formData.append('attending_students', this.form.attending_students);
            }
            formData.append('incident', this.form.incident ?? '');
            formData.append('incident_detail', this.form.incident_detail ?? '');
            formData.append('evidence', this.form.evidence ?? '');
            formData.append('recognition_date', this.form.recognition_date || this.todayDate);
            formData.append('note', this.form.note ?? '');
            formData.append('is_notification', notificationRequestValue(this.form.is_notification));
        },

        validateMonitoringForm() {
            if (!this.requiresIncident || this.modalMode === 'view') {
                return true;
            }

            if (this.uiConfig.recordingOnlyOnEdit && (this.modalMode === 'add' || this.modalMode === 'copy')) {
                return true;
            }

            if (!String(this.form.incident ?? '').trim()) {
                this.toast = { type: 'error', message: 'Vui lòng chọn Việc phát sinh.' };
                this.openSections = {
                    ...this.openSections,
                    'recording-info': true,
                };
                return false;
            }

            return true;
        },

        async saveMonitoring() {
            if (!this.validateMonitoringForm()) {
                return;
            }

            this.saving = true;
            this.toast = null;
            const isCreate = this.modalMode === 'add' || this.modalMode === 'copy';
            const url = isCreate
                ? `/monitoring/${this.module}`
                : `/monitoring/${this.module}/${this.form.id}`;
            const formData = new FormData();
            if (!isCreate) {
                formData.append('_method', 'PUT');
            }
            this.appendClassFields(formData);
            this.appendMonitoringFields(formData);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Lưu thất bại');
                }
                if (isCreate) {
                    this.items.push(data.item);
                } else {
                    const idx = this.items.findIndex((i) => i.id === data.item.id);
                    if (idx >= 0) {
                        this.items[idx] = this.mergeMonitoringItem(this.items[idx], data.item);
                    } else {
                        this.items.push(data.item);
                    }
                }
                this.toast = { type: 'success', message: data.message };
                setTimeout(() => this.closeModal(), 600);
            } catch (e) {
                this.toast = { type: 'error', message: e.message };
            } finally {
                this.saving = false;
            }
        },

        async deleteItem() {
            if (!this.deleteTarget) return;
            this.saving = true;
            try {
                const res = await fetch(`/monitoring/${this.module}/${this.deleteTarget.id}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Xóa thất bại');
                }
                this.items = this.items.filter((i) => i.id !== data.id);
                this.deleteOpen = false;
                this.deleteTarget = null;
                this.toast = { type: 'success', message: data.message };
            } catch (e) {
                this.toast = { type: 'error', message: e.message };
            } finally {
                this.saving = false;
            }
        },

        ...evidencePanelMethods(),
    };
}

window.monitoringSchedulesPage = monitoringSchedulesPage;
