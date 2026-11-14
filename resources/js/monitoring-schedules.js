// Per-module column definitions — mirrors old Next.js app
import { evidencePanelMethods, evidencePanelState } from './evidence-input.js';
import { columnKeyIconPaths as columnIconPaths, headerIconClassForKey } from './nttu-icons.js';
import { t, getLanguage } from './language.js';
import { createNttuMultiSelectMixin } from './nttu-multi-select.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice } from './nttu-pagination.js';
import { submitExportPost } from './export-form-helper.js';
import { multiSortRows, normalizeSortRules, comparePeriods } from './nttu-table-sort.js';

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

const LOCAL_NOTE_MODULES = new Set([
    'homeroom',
    'online',
    'in-person',
    'exams',
    'external-practice',
]);

function moduleSupportsLocalNote(module) {
    return LOCAL_NOTE_MODULES.has(module);
}

function localNotesStorageKey(module) {
    return `nttu_monitoring_${module}_local_notes`;
}

function buildLocalNoteDraftKey(form) {
    if (form?.id) {
        return `id:${form.id}`;
    }

    return [
        'row',
        form?.date || '',
        form?.period || '',
        form?.class || '',
        form?.content || '',
        form?.lecturer || '',
    ].join('|');
}

function readLocalNotesMap(module) {
    try {
        return JSON.parse(localStorage.getItem(localNotesStorageKey(module)) || '{}');
    } catch {
        return {};
    }
}

function writeLocalNotesMap(module, map) {
    localStorage.setItem(localNotesStorageKey(module), JSON.stringify(map));
}

function applyLocalNotesToItems(module, items) {
    if (!moduleSupportsLocalNote(module) || !Array.isArray(items)) {
        return items;
    }

    const map = readLocalNotesMap(module);

    return items.map((item) => {
        const key = buildLocalNoteDraftKey(item);
        if (!Object.prototype.hasOwnProperty.call(map, key)) {
            return item;
        }

        return { ...item, note: map[key] };
    });
}

function formSnapshotWithoutLocalNote(form, excludeNote) {
    const snapshot = { ...form };
    if (excludeNote) {
        delete snapshot.note;
    }

    return snapshot;
}

async function compressIncidentPhoto(file, maxSize = 1600, quality = 0.85) {
    const dataUrl = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Không đọc được file ảnh.'));
        reader.readAsDataURL(file);
    });

    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            const scale = Math.min(1, maxSize / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            resolve(canvas.toDataURL('image/jpeg', quality));
        };
        img.onerror = () => reject(new Error('File không phải ảnh hợp lệ.'));
        img.src = dataUrl;
    });
}

function isPhysicalRoomSchedule(item) {
    if (!item) return false;
    const b = String(item.building || '').toLowerCase().trim();
    const r = String(item.room || item.room_label || '').toLowerCase().trim();

    const onlineKeywords = ['trực tuyến', 'tructuyen', 'online', 'zoom', 'google meet', 'meet', 'ms teams', 'teams'];
    if (onlineKeywords.some((kw) => b.includes(kw) || r.includes(kw))) {
        return false;
    }

    const outsideKeywords = ['ngoài', 'ngoai', 'thực hành ngoài', 'thuc hanh ngoai'];
    if (outsideKeywords.some((kw) => b.includes(kw) || r.includes(kw))) {
        return false;
    }
    if (b === 'thn') {
        return false;
    }

    return true;
}

function isOnlineSchedule(item) {
    if (!item) return false;
    const b = String(item.building || '').toLowerCase().trim();
    const r = String(item.room || item.room_label || '').toLowerCase().trim();

    const onlineKeywords = ['trực tuyến', 'tructuyen', 'online', 'zoom', 'google meet', 'meet', 'ms teams', 'teams'];
    return onlineKeywords.some((kw) => b.includes(kw) || r.includes(kw));
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
    const selFilterKey = `nttu_monitoring_${config.module}_sel_filter`;
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
    let savedSelectionFilter = 'all';
    let savedAdvanced = null;
    let savedPresets = [];
    let savedSort = null;
    let savedColumnFilters = {};
    try {
        savedVisibility = { ...defaultVisibility, ...JSON.parse(localStorage.getItem(storageKey) || '{}') };
        savedPage = normalizeCurrentPage(localStorage.getItem(pageKey) || '1', 1);
        savedRows = normalizeRowsPerPage(localStorage.getItem(rowsKey) || '10', 10);
        savedSelected = JSON.parse(localStorage.getItem(selKey) || '[]');
        savedSelectionFilter = localStorage.getItem(selFilterKey) || 'all';
        savedAdvanced = JSON.parse(localStorage.getItem(advKey) || 'null');
        savedPresets = JSON.parse(localStorage.getItem(presetKey) || '[]');
        savedSort = normalizeSortRules(JSON.parse(localStorage.getItem(sortKey) || 'null'));
        savedColumnFilters = JSON.parse(localStorage.getItem(colFiltersKey) || '{}');
    } catch (_) {}

    const NTTU_INCIDENT_CATEGORIES = {
        exams: [
            'Ghi nhận sinh viên vi phạm quy chế thi',
            'CBCT Đi trễ (số phút)',
            'CBCT không đến coi thi',
            'CBCT thực hiện không đúng quy chế',
            'Coi thi thay',
            'Chuyển phòng thi',
            'Thay đổi thời gian thi',
            'Hủy lịch thi',
            'Thiếu đề thi (số lượng)',
            'Lỗi đề thi (thiếu trang/thiếu đáp án/trùng đáp án)',
            'Có lịch nhưng không có CBCT, Sinh viên',
            'Ghi nhận khác',
        ],
        homeroom: [
            'Có SH',
            'Không SH theo lịch',
            'SH Ngoài lịch (Không đăng ký)',
            'Có GV, không SV',
            'Có SV, không GV',
            'Báo nghỉ',
            'Ghi nhận khác',
        ],
        'in-person': [
            'Không GV, Không SV (đăng ký nhưng không dạy)',
            'Không đến lớp, có SV chờ (số SV chờ)',
            'Đi trễ',
            'Về sớm',
            'GV bỏ lớp',
            'Dạy thay (đổi Giảng viên)',
            'Báo nghỉ',
            'Chuyển phòng',
            'Ngoài lịch (Không đăng ký)',
            'Có GV, Không SV',
            'Ghi nhận khác',
        ],
        online: [
            'Báo nghỉ',
            'Dạy thay',
            'Ghi nhận khác',
            'Không GV, Không SV',
        ],
        'external-practice': [
            'Có thực hiện theo KH',
            'Có thực hiện',
            'Báo nghỉ',
            'Ghi nhận khác',
        ],
    };

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
        incidentCategoriesByModule: config.incidentCategoriesByModule || {},
        employeeDefault: config.employeeDefault,
        todayDate: config.todayDate,
        modalEntity: config.modalEntity || 'lớp học',
        currentLang: getLanguage(),
        showsAttendingStudents: !!config.showsAttendingStudents,
        uiConfig,
        persistLocalNoteEnabled: moduleSupportsLocalNote(config.module),
        modalOpen: false,
        modalMode: 'edit',
        advancedOpen: false,
        colVisOpen: false,
        settingsOpen: false,
        headerPopover: null,
        rowMenuOpen: null,
        deleteOpen: false,
        deleteTarget: null,
        clearRecordingOpen: false,
        clearRecordingTarget: null,
        incidentDetailExtracting: false,
        incidentCameraOpen: false,
        incidentCameraStream: null,
        incidentCameraFacing: 'environment',
        incidentCameraDeviceId: '',
        incidentCameraDevices: [],
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
        selectionFilter: savedSelectionFilter,
        selectionPopover: false,
        includeExams: localStorage.getItem(`nttu_${config.module}_include_exams`) === '1'
            || (config.module === 'in-person' && localStorage.getItem('nttu_in_person_include_exams') === '1'),
        includeHomeroom: localStorage.getItem(`nttu_${config.module}_include_homeroom`) === '1'
            || (config.module === 'in-person' && localStorage.getItem('nttu_in_person_include_homeroom') === '1'),
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
                // Server date wins for schedule modules — stale localStorage date causes empty tables.
                ...((config.uiConfig?.advancedDateReloads && (config.date || config.todayDate))
                    ? { date: config.date || config.todayDate }
                    : {}),
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
            this.$watch('modalOpen', (isOpen) => {
                if (isOpen) {
                    this.syncIncidentOptionsAndCameraButtons();
                }
            });
            this.$watch(() => this.form?.status, () => {
                if (this.modalOpen) {
                    this.syncIncidentOptionsAndCameraButtons();
                }
            });
            this.$nextTick(() => {
                this.ensureSelectionFilterHeader();
                this.ensureIncludeFilterCheckboxes();
                setTimeout(() => this.ensureIncludeFilterCheckboxes(), 300);
                setTimeout(() => this.ensureIncludeFilterCheckboxes(), 800);
            });
            if (this.dataUrl) {
                const initialDate = this.uiConfig.advancedDateReloads
                    ? (this.date || this.todayDate)
                    : (this.advancedFilters.date || this.date || this.todayDate);
                await this.fetchScheduleData(initialDate);
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
                let url = `${this.dataUrl}?date=${encodeURIComponent(d)}`;
                if (this.module === 'in-person') {
                    if (this.includeExams) url += '&include_exams=1';
                    if (this.includeHomeroom) url += '&include_homeroom=1';
                }
                const fetchHeaders = {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };
                const fetchPromises = [
                    fetch(url, { headers: fetchHeaders }).then((r) => r.ok ? r.json() : null)
                ];

                const isApplicable = this.module === 'in-person' || this.module === 'online';
                if (isApplicable) {
                    if (this.includeHomeroom) {
                        const homeroomUrl = this.dataUrl.replace(`/${this.module}/data`, '/homeroom/data');
                        fetchPromises.push(
                            fetch(`${homeroomUrl}?date=${encodeURIComponent(d)}`, { headers: fetchHeaders })
                                .then((r) => r.ok ? r.json() : null)
                                .catch(() => null)
                        );
                    }
                    if (this.includeExams) {
                        const examsUrl = this.dataUrl.replace(`/${this.module}/data`, '/exams/data');
                        fetchPromises.push(
                            fetch(`${examsUrl}?date=${encodeURIComponent(d)}`, { headers: fetchHeaders })
                                .then((r) => r.ok ? r.json() : null)
                                .catch(() => null)
                        );
                    }
                }

                const results = await Promise.all(fetchPromises);
                const payload = results[0];
                if (!payload) throw new Error('Không tải được lịch');

                let combinedItems = [...(payload.items || [])];
                const seenIds = new Set(combinedItems.map((item) => String(item.id)));
                const targetFilter = this.module === 'online' ? isOnlineSchedule : isPhysicalRoomSchedule;

                for (let i = 1; i < results.length; i++) {
                    const extraPayload = results[i];
                    if (!extraPayload || !Array.isArray(extraPayload.items)) continue;
                    extraPayload.items.forEach((item) => {
                        const idStr = String(item.id);
                        if (!seenIds.has(idStr) && targetFilter(item)) {
                            seenIds.add(idStr);
                            combinedItems.push(item);
                        }
                    });
                }

                combinedItems = multiSortRows(combinedItems, [], ['period', 'room', 'id']);

                this.items = applyLocalNotesToItems(this.module, combinedItems);
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
            setTimeout(() => { this.toast = null; }, 30000);
        },

        get visibleColumnKeys() {
            return this.columnOrder.filter((k) => this.columns[k] && this.columnVisibility[k]);
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

        get modalTitle() {
            void this.currentLang;
            const entity = t(this.modalEntity, this.currentLang) || this.modalEntity;
            if (this.modalMode === 'view') return `${t('Chi tiết', this.currentLang)} ${entity}`;
            if (this.modalMode === 'edit') return `${t('Ghi nhận', this.currentLang)} ${entity}`;
            if (this.modalMode === 'copy') return `${t('Sao chép', this.currentLang)} ${entity}`;
            return `${t('Thêm mới', this.currentLang)} ${entity}`;
        },

        text(key) {
            return typeof t === "function" ? t(key, this.currentLang) : key;
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

        detectItemModule(item) {
            if (!item) return this.module || 'in-person';
            const statusRaw = String(item.status || '').trim();
            const status = statusRaw.toLowerCase();
            const content = String(item.content || '').toLowerCase();
            const building = String(item.building || '').toLowerCase();
            const room = String(item.room || '').toLowerCase();

            // 1. Lớp thi / Phòng thi (kể cả trực tiếp và trực tuyến)
            if (
                statusRaw === 'Phòng thi' ||
                statusRaw === 'Thi cuối kỳ' ||
                status.includes('phòng thi') ||
                status.includes('thi kết thúc') ||
                status.includes('thi cuối kỳ') ||
                building.includes('thi trực tuyến') ||
                room.includes('thi trực tuyến') ||
                building.startsWith('thi') ||
                room.startsWith('thi') ||
                content.includes('thi kết thúc') ||
                content.includes('thi kthm') ||
                content.includes('thi kthp')
            ) {
                return 'exams';
            }

            // 2. Sinh hoạt lớp / Cố vấn học tập (kể cả trực tiếp và trực tuyến)
            const homeroomKeywords = ['cvht', 'shcn', 'cố vấn', 'sinh hoạt', 'chủ nhiệm'];
            if (homeroomKeywords.some((kw) => content.includes(kw) || status.includes(kw) || building.includes(kw) || room.includes(kw))) {
                return 'homeroom';
            }

            // 3. Thực hành ngoài
            if (building.includes('ngoài') || building.includes('thực hành ngoài') || room.includes('ngoài') || room.includes('ngoai') || item.building === 'THN') {
                return 'external-practice';
            }

            // 4. Lớp học trực tuyến
            if (building.includes('trực tuyến') || room.includes('trực tuyến') || building.includes('online') || room.includes('online')) {
                return 'online';
            }

            return this.module || 'in-person';
        },

        get currentItemModule() {
            return this.detectItemModule(this.form);
        },

        get currentIncidentCategories() {
            const mod = this.currentItemModule || this.module || 'in-person';
            let rawList = (this.incidentCategoriesByModule && (
                    this.incidentCategoriesByModule[mod] ||
                    (mod === 'exams' && (this.incidentCategoriesByModule['Thi trực tuyến'] || this.incidentCategoriesByModule['Thi kết thúc học phần'])) ||
                    (mod === 'homeroom' && this.incidentCategoriesByModule['Cố vấn học tập']) ||
                    (mod === 'online' && this.incidentCategoriesByModule['Lớp học trực tuyến']) ||
                    (mod === 'in-person' && this.incidentCategoriesByModule['Lớp học trực tiếp'])
                ))
                || NTTU_INCIDENT_CATEGORIES[mod]
                || this.incidentCategories
                || [];

            let list = (rawList || []).map((c, i) => {
                if (typeof c === 'string') {
                    return { id: String(i), name: c };
                }
                return { id: c.id || String(i), name: c.name || String(c) };
            });

            const current = this.form?.incident;
            if (current && !list.some((c) => c.name === current)) {
                list = [{ id: 'custom', name: current }, ...list];
            }
            return list;
        },

        get isCameraExtractSupported() {
            return ['homeroom', 'exams'].includes(this.currentItemModule) || ['homeroom', 'exams'].includes(this.module);
        },

        get cameraModalTitle() {
            return this.currentItemModule === 'exams' || this.module === 'exams'
                ? 'Chụp ảnh phiếu ghi nhận phòng thi'
                : 'Chụp ảnh phiếu sinh hoạt';
        },

        get cameraGuideText() {
            return this.currentItemModule === 'exams' || this.module === 'exams'
                ? 'Đưa phiếu vào giữa khung, đủ sáng, rõ mục "Vi phạm quy chế" hoặc "Tình hình phòng thi".'
                : 'Đưa phiếu vào giữa khung, đủ sáng, rõ mục "Nội dung sinh hoạt (CVHT)".';
        },

        get isIncidentDetailRequired() {
            return ['homeroom', 'external-practice'].includes(this.currentItemModule) || ['homeroom', 'external-practice'].includes(this.module);
        },

        get requiresIncident() {
            return !!this.uiConfig.requiresIncident || ['homeroom', 'external-practice'].includes(this.currentItemModule) || ['homeroom', 'external-practice'].includes(this.module);
        },

        syncIncidentOptionsAndCameraButtons() {
            const doSync = () => {
                const root = this.$root;
                if (!root) return;

                const targetModule = this.currentItemModule || this.module || 'in-person';

                // 1. Sync Incident Categories in <select x-model="form.incident">
                const incidentSelect = root.querySelector('select[x-model="form.incident"]');
                if (incidentSelect) {
                    const rawCategories = (this.incidentCategoriesByModule && (
                            this.incidentCategoriesByModule[targetModule] ||
                            (targetModule === 'exams' && (this.incidentCategoriesByModule['Thi trực tuyến'] || this.incidentCategoriesByModule['Thi kết thúc học phần'])) ||
                            (targetModule === 'homeroom' && this.incidentCategoriesByModule['Cố vấn học tập']) ||
                            (targetModule === 'online' && this.incidentCategoriesByModule['Lớp học trực tuyến']) ||
                            (targetModule === 'in-person' && this.incidentCategoriesByModule['Lớp học trực tiếp'])
                        ))
                        || NTTU_INCIDENT_CATEGORIES[targetModule]
                        || this.incidentCategories
                        || [];

                    const catNames = rawCategories.map((c) => (typeof c === 'string' ? c : (c.name || String(c))));
                    const currentVal = String(this.form?.incident || '').trim();
                    const requires = this.requiresIncident;

                    const existingOpts = Array.from(incidentSelect.options).map((o) => o.value).filter(Boolean);
                    const isMismatch = catNames.length > 0 && !catNames.some((name) => existingOpts.includes(name));

                    if (existingOpts.length === 0 || isMismatch) {
                        let html = `<option value="" ${!requires ? '' : 'disabled hidden'}>${requires ? '— Chọn việc phát sinh —' : '--- Không có ---'}</option>`;
                        if (currentVal && !catNames.includes(currentVal)) {
                            catNames.unshift(currentVal);
                        }
                        for (const name of catNames) {
                            const selected = (currentVal === name) ? 'selected' : '';
                            html += `<option value="${name}" ${selected}>${name}</option>`;
                        }
                        incidentSelect.innerHTML = html;
                        if (currentVal) {
                            incidentSelect.value = currentVal;
                        }
                        incidentSelect.onchange = (e) => {
                            this.form.incident = e.target.value;
                        };
                    }
                }

                // 2. Sync Camera & Photo Upload Buttons
                const detailInput = root.querySelector('input[x-model="form.incident_detail"]');
                if (detailInput) {
                    const btnContainer = detailInput.parentElement?.querySelector('.absolute.inset-y-0.right-2');
                    if (btnContainer) {
                        const isSupported = ['exams', 'homeroom'].includes(targetModule) || ['exams', 'homeroom'].includes(this.module);

                        if (isSupported && !this.isViewMode) {
                            detailInput.classList.add('pr-20');
                            const bladeButtons = btnContainer.querySelectorAll('button:not(.btn-incident-camera-dynamic):not(.btn-incident-upload-dynamic)');
                            if (bladeButtons.length > 0) {
                                bladeButtons.forEach((btn) => {
                                    btn.style.setProperty('display', 'inline-flex', 'important');
                                });
                            } else {
                                let existingCameraBtn = btnContainer.querySelector('.btn-incident-camera-dynamic');
                                let existingUploadBtn = btnContainer.querySelector('.btn-incident-upload-dynamic');

                                if (!existingCameraBtn) {
                                    const camBtn = document.createElement('button');
                                    camBtn.type = 'button';
                                    camBtn.className = 'btn-incident-camera-dynamic inline-flex h-7 w-7 items-center justify-center rounded text-orange-600 hover:bg-orange-100';
                                    camBtn.title = 'Chụp ảnh phiếu để trích xuất nội dung';
                                    camBtn.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
                                    camBtn.onclick = (e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        this.openIncidentDetailCamera();
                                    };
                                    btnContainer.appendChild(camBtn);
                                } else {
                                    existingCameraBtn.style.setProperty('display', 'inline-flex', 'important');
                                }

                                if (!existingUploadBtn) {
                                    const uploadBtn = document.createElement('button');
                                    uploadBtn.type = 'button';
                                    uploadBtn.className = 'btn-incident-upload-dynamic inline-flex h-7 w-7 items-center justify-center rounded text-orange-600 hover:bg-orange-100';
                                    uploadBtn.title = 'Tải ảnh phiếu từ máy để trích xuất nội dung';
                                    uploadBtn.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
                                    uploadBtn.onclick = (e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        this.$refs.incidentDetailPhotoInput?.click();
                                    };
                                    btnContainer.appendChild(uploadBtn);
                                } else {
                                    existingUploadBtn.style.setProperty('display', 'inline-flex', 'important');
                                }
                            }
                        } else {
                            const dynCam = btnContainer.querySelector('.btn-incident-camera-dynamic');
                            const dynUp = btnContainer.querySelector('.btn-incident-upload-dynamic');
                            if (dynCam) dynCam.style.display = 'none';
                            if (dynUp) dynUp.style.display = 'none';
                            const bladeButtons = btnContainer.querySelectorAll('button:not(.btn-incident-camera-dynamic):not(.btn-incident-upload-dynamic)');
                            bladeButtons.forEach((btn) => {
                                btn.style.display = 'none';
                            });
                            detailInput.classList.remove('pr-20');
                        }
                    }
                }
            };

            this.$nextTick(doSync);
            setTimeout(doSync, 50);
            setTimeout(doSync, 150);
            setTimeout(doSync, 300);
            setTimeout(doSync, 600);
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

            if (this.module === 'in-person' || this.module === 'online') {
                if (this.includeExams) params.set('include_exams', '1');
                if (this.includeHomeroom) params.set('include_homeroom', '1');
            }

            const qs = params.toString();

            return qs ? `${base}?${qs}` : base;
        },

        exportExcel(event) {
            const base = this.exportUrlBase || (this.dataUrl ? this.dataUrl.replace(/\/data$/, '/export') : '');
            if (!base) return;
            if (event) event.preventDefault();

            const isCustomFiltered = this.selectionFilter === 'selected' || (this.items && this.filteredItems.length < this.items.length);
            const ids = isCustomFiltered ? this.filteredItems.map((item) => item.id).filter(Boolean) : [];

            const f = this.advancedFilters;
            const payload = {
                date: this.currentDate || '',
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
            if (this.module === 'in-person' || this.module === 'online') {
                if (this.includeExams) payload.include_exams = '1';
                if (this.includeHomeroom) payload.include_homeroom = '1';
            }
            submitExportPost(base, payload);
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

        get activeSortRules() {
            return normalizeSortRules(this.sortConfig);
        },

        sortDirection(key) {
            const rule = this.activeSortRules.find((r) => r.key === key);
            return rule ? rule.direction : null;
        },

        sortPriority(key) {
            if (this.activeSortRules.length <= 1) return null;
            const index = this.activeSortRules.findIndex((r) => r.key === key);
            return index >= 0 ? index + 1 : null;
        },

        isPrimarySort(key) {
            return this.activeSortRules[0]?.key === key;
        },

        isSortRule(key, direction, priority = 1) {
            const rule = this.activeSortRules[priority - 1];
            return rule && rule.key === key && rule.direction === direction;
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
            this.sortConfig = [{ key, direction }];
            localStorage.setItem(sortKey, JSON.stringify(this.sortConfig));
            this.headerPopover = null;
        },

        requestSecondarySort(key, direction) {
            const current = this.activeSortRules;
            const primary = current[0] || { key, direction };
            if (primary.key === key) {
                this.sortConfig = [{ key, direction }];
            } else {
                this.sortConfig = [primary, { key, direction }];
            }
            localStorage.setItem(sortKey, JSON.stringify(this.sortConfig));
            this.headerPopover = null;
        },

        removeSort(key) {
            const filtered = this.activeSortRules.filter((r) => r.key !== key);
            this.sortConfig = filtered.length > 0 ? filtered : null;
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

        async clearAllFilters() {
            this.columnFilters = {};
            this.selectionFilter = 'all';
            this.sortConfig = null;
            localStorage.removeItem(colFiltersKey);
            localStorage.removeItem(sortKey);
            localStorage.removeItem(selFilterKey);
            this.updateSelectionHeaderBadge();
            await this.resetAdvanced();
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
            const th = this.$root?.querySelector?.('.catalog-th-index');
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
                    funnelSvg.classList.remove('opacity-70');
                } else {
                    funnelSvg.classList.remove('!text-yellow-300', 'font-bold', 'drop-shadow');
                    funnelSvg.classList.add('opacity-70');
                }
            }
            if (this.selectionFilter !== 'all') {
                th.classList.add('bg-blue-600', 'text-white');
            } else {
                th.classList.remove('bg-blue-600', 'text-white');
            }
        },

        ensureSelectionFilterHeader() {
            const th = this.$root?.querySelector?.('.catalog-th-index');
            if (!th || th.querySelector('.btn-selection-filter')) return;

            th.classList.add('relative', 'p-0');
            th.innerHTML = `
                <div class="relative flex items-center justify-center h-full">
                    <button type="button" class="btn-selection-filter flex items-center justify-center gap-1 w-full h-full py-2 px-1 hover:bg-black/10 transition-colors" title="Lọc theo dòng đã chọn">
                        <span class="font-bold">#</span>
                        <svg class="h-3 w-3 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        toggleIncludeFilter() {
            localStorage.setItem(`nttu_${this.module}_include_exams`, this.includeExams ? '1' : '0');
            localStorage.setItem(`nttu_${this.module}_include_homeroom`, this.includeHomeroom ? '1' : '0');

            document.querySelectorAll(`.${this.module}-chk-exams, .chk-exams`).forEach((el) => { el.checked = !!this.includeExams; });
            document.querySelectorAll(`.${this.module}-chk-homeroom, .chk-homeroom`).forEach((el) => { el.checked = !!this.includeHomeroom; });

            this.fetchScheduleData(this.date);
        },

        ensureIncludeFilterCheckboxes() {
            if (this.module !== 'in-person' && this.module !== 'online') return;
            const containerId = `${this.module}-include-filters`;
            const existing = document.getElementById(containerId);
            if (existing) {
                const ce = existing.querySelector('.chk-exams');
                const ch = existing.querySelector('.chk-homeroom');
                if (ce) ce.checked = !!this.includeExams;
                if (ch) ch.checked = !!this.includeHomeroom;
                return;
            }

            const container = this.$root?.querySelector?.('h2')
                || document.querySelector('.nttu-card h2')
                || document.querySelector('h2');
            if (!container) return;

            const examLabel = this.module === 'online' ? 'Lớp thi' : 'Phòng thi';
            const div = document.createElement('div');
            div.id = containerId;
            div.className = 'inline-flex items-center gap-2.5 ml-3 text-xs font-normal text-gray-700 select-none bg-blue-50/80 px-2.5 py-1 rounded-md border border-blue-200';
            div.innerHTML = `
                <span class="text-gray-500 font-medium text-[11px]">Bao gồm:</span>
                <label class="inline-flex items-center gap-1.5 cursor-pointer hover:text-blue-700">
                    <input type="checkbox" class="${this.module}-chk-exams chk-exams rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                    <span class="font-medium text-gray-800">${examLabel}</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer hover:text-blue-700">
                    <input type="checkbox" class="${this.module}-chk-homeroom chk-homeroom rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                    <span class="font-medium text-gray-800">Sinh hoạt</span>
                </label>
            `;
            const chkExams = div.querySelector('.chk-exams');
            const chkHomeroom = div.querySelector('.chk-homeroom');
            chkExams.checked = !!this.includeExams;
            chkHomeroom.checked = !!this.includeHomeroom;

            chkExams.onchange = () => {
                this.includeExams = chkExams.checked;
                this.toggleIncludeFilter();
            };
            chkHomeroom.onchange = () => {
                this.includeHomeroom = chkHomeroom.checked;
                this.toggleIncludeFilter();
            };

            container.appendChild(div);
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
            const isCreateWithoutRecording = (mode === 'add' || mode === 'copy')
                && this.uiConfig.recordingOnlyOnEdit;

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
                employee: isCreateWithoutRecording ? '' : (item?.employee || this.employeeDefault),
                attending_students: isCreateWithoutRecording ? '' : (item?.attending_students ?? ''),
                incident: mode === 'copy' || isCreateWithoutRecording ? '' : (item?.incident || ''),
                incident_detail: mode === 'copy' || isCreateWithoutRecording ? '' : (item?.incident_detail || ''),
                evidence: mode === 'copy' || isCreateWithoutRecording ? '' : (item?.evidence || ''),
                recognition_date: isCreateWithoutRecording
                    ? ''
                    : (mode === 'copy' ? this.todayDate : (item?.recognition_date || this.todayDate)),
                note: mode === 'copy' ? '' : (item?.note || ''),
                is_notification: mode === 'copy' || isCreateWithoutRecording
                    ? false
                    : parseNotificationValue(item?.is_notification),
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

            if (!isCreateWithoutRecording && (mode === 'edit' || mode === 'add' || mode === 'copy') && !item?.employee) {
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
            this.restoreLocalNoteDraft();
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
            this.$nextTick(() => {
                this.evidenceOnModalOpen();
                this.syncIncidentOptionsAndCameraButtons();
            });
        },

        openAddModal() {
            this.openModal('add', null);
        },

        confirmDelete(item) {
            this.deleteTarget = item;
            this.deleteOpen = true;
            this.rowMenuOpen = null;
        },

        confirmClearRecording(item) {
            if (!this.isHandled(item)) {
                return;
            }
            this.clearRecordingTarget = item;
            this.clearRecordingOpen = true;
            this.rowMenuOpen = null;
        },

        async extractIncidentDetailFromPhoto(event) {
            const file = event?.target?.files?.[0];
            if (event?.target) {
                event.target.value = '';
            }
            if (!file) {
                return;
            }

            let dataUrl = '';
            try {
                dataUrl = await compressIncidentPhoto(file);
            } catch (e) {
                this.showToast(e.message || 'Không đọc được file ảnh.', 'error');
                return;
            }

            await this.extractIncidentDetailFromDataUrl(dataUrl);
        },

        async extractIncidentDetailFromDataUrl(dataUrl) {
            this.incidentDetailExtracting = true;
            this.toast = null;
            try {
                const targetModule = this.currentItemModule || this.module;
                const res = await fetch(`/monitoring/${targetModule}/extract-incident-detail`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ photo: dataUrl }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Không trích xuất được nội dung từ ảnh.');
                }

                const current = String(this.form.incident_detail || '').trim();
                this.form.incident_detail = current ? `${current}; ${data.text}` : data.text;
                this.showToast(data.message || 'Đã trích xuất nội dung từ ảnh phiếu.', 'success');
            } catch (e) {
                this.showToast(e.message || 'Không trích xuất được nội dung từ ảnh.', 'error');
            } finally {
                this.incidentDetailExtracting = false;
            }
        },

        openIncidentDetailCamera() {
            if (!navigator.mediaDevices?.getUserMedia) {
                // Không có webcam API (trình duyệt cũ) — dùng input capture của thiết bị.
                this.$refs.incidentDetailCameraInput?.click();
                return;
            }
            this.incidentCameraOpen = true;
            this.$nextTick(() => {
                this.startIncidentDetailCamera();
                const root = this.$root;
                if (root) {
                    const titleText = this.currentItemModule === 'exams'
                        ? 'Chụp ảnh phiếu ghi nhận phòng thi'
                        : 'Chụp ảnh phiếu sinh hoạt';
                    const h3 = root.querySelector('[x-show="incidentCameraOpen"] h3');
                    if (h3) {
                        const iconSvg = h3.querySelector('svg')?.outerHTML || '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
                        h3.innerHTML = `${iconSvg} <span>${titleText}</span>`;
                    }
                    const guideP = root.querySelector('[x-show="incidentCameraOpen"] p.text-xs');
                    if (guideP) {
                        guideP.textContent = this.currentItemModule === 'exams'
                            ? 'Đưa phiếu vào giữa khung, đủ sáng, rõ mục "Vi phạm quy chế" hoặc "Tình hình phòng thi".'
                            : 'Đưa phiếu vào giữa khung, đủ sáng, rõ mục "Nội dung sinh hoạt (CVHT)".';
                    }
                }
            });
        },

        async startIncidentDetailCamera() {
            this.stopIncidentDetailCamera();
            try {
                const video = this.incidentCameraDeviceId
                    ? { deviceId: { exact: this.incidentCameraDeviceId }, width: { ideal: 1920 }, height: { ideal: 1080 } }
                    : { facingMode: { ideal: this.incidentCameraFacing }, width: { ideal: 1920 }, height: { ideal: 1080 } };
                const stream = await navigator.mediaDevices.getUserMedia({ video });
                this.incidentCameraStream = stream;
                const el = this.$refs.incidentDetailVideo;
                if (el) el.srcObject = stream;
                await this.refreshIncidentCameraDevices();
            } catch (_) {
                this.closeIncidentDetailCamera();
                this.showToast('Không thể truy cập camera. Hãy cấp quyền camera hoặc dùng nút tải ảnh.', 'error');
            }
        },

        async refreshIncidentCameraDevices() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.incidentCameraDevices = devices
                    .filter((d) => d.kind === 'videoinput')
                    .map((d, i) => {
                        const raw = (d.label || '').toLowerCase();
                        let label = d.label;
                        if (raw.includes('front') || raw.includes('user') || raw.includes('trước') || raw.includes('selfie')) {
                            label = '📷 Camera trước';
                        } else if (raw.includes('back') || raw.includes('rear') || raw.includes('sau') || raw.includes('environment')) {
                            label = '📷 Camera sau';
                        } else if (i === 0 && devices.length > 1) {
                            label = d.label || '📷 Camera sau (1)';
                        } else if (i === 1 && devices.length > 1) {
                            label = d.label || '📷 Camera trước (2)';
                        } else {
                            label = d.label || `📷 Camera ${i + 1}`;
                        }
                        return { id: d.deviceId, label };
                    });

                if (!this.incidentCameraDeviceId) {
                    const current = this.incidentCameraStream?.getVideoTracks?.()[0]?.getSettings?.()?.deviceId;
                    if (current) this.incidentCameraDeviceId = current;
                }
            } catch (_) {
                this.incidentCameraDevices = [];
            }
        },

        async selectIncidentDetailCamera(deviceId) {
            this.incidentCameraDeviceId = deviceId;
            await this.startIncidentDetailCamera();
        },

        async flipIncidentDetailCamera() {
            this.incidentCameraFacing = this.incidentCameraFacing === 'environment' ? 'user' : 'environment';
            this.incidentCameraDeviceId = '';
            await this.startIncidentDetailCamera();
        },

        stopIncidentDetailCamera() {
            if (this.incidentCameraStream) {
                this.incidentCameraStream.getTracks().forEach((t) => t.stop());
                this.incidentCameraStream = null;
            }
            const video = this.$refs.incidentDetailVideo;
            if (video) video.srcObject = null;
        },

        closeIncidentDetailCamera() {
            this.stopIncidentDetailCamera();
            this.incidentCameraOpen = false;
        },

        async captureIncidentDetailPhoto() {
            const video = this.$refs.incidentDetailVideo;
            if (!video || !video.videoWidth) {
                this.showToast('Camera chưa sẵn sàng. Vui lòng đợi giây lát.', 'error');
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

            this.closeIncidentDetailCamera();
            await this.extractIncidentDetailFromDataUrl(dataUrl);
        },

        async clearRecording() {
            if (!this.clearRecordingTarget) {
                return;
            }

            this.saving = true;
            this.toast = null;
            try {
                const res = await fetch(`/monitoring/${this.module}/${this.clearRecordingTarget.id}/clear-recording`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Hủy ghi nhận thất bại');
                }
                const idx = this.items.findIndex((i) => i.id === data.item.id);
                if (idx >= 0) {
                    this.items[idx] = this.mergeMonitoringItem(this.items[idx], data.item);
                }
                this.clearRecordingOpen = false;
                this.clearRecordingTarget = null;
                this.showToast(data.message, 'success');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        get isChanged() {
            const excludeNote = this.persistLocalNoteEnabled;
            const form = formSnapshotWithoutLocalNote(this.form, excludeNote);
            const initial = formSnapshotWithoutLocalNote(this.initialForm, excludeNote);

            return JSON.stringify(form) !== JSON.stringify(initial);
        },

        undoForm() {
            this.form = JSON.parse(JSON.stringify(this.initialForm));
            this.restoreLocalNoteDraft();
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
            this.persistLocalNoteDraft();
            this.evidenceCleanupPanel();
            this.closeIncidentDetailCamera();
            this.modalOpen = false;
        },

        localNoteDraftKey() {
            return buildLocalNoteDraftKey(this.form);
        },

        restoreLocalNoteDraft() {
            if (!this.persistLocalNoteEnabled) {
                return;
            }

            const map = readLocalNotesMap(this.module);
            const key = this.localNoteDraftKey();
            if (!Object.prototype.hasOwnProperty.call(map, key)) {
                return;
            }

            this.form.note = map[key];
        },

        persistLocalNoteDraft() {
            if (!this.persistLocalNoteEnabled) {
                return;
            }

            const map = readLocalNotesMap(this.module);
            const key = this.localNoteDraftKey();
            const note = String(this.form.note ?? '');

            if (note.trim() === '') {
                delete map[key];
            } else {
                map[key] = note;
            }

            writeLocalNotesMap(this.module, map);
            this.syncLocalNoteToTableRow();
        },

        syncLocalNoteToTableRow() {
            if (!this.persistLocalNoteEnabled || !this.form?.id) {
                return;
            }

            const idx = this.items.findIndex((item) => item.id === this.form.id);
            if (idx < 0) {
                return;
            }

            this.items[idx] = {
                ...this.items[idx],
                note: String(this.form.note ?? ''),
            };
        },

        clearLocalNoteDraftSilently() {
            if (!this.persistLocalNoteEnabled) {
                return;
            }

            const map = readLocalNotesMap(this.module);
            delete map[this.localNoteDraftKey()];
            writeLocalNotesMap(this.module, map);
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
                this.showToast('Vui lòng chọn Việc phát sinh.', 'error');
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
                this.showToast(data.message, 'success');
                this.clearLocalNoteDraftSilently();
                setTimeout(() => this.closeModal(), 600);
            } catch (e) {
                this.showToast(e.message, 'error');
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
                this.showToast(data.message, 'success');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        isFetchingMeetLink: false,
        async fetchMeetLinkFromEmail(source = 'email') {
            if (this.isFetchingMeetLink) return;
            this.isFetchingMeetLink = true;
            try {
                const res = await fetch('/monitoring/fetch-meet-link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        module: this.module,
                        source: source,
                        room: this.form.room || '',
                        class: this.form.class || '',
                        lecturer: this.form.lecturer_name || this.form.lecturer || '',
                        period: this.form.period || '',
                        subject: this.form.module_name || this.form.content || '',
                        department: this.form.department || ''
                    })
                });
                
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.error || 'Lỗi hệ thống');
                }
                
                if (!this.form.note) {
                    this.form.note = data.link;
                } else {
                    if (!this.form.note.includes(data.link)) {
                        this.form.note += ' ' + data.link;
                    }
                }
                if (typeof this.persistLocalNoteDraft === 'function') {
                    this.persistLocalNoteDraft();
                }
                if (this.showToast) {
                    this.showToast('Đã tìm thấy và lưu Link Meet!', 'success');
                } else {
                    alert('Đã tìm thấy: ' + data.link);
                }
            } catch (e) {
                if (this.showToast) {
                    this.showToast(e.message, 'error');
                } else {
                    alert(e.message);
                }
            } finally {
                this.isFetchingMeetLink = false;
            }
        },

        ...evidencePanelMethods(),
    };
}

window.monitoringSchedulesPage = monitoringSchedulesPage;

