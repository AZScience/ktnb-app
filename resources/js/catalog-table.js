import { applyDocumentRecordFormDefaults, documentRecordFormMethods, documentRecordFormState } from './document-record-form.js';
import { assetReceptionFormMethods, assetReceptionFormState, ensureDepartmentInOptions } from './asset-reception-form.js';
import { evidencePanelMethods, evidencePanelState } from './evidence-input.js';
import { petitionFormMethods, petitionFormState } from './petition-form.js';
import {
    serviceRequestFormMethods,
    serviceRequestFormState,
    serviceRequestResolutionCode,
    serviceRequestResolutionLabel,
} from './service-request-form.js';
import { violationFormMethods, violationFormState } from './student-violation-form.js';
import { headerIconClassForKey, headerIconPathForColumn } from './nttu-icons.js';
import { downloadImportTemplate } from './import-template.js';
import { t, getLanguage, translateMessage, applyI18n } from './language.js';
import { createNttuMultiSelectMixin } from './nttu-multi-select.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice } from './nttu-pagination.js';
import { catalogCkeditorKeys, catalogCkeditorMethods, isCkeditorContentEmpty, preloadCatalogCkeditorCompose, resetCatalogCkeditorState } from './catalog-ckeditor.js';
import {
    TABLE_EMPTY_FILTERED,
    TABLE_EMPTY_NO_DATA,
    TABLE_LOADING,
    TABLE_SUBTITLE_EMPTY,
    TABLE_SUBTITLE_FILTERED,
    TABLE_SUBTITLE_LOADING,
} from './nttu-table-messages.js';

async function readJsonResponse(res) {
    const text = await res.text();
    if (!text) {
        return {};
    }

    try {
        return JSON.parse(text);
    } catch {
        const plain = text
            .replace(/<script[\s\S]*?<\/script>/gi, ' ')
            .replace(/<style[\s\S]*?<\/style>/gi, ' ')
            .replace(/<[^>]+>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
        const titleMatch = plain.match(/(SQLSTATE\[[^\]]+\][^.]*|Illuminate\\[^:]*:[^<]{0,200})/);
        const message = titleMatch?.[0] || plain.slice(0, 220) || `Máy chủ trả về phản hồi không hợp lệ (HTTP ${res.status}).`;
        throw new Error(message);
    }
}

function todayIso() {
    return new Date().toISOString().slice(0, 10);
}

function todayDmY() {
    const d = new Date();
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}/${d.getFullYear()}`;
}

function defaultAdvancedFilters(mode) {
    if (mode === 'violations') {
        return {
            filterDate: todayIso(),
            buildings: [],
            officers: [],
            periodSession: 'all',
            periodStart: '',
            periodEnd: '',
        };
    }

    if (mode === 'assets') {
        return {
            start_date: '',
            end_date: '',
            buildings: [],
            officers: [],
        };
    }

    if (mode === 'documents') {
        return {
            date_type: 'issue_date',
            start_date: '',
            end_date: '',
            doc_type: '',
            title: '',
            department: '',
            assignee: '',
            issuing_body: '',
            signer: '',
        };
    }

    if (mode === 'announcements') {
        return {
            start_date: '',
            end_date: '',
            title: '',
        };
    }

    if (mode === 'incident-records') {
        return {
            start_date: '',
            end_date: '',
            officers: [],
        };
    }

    return {};
}

function normalizeAdvancedFilters(mode, raw) {
    if (mode === 'assets') {
        const filters = raw || {};

        return {
            start_date: filters.start_date || filters.filterDate || '',
            end_date: filters.end_date || filters.filterDate || '',
            buildings: Array.isArray(filters.buildings) ? [...filters.buildings] : [],
            officers: Array.isArray(filters.officers) ? [...filters.officers] : [],
        };
    }

    if (mode === 'incident-records') {
        const filters = raw || {};
        return {
            start_date: filters.start_date || '',
            end_date: filters.end_date || '',
            officers: Array.isArray(filters.officers) ? [...filters.officers] : [],
        };
    }

    if (mode === 'documents') {
        const filters = raw || {};

        return {
            date_type: filters.date_type === 'received_date' ? 'received_date' : 'issue_date',
            start_date: filters.start_date || '',
            end_date: filters.end_date || '',
            doc_type: filters.doc_type || '',
            title: filters.title || '',
            department: filters.department || '',
            assignee: filters.assignee || '',
            issuing_body: filters.issuing_body || '',
            signer: filters.signer || '',
        };
    }

    if (mode === 'announcements') {
        const filters = raw || {};

        return {
            start_date: filters.start_date || '',
            end_date: filters.end_date || '',
            title: filters.title || '',
        };
    }

    if (mode !== 'violations') {
        return {};
    }

    const filters = raw || {};
    let filterDate = filters.filterDate || todayIso();
    if (filterDate.includes('/')) {
        const [d, m, y] = filterDate.split('/');
        filterDate = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }

    return {
        filterDate,
        buildings: Array.isArray(filters.buildings) ? [...filters.buildings] : [],
        officers: Array.isArray(filters.officers) ? [...filters.officers] : [],
        periodSession: filters.periodSession || 'all',
        periodStart: filters.periodStart ?? '',
        periodEnd: filters.periodEnd ?? '',
    };
}

function parseCatalogDate(value) {
    if (!value) {
        return null;
    }

    const str = String(value).trim();
    if (str.includes('/')) {
        const [d, m, y] = str.split('/');
        if (d && m && y) {
            return new Date(`${y}-${m.padStart(2, '0')}-${String(d).padStart(2, '0')}T12:00:00`);
        }
    }

    if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
        return new Date(`${str.slice(0, 10)}T12:00:00`);
    }

    const parsed = new Date(str);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function matchesDocumentsAdvanced(item, advancedFilters) {
    const exactFields = ['doc_type', 'department', 'assignee'];
    for (const key of exactFields) {
        const value = advancedFilters[key];
        if (!value) {
            continue;
        }
        if (String(item[key] || '').toLowerCase() !== String(value).toLowerCase()) {
            return false;
        }
    }

    const containsFields = ['title', 'issuing_body', 'signer'];
    for (const key of containsFields) {
        const value = advancedFilters[key];
        if (!value) {
            continue;
        }
        if (!String(item[key] || '').toLowerCase().includes(String(value).toLowerCase())) {
            return false;
        }
    }

    const { start_date: startDate, end_date: endDate } = advancedFilters;
    if (!startDate && !endDate) {
        return true;
    }

    const dateField = advancedFilters.date_type || 'issue_date';
    const itemDate = parseCatalogDate(item[dateField]);
    if (!itemDate) {
        return false;
    }

    if (startDate) {
        const start = new Date(`${startDate}T00:00:00`);
        if (itemDate < start) {
            return false;
        }
    }

    if (endDate) {
        const end = new Date(`${endDate}T23:59:59`);
        if (itemDate > end) {
            return false;
        }
    }

    return true;
}

function matchesAnnouncementsAdvanced(item, advancedFilters) {
    if (advancedFilters.title?.trim()) {
        const query = advancedFilters.title.trim().toLowerCase();
        if (!String(item.title || '').toLowerCase().includes(query)) {
            return false;
        }
    }

    const { start_date: startDate, end_date: endDate } = advancedFilters;
    if (!startDate && !endDate) {
        return true;
    }

    const dateField = 'created_at';
    let rawDate = item[dateField];
    if (!rawDate && item.created_at_label) {
        rawDate = item.created_at_label;
    }

    const itemDate = parseCatalogDate(rawDate);
    if (!itemDate) {
        return false;
    }

    if (startDate) {
        const start = new Date(`${startDate}T00:00:00`);
        if (itemDate < start) {
            return false;
        }
    }

    if (endDate) {
        const end = new Date(`${endDate}T23:59:59`);
        if (itemDate > end) {
            return false;
        }
    }

    return true;
}

function normalizeViolationDate(value) {
    if (!value) {
        return '';
    }

    const str = String(value).trim();
    if (str.includes('/')) {
        const parts = str.split('/');
        if (parts.length === 3) {
            const [d, m, y] = parts;
            return `${y}-${m.padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        }
    }

    return str;
}

function periodStart(value) {
    const raw = String(value ?? '').split(/→|->/)[0]?.trim() ?? '';
    const num = parseInt(raw, 10);
    return Number.isNaN(num) ? null : num;
}

function violationPeriodStart(item) {
    const fromField = periodStart(item.period);
    if (fromField !== null) {
        return fromField;
    }

    if (!item.created_at) {
        return null;
    }

    const hour = new Date(item.created_at).getHours();
    if (hour >= 6 && hour < 12) {
        return 3;
    }
    if (hour >= 12 && hour < 18) {
        return 9;
    }
    if (hour >= 18 && hour < 22) {
        return 15;
    }

    return null;
}

function matchesViolationsPeriodSession(item, advancedFilters) {
    if (!advancedFilters.periodSession || advancedFilters.periodSession === 'all') {
        return true;
    }

    const start = violationPeriodStart(item);
    if (start === null) {
        return false;
    }

    const session = advancedFilters.periodSession;
    if (session === 'morning') {
        return start >= 1 && start <= 6;
    }
    if (session === 'afternoon') {
        return start >= 7 && start <= 12;
    }
    if (session === 'evening') {
        return start >= 13 && start <= 17;
    }
    if (session === 'custom') {
        const from = parseInt(advancedFilters.periodStart, 10);
        const to = parseInt(advancedFilters.periodEnd, 10);
        if (!Number.isNaN(from) && start < from) {
            return false;
        }
        if (!Number.isNaN(to) && start > to) {
            return false;
        }
    }

    return true;
}

function matchesViolationsAdvanced(item, advancedFilters) {
    if (advancedFilters.officers?.length > 0) {
        if (!item.officer || !advancedFilters.officers.includes(item.officer)) {
            return false;
        }
    }

    if (advancedFilters.buildings?.length > 0) {
        if (!item.building || !advancedFilters.buildings.includes(item.building)) {
            return false;
        }
    }

    if (advancedFilters.filterDate) {
        const itemDate = normalizeViolationDate(item.violation_date);
        if (itemDate && itemDate !== advancedFilters.filterDate) {
            return false;
        }
    }

    if (!matchesViolationsPeriodSession(item, advancedFilters)) {
        return false;
    }

    return true;
}

function matchesAssetsAdvanced(item, advancedFilters, options = {}) {
    if (advancedFilters.buildings?.length > 0) {
        const field = options.buildingFilterField || 'building_block';
        const value = item[field];
        if (!value || !advancedFilters.buildings.includes(value)) {
            return false;
        }
    }

    if (advancedFilters.officers?.length > 0) {
        const staffField = options.staffField || 'receiving_staff';
        const value = item[staffField];
        if (!value || !advancedFilters.officers.includes(value)) {
            return false;
        }
    }

    const { start_date: startDate, end_date: endDate } = advancedFilters;
    if (startDate || endDate) {
        const dateField = options.dateField || 'reception_date';
        let rawDate = item[dateField];
        if (!rawDate && dateField === 'resolution_date') {
            rawDate = item.reception_date;
        }
        if (!rawDate && dateField === 'gratitude_date') {
            rawDate = item.reception_date;
        }

        const itemDate = parseCatalogDate(rawDate);
        if (!itemDate) {
            return false;
        }

        if (startDate) {
            const start = new Date(`${startDate}T00:00:00`);
            if (itemDate < start) {
                return false;
            }
        }

        if (endDate) {
            const end = new Date(`${endDate}T23:59:59`);
            if (itemDate > end) {
                return false;
            }
        }
    }

    return true;
}

function nextReceptionEntryNumber(items) {
    const year = String(new Date().getFullYear());
    let maxNum = 0;
    (items || []).forEach((row) => {
        const entry = String(row.entry_number || '').trim();
        const match = entry.match(/^(?:KTNB-|TT-)(\d+)\/(\d{4})$/i);
        if (!match || match[2] !== year) {
            return;
        }
        const num = parseInt(match[1], 10);
        if (!Number.isNaN(num) && num > maxNum) {
            maxNum = num;
        }
    });

    return `KTNB-${String(maxNum + 1).padStart(4, '0')}/${year}`;
}

function nextGratitudeNumber(items) {
    let maxNum = 0;
    (items || []).forEach((row) => {
        const gratitude = String(row.gratitude_number || '');
        if (gratitude.endsWith('/TTA')) {
            const num = parseInt(gratitude.split('/')[0], 10);
            if (!Number.isNaN(num) && num > maxNum) {
                maxNum = num;
            }
        }
    });

    return `${String(maxNum + 1).padStart(4, '0')}/TTA`;
}

function applyAssetFormDefaults(mode, data, config) {
    const assetMode = config.formMode || '';
    const staffDefault = config.staffDefault || '';

    if (assetMode === 'asset-reception' && (mode === 'add' || mode === 'copy')) {
        data.entry_number = nextReceptionEntryNumber(config.items);
        data.reception_date = data.reception_date || todayDmY();
        data.receiving_staff = staffDefault;
        data.return_status = data.return_status || 'Chưa trả';
    }

    if (assetMode === 'asset-return' && mode === 'edit') {
        data.resolution_date = data.resolution_date || todayDmY();
        data.return_staff = data.return_staff || staffDefault;
        data.return_status = 'Đã trả';
    }

    if (assetMode === 'asset-gratitude' && mode === 'edit') {
        if (!data.gratitude_number) {
            data.gratitude_number = nextGratitudeNumber(config.items);
        }
        data.gratitude_date = data.gratitude_date || todayDmY();
        data.gratitude_staff = data.gratitude_staff || staffDefault;
    }

    return data;
}

function nextRequestTicketNumber(items) {
    let maxNum = 0;
    (items || []).forEach((row) => {
        const ticket = String(row.ticket_number || '');
        if (ticket.endsWith('/PYC')) {
            const num = parseInt(ticket.split('/')[0], 10);
            if (!Number.isNaN(num) && num > maxNum) {
                maxNum = num;
            }
        }
    });

    return `${String(maxNum + 1).padStart(4, '0')}/PYC`;
}

function applyServiceRequestFormDefaults(mode, data, config) {
    if (config.formMode !== 'service-request') {
        return data;
    }

    const staffDefault = config.staffDefault || '';
    if (mode === 'add') {
        data.ticket_number = data.ticket_number || nextRequestTicketNumber(config.items);
        data.reception_date = data.reception_date || todayDmY();
        data.request_date = data.request_date || todayDmY();
        data.recipient = data.recipient || staffDefault;
        data.status = data.status || 'pending';
        data.is_processed_immediately = data.is_processed_immediately ?? false;
        data.request_type = data.request_type || 'Phiếu yêu cầu hỗ trợ';
    }

    if (!data.evidence && data.attachments) {
        data.evidence = data.attachments;
    }

    return data;
}

function applyPetitionFormDefaults(mode, data, config) {
    if (config.formMode !== 'petition') {
        return data;
    }

    const staffDefault = config.staffDefault || '';
    if (mode === 'add') {
        data.reception_date = data.reception_date || todayDmY();
        data.recipient = data.recipient || staffDefault;
        data.petition_type = data.petition_type || 'Kiến nghị';
        data.number_of_people = data.number_of_people ?? 1;
        data.is_accepted = data.is_accepted ?? false;
        data.is_returned = data.is_returned ?? false;
        data.is_forwarded = data.is_forwarded ?? false;
    }

    return data;
}

function matchesIncidentRecordsAdvanced(item, advancedFilters) {
    if (advancedFilters.officers?.length > 0) {
        if (!item.creator_name || !advancedFilters.officers.includes(item.creator_name)) {
            return false;
        }
    }

    const { start_date: startDate, end_date: endDate } = advancedFilters;
    if (!startDate && !endDate) {
        return true;
    }

    const dateStr = String(item.incident_time || '').substring(0, 10);
    if (!dateStr) return false;

    if (startDate && dateStr < startDate) return false;
    if (endDate && dateStr > endDate) return false;

    return true;
}

export function catalogTablePage(config) {
    const storagePrefix = config.storageKey || 'catalog';
    const loadJson = (key, fallback) => {
        try {
            return JSON.parse(localStorage.getItem(`${storagePrefix}_${key}`) || 'null') ?? fallback;
        } catch {
            return fallback;
        }
    };
    const saveJson = (key, value) => {
        localStorage.setItem(`${storagePrefix}_${key}`, JSON.stringify(value));
    };

    const defaultVisibility = {};
    Object.keys(config.columns).forEach((k) => {
        defaultVisibility[k] = config.columns[k].visible !== false;
    });

    const formFields = config.formFields || [
        { key: 'name', label: 'Tên chức vụ', required: true },
        { key: 'note', label: 'Ghi chú' },
    ];

    const formDefaults = config.formDefaults || {};
    const formSections = config.formSections || [];
    const formLayout = config.formLayout || 'simple';

    const formFieldMap = {};
    formFields.forEach((field) => {
        formFieldMap[field.key] = field;
    });

    Object.keys(config.columns).forEach((key) => {
        const field = formFieldMap[key];
        if (field?.icon && !config.columns[key].icon) {
            config.columns[key] = { ...config.columns[key], icon: field.icon };
        }
        if (field?.iconTone && !config.columns[key].iconTone) {
            config.columns[key] = { ...config.columns[key], iconTone: field.iconTone };
        }
    });

    const hasEvidenceField = formFields.some((field) => field.type === 'evidence');
    const ckeditorFieldKeys = catalogCkeditorKeys(formFields);
    const hasCkeditorField = ckeditorFieldKeys.length > 0;
    const evidenceField = formFields.find((field) => field.type === 'evidence');
    const evidenceFieldKey = evidenceField?.key || 'evidence';
    const hasViolationForm = config.formMode === 'violations';
    const hasAssetForm = String(config.formMode || '').startsWith('asset-');
    const hasAssetReceptionForm = config.formMode === 'asset-reception';
    const hasAssetReturnForm = config.formMode === 'asset-return';
    const hasPetitionForm = config.formMode === 'petition';
    const hasDocumentRecordForm = config.formMode === 'document-record';
    const hasServiceRequestForm = config.formMode === 'service-request';
    const allowedDialogModes = Array.isArray(config.allowedDialogModes) && config.allowedDialogModes.length
        ? config.allowedDialogModes
        : ['add', 'edit', 'view', 'copy'];

    const multiSelectMixin = createNttuMultiSelectMixin({
        resolveOptions(field) {
            if (field === 'buildings') {
                return this.advancedFilterOptions.buildings || [];
            }
            if (field === 'officers') {
                return this.advancedFilterOptions.officers || [];
            }

            return [];
        },
    });

    const emptyForm = () => {
        const data = {};
        formFields.forEach((field) => {
            if (field.type === 'violation_verification') {
                return;
            }
            if (field.type === 'checkbox') {
                data[field.key] = formDefaults[field.key] ?? false;
            } else if (field.type === 'number') {
                data[field.key] = formDefaults[field.key] ?? '';
            } else {
                data[field.key] = formDefaults[field.key] ?? '';
            }
        });
        return data;
    };

    const itemToForm = (item) => {
        const data = {};
        formFields.forEach((field) => {
            if (field.type === 'violation_verification') {
                return;
            }
            if (field.type === 'checkbox') {
                data[field.key] = !!item[field.key];
            } else if (field.type === 'number') {
                data[field.key] = item[field.key] ?? '';
            } else {
                data[field.key] = item[field.key] ?? '';
            }
        });
        return data;
    };

    const normalizeSortConfig = (raw) => {
        if (Array.isArray(raw)) {
            return raw.map((entry) => ({
                key: entry.key,
                direction: entry.direction === 'desc' || entry.direction === 'descending' ? 'descending' : 'ascending',
            }));
        }
        if (raw?.key) {
            return [{
                key: raw.key,
                direction: raw.direction === 'desc' || raw.direction === 'descending' ? 'descending' : 'ascending',
            }];
        }
        return [];
    };

    const matchesFilter = (item, key, value, columns) => {
        if (!value) return true;
        if (key === 'building_block_id' || key === 'department' || key === 'role_id' || key === 'position') {
            return item[key] === value;
        }
        const raw = item[key];
        const colType = columns[key]?.type;
        if (colType === 'boolean_status') {
            const label = raw ? 'ngưng hoạt động' : 'đang hoạt động';
            return label.includes(String(value).toLowerCase());
        }
        if (colType === 'boolean_yesno') {
            const label = raw ? 'có' : 'không';
            return label.includes(String(value).toLowerCase());
        }
        if (colType === 'service_resolution') {
            return serviceRequestResolutionCode(item) === value;
        }
        if (typeof raw === 'boolean') {
            const label = raw ? 'có' : 'không';
            return label.includes(String(value).toLowerCase());
        }
        return String(raw ?? '').toLowerCase().includes(String(value).toLowerCase());
    };

    return {
        ...multiSelectMixin,
        titleKey: config.title,
        entityLabel: config.entityLabel,
        columns: config.columns,
        formFields,
        formSections,
        formLayout,
        formFieldMap,
        routes: config.routes,
        importColumns: config.importColumns || [],
        importTemplateFilename: config.importTemplateFilename || 'Mau_Import.xlsx',
        importTemplateOptions: config.importTemplateOptions || {},
        items: config.items || [],
        serverPaginated: config.serverPaginated === true,
        serverMeta: { total: 0, current_page: 1, last_page: 1, per_page: 25 },
        listLoading: false,
        importProgress: null,
        _listFetchTimer: null,
        advancedFilterMode: config.advancedFilterMode || null,
        advancedFilterOptions: config.advancedFilterOptions || {},
        allowAdd: config.allowAdd !== false,
        canDelete: config.canDelete !== false,
        allowedDialogModes,
        editActionLabelKey: config.editActionLabel || 'Chỉnh sửa',
        modalTitle: config.modalTitle || null,
        currentLang: getLanguage(),
        staffDefault: config.staffDefault || '',
        buildingOptions: config.buildingOptions || [],
        docTypeOptions: config.docTypeOptions || [],
        departmentOptions: config.departmentOptions || [],
        employeeOptions: config.employeeOptions || [],
        urgencyOptions: config.urgencyOptions || [],
        confidentialityOptions: config.confidentialityOptions || [],
        documentStatusOptions: config.documentStatusOptions || [],
        requestTypeOptions: config.requestTypeOptions || [],
        formMode: config.formMode || null,

        ...(hasEvidenceField ? {
            ...evidencePanelState(config.evidenceUploadUrl || ''),
            evidenceFieldKey,
        } : {}),
        ...(hasViolationForm ? violationFormState(config) : {}),
        ...(hasServiceRequestForm ? serviceRequestFormState() : {}),
        ...(hasAssetReceptionForm ? assetReceptionFormState() : {}),
        ...(hasPetitionForm ? petitionFormState() : {}),
        ...(hasDocumentRecordForm ? documentRecordFormState(config) : {}),
        ...(hasCkeditorField ? catalogCkeditorMethods(ckeditorFieldKeys, formFields) : {}),

        openSections: {},

        modalOpen: false,
        deleteOpen: false,
        advancedOpen: false,
        importOpen: false,
        headerPopover: null,
        settingsOpen: false,
        rowMenuOpen: null,
        presetMenuOpen: false,
        presetNaming: false,
        presetName: '',
        presetRenaming: null,
        renamingPresetValue: '',
        filterPresets: loadJson('filter_presets', []),
        dialogMode: 'add',
        saving: false,
        importing: false,
        importLoading: false,
        toast: null,

        form: {},
        initialForm: {},
        selectedItem: null,
        importPreview: [],

        currentPage: normalizeCurrentPage(loadJson('page', 1), 1),
        rowsPerPage: normalizeRowsPerPage(loadJson('rows', 10), 10),
        columnVisibility: { ...defaultVisibility, ...loadJson('colvis', {}) },
        filters: loadJson('filters', {}),
        advancedFilters: normalizeAdvancedFilters(
            config.advancedFilterMode,
            loadJson('advanced', defaultAdvancedFilters(config.advancedFilterMode))
        ),
        sortConfig: normalizeSortConfig(loadJson('sort', [])),
        selectedIds: loadJson('selected', []),

        init() {
            this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 10);
            this.currentPage = normalizeCurrentPage(this.currentPage, this.totalPages);
            window.addEventListener('nttu-language-changed', (event) => {
                this.currentLang = event.detail?.language || getLanguage();
                const table = this.$root?.querySelector?.('table[data-col-resize]');
                if (table) {
                    table._nttuLabelMinWidths = {};
                }
                this.scheduleColumnResize();
                applyI18n(this.currentLang, this.$root);
            });
            this.$watch('visibleColumnKeys', () => this.scheduleColumnResize());
            this.scheduleColumnResize();
            if (this.serverPaginated && this.routes.list) {
                this.fetchList();
            }
            if (hasCkeditorField) {
                preloadCatalogCkeditorCompose();
            }
        },

        text(key) {
            void this.currentLang;
            if (!key) {
                return '';
            }
            return t(key, this.currentLang) || key;
        },

        pageTitle() {
            return this.text(this.titleKey);
        },

        editActionLabelText() {
            return this.text(this.editActionLabelKey);
        },

        filterPlaceholder(key) {
            return `${this.text('Lọc')} ${this.columnLabel(key)}...`;
        },

        paginationSummary() {
            void this.currentLang;
            let summary = `${this.text('Tổng cộng')} ${this.filteredItems.length} ${this.text('bản ghi')}.`;
            if (this.visibleSelectedCount > 0) {
                summary += ` ${this.text('Đã chọn')} ${this.visibleSelectedCount} ${this.text('dòng')}.`;
            }
            return summary;
        },

        tableEmptyMessage() {
            void this.currentLang;
            if (this.listLoading) {
                return this.text(TABLE_LOADING);
            }
            if (this.hasActiveFilters) {
                return this.text(TABLE_EMPTY_FILTERED);
            }

            return this.text(TABLE_EMPTY_NO_DATA);
        },

        tableEmptySubtitle() {
            void this.currentLang;
            if (this.listLoading) {
                return this.text(TABLE_SUBTITLE_LOADING);
            }
            if (this.hasActiveFilters) {
                return this.text(TABLE_SUBTITLE_FILTERED);
            }

            const entity = t(this.entityLabel, this.currentLang) || this.entityLabel;
            const template = this.text('Chưa có {entity} nào. Hãy thêm mới hoặc chọn điều kiện khác.')
                || 'Chưa có {entity} nào. Hãy thêm mới hoặc chọn điều kiện khác.';

            return template.replace('{entity}', entity);
        },

        booleanStatusText(active) {
            return active ? this.text('Ngưng hoạt động') : this.text('Đang hoạt động');
        },

        booleanYesNoText(value) {
            return value ? this.text('Có') : this.text('Không');
        },

        savingLabel() {
            if (this.saving) {
                return this.text('Đang lưu...');
            }
            if (Array.isArray(this.violationStudents) && this.violationStudentNotInSystem) {
                return this.text('Lưu & thêm sinh viên');
            }

            return this.text('Lưu lại');
        },

        importingLabel() {
            return this.importing ? this.text('Đang import...') : this.text('Xác nhận Import');
        },

        importProgressMessage() {
            const msg = this.importProgress?.message;
            if (!msg) {
                return this.text('Đang import...');
            }
            return translateMessage(msg, this.currentLang);
        },

        scheduleListFetch() {
            if (!this.serverPaginated || !this.routes.list) {
                return;
            }
            clearTimeout(this._listFetchTimer);
            this._listFetchTimer = setTimeout(() => this.fetchList(), 300);
        },

        async fetchList() {
            if (!this.serverPaginated || !this.routes.list) {
                return;
            }
            this.listLoading = true;
            try {
                const params = new URLSearchParams({
                    page: String(this.safePage),
                    per_page: String(this.normalizedRowsPerPage),
                });
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) {
                        params.set(`filters[${key}]`, value);
                    }
                });
                if (this.sortConfig[0]) {
                    params.set('sort_key', this.sortConfig[0].key);
                    params.set(
                        'sort_dir',
                        this.sortConfig[0].direction === 'descending' ? 'desc' : 'asc',
                    );
                }
                const response = await fetch(`${this.routes.list}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await readJsonResponse(response);
                if (!response.ok) {
                    throw new Error(data.message || 'Không tải được dữ liệu');
                }
                this.items = data.items || [];
                this.serverMeta = data.meta || this.serverMeta;
                this.currentPage = this.serverMeta.current_page || this.currentPage;
                saveJson('page', this.currentPage);
                this.scheduleColumnResize();
            } catch (error) {
                this.showToast(error.message || 'Lỗi tải dữ liệu', 'error');
            } finally {
                this.listLoading = false;
            }
        },

        scheduleColumnResize() {
            this.$nextTick(() => {
                const table = this.$root?.querySelector?.('table[data-col-resize]');
                if (table && typeof window.nttuScheduleColumnResize === 'function') {
                    window.nttuScheduleColumnResize(table, [0, 50, 150, 400, 800, 1200]);
                }
            });
        },

        get visibleColumnKeys() {
            return Object.keys(this.columns).filter((k) => this.columnVisibility[k]);
        },

        get filteredItems() {
            if (this.serverPaginated) {
                return this.items;
            }

            let result = this.items.filter((item) => {
                const matchesColumnFilters = Object.entries(this.filters).every(([key, value]) => {
                    return matchesFilter(item, key, value, this.columns);
                });
                if (!matchesColumnFilters) {
                    return false;
                }

                if (this.advancedFilterMode === 'violations') {
                    return matchesViolationsAdvanced(item, this.advancedFilters);
                }

                if (this.advancedFilterMode === 'assets') {
                    return matchesAssetsAdvanced(item, this.advancedFilters, this.advancedFilterOptions);
                }

                if (this.advancedFilterMode === 'documents') {
                    return matchesDocumentsAdvanced(item, this.advancedFilters);
                }

                if (this.advancedFilterMode === 'announcements') {
                    return matchesAnnouncementsAdvanced(item, this.advancedFilters);
                }

                if (this.advancedFilterMode === 'incident-records') {
                    return matchesIncidentRecordsAdvanced(item, this.advancedFilters);
                }

                return true;
            });

            if (this.sortConfig.length > 0) {
                const { key, direction } = this.sortConfig[0];
                result = [...result].sort((a, b) => {
                    const av = String(a[key] ?? '');
                    const bv = String(b[key] ?? '');
                    const cmp = av.localeCompare(bv, 'vi', { numeric: true });
                    return direction === 'ascending' ? cmp : -cmp;
                });
            }

            return result;
        },

        get normalizedRowsPerPage() {
            return normalizeRowsPerPage(this.rowsPerPage, 10);
        },

        get totalPages() {
            if (this.serverPaginated) {
                return Math.max(1, Number(this.serverMeta.last_page) || 1);
            }

            return Math.max(1, Math.ceil(this.filteredItems.length / this.normalizedRowsPerPage));
        },

        get safePage() {
            return normalizeCurrentPage(this.currentPage, this.totalPages);
        },

        get currentItems() {
            if (this.serverPaginated) {
                return this.items;
            }

            return paginateSlice(
                this.filteredItems,
                this.currentPage,
                this.normalizedRowsPerPage,
            ).items;
        },

        get visibleSelectedCount() {
            const ids = new Set(this.selectedIds);
            return this.filteredItems.filter((item) => ids.has(item.id)).length;
        },

        get isChanged() {
            return JSON.stringify(this.form) !== JSON.stringify(this.initialForm);
        },

        get isViewMode() {
            return this.dialogMode === 'view';
        },

        get hasActiveFilters() {
            if (Object.keys(this.filters).some((key) => this.filters[key])) {
                return true;
            }
            if (!this.advancedFilterMode) {
                return false;
            }

            return Object.values(this.advancedFilters).some((value) => {
                if (Array.isArray(value)) {
                    return value.length > 0;
                }
                if (typeof value === 'string') {
                    return value.trim() !== '';
                }

                return value !== null && value !== undefined && value !== false;
            });
        },

        fieldVisible(key) {
            const field = this.formFieldMap[key];
            if (!field) return true;
            if (Array.isArray(field.visibleModes) && field.visibleModes.length) {
                return field.visibleModes.includes(this.dialogMode);
            }
            if (Array.isArray(field.hideOnModes) && field.hideOnModes.length) {
                return !field.hideOnModes.includes(this.dialogMode);
            }
            return true;
        },

        fieldReadOnly(key) {
            const field = this.formFieldMap[key];
            if (this.dialogMode === 'view') return true;
            if (field?.readonlyOnEdit && this.dialogMode === 'edit') return true;
            return Array.isArray(field?.readonlyOnModes) && field.readonlyOnModes.includes(this.dialogMode);
        },

        isSelected(id) {
            return this.selectedIds.includes(id);
        },

        showToast(message, type = 'success') {
            const translated = translateMessage(message, this.currentLang);
            this.toast = { message: translated, type };
            setTimeout(() => {
                if (this.toast?.message === translated) {
                    this.toast = null;
                }
            }, 2500);
        },

        ensureDepartmentField(fieldKey) {
            const value = this.form[fieldKey];
            this.departmentOptions = ensureDepartmentInOptions(this.departmentOptions, value);
        },

        validateRequiredFormFields() {
            for (const field of this.formFields) {
                if (!field.required) {
                    continue;
                }
                if (!this.fieldVisible(field.key)) {
                    continue;
                }
                if (this.fieldReadOnly(field.key)) {
                    continue;
                }
                const value = this.form[field.key];
                if (field.type === 'ckeditor') {
                    if (isCkeditorContentEmpty(value)) {
                        const label = this.text(field.label) || field.label || field.key;
                        this.showToast(`Vui lòng nhập ${label}`, 'error');
                        return false;
                    }
                    continue;
                }
                if (value === null || value === undefined || String(value).trim() === '') {
                    const label = this.text(field.label) || field.label || field.key;
                    this.showToast(`Vui lòng nhập ${label}`, 'error');
                    return false;
                }
            }

            return true;
        },

        toggleRow(id) {
            const set = new Set(this.selectedIds);
            if (set.has(id)) set.delete(id);
            else set.add(id);
            this.selectedIds = [...set];
            saveJson('selected', this.selectedIds);
        },

        setFilter(key, value) {
            this.filters = { ...this.filters, [key]: value };
            this.currentPage = 1;
            saveJson('filters', this.filters);
            saveJson('page', this.currentPage);
            this.scheduleListFetch();
        },

        clearFilters() {
            this.filters = {};
            if (['violations', 'assets', 'documents', 'announcements'].includes(this.advancedFilterMode)) {
                this.advancedFilters = defaultAdvancedFilters(this.advancedFilterMode);
                saveJson('advanced', this.advancedFilters);
            }
            this.currentPage = 1;
            saveJson('filters', this.filters);
            saveJson('page', this.currentPage);
            this.scheduleListFetch();
        },

        resetAdvancedFilters() {
            this.filters = {};
            this.advancedFilters = defaultAdvancedFilters(this.advancedFilterMode);
            this.currentPage = 1;
            saveJson('filters', this.filters);
            saveJson('advanced', this.advancedFilters);
            saveJson('page', this.currentPage);
        },

        applyAdvancedFilters() {
            this.advancedOpen = false;
            this.currentPage = 1;
            saveJson('advanced', this.advancedFilters);
            saveJson('page', this.currentPage);
            this.scheduleListFetch();
        },

        saveFilterPreset(name) {
            const trimmed = String(name || '').trim();
            if (!trimmed) {
                return;
            }

            const preset = {
                name: trimmed,
                filters: normalizeAdvancedFilters(this.advancedFilterMode, this.advancedFilters),
                createdAt: new Date().toISOString(),
            };
            this.filterPresets = [...this.filterPresets.filter((p) => p.name !== trimmed), preset];
            saveJson('filter_presets', this.filterPresets);
            this.presetNaming = false;
            this.presetName = '';
            this.presetMenuOpen = false;
        },

        applyFilterPreset(preset) {
            this.advancedFilters = normalizeAdvancedFilters(
                this.advancedFilterMode,
                preset?.filters || defaultAdvancedFilters(this.advancedFilterMode)
            );
            this.presetMenuOpen = false;
            this.currentPage = 1;
            saveJson('advanced', this.advancedFilters);
            saveJson('page', this.currentPage);
        },

        deleteFilterPreset(name) {
            this.filterPresets = this.filterPresets.filter((p) => p.name !== name);
            saveJson('filter_presets', this.filterPresets);
        },

        startRenameFilterPreset(name) {
            this.presetRenaming = name;
            this.renamingPresetValue = name;
        },

        cancelRenameFilterPreset() {
            this.presetRenaming = null;
            this.renamingPresetValue = '';
        },

        renameFilterPreset() {
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
            saveJson('filter_presets', this.filterPresets);
            this.cancelRenameFilterPreset();
            this.showToast(`Đã đổi tên thành "${trimmed}"`);
        },

        clearColumnFilter(key) {
            const next = { ...this.filters };
            delete next[key];
            this.filters = next;
            this.currentPage = 1;
            saveJson('filters', this.filters);
            saveJson('page', this.currentPage);
        },

        isFiltered(key) {
            return !!this.filters[key];
        },

        sortStateFor(key) {
            return this.sortConfig.find((entry) => entry.key === key) || null;
        },

        sortIconClass(key) {
            return this.isFiltered(key) ? 'nttu-sort-filtered' : '';
        },

        columnHeaderIconPath(key) {
            return headerIconPathForColumn(key, this.columns[key]);
        },

        columnHeaderIconClass(key) {
            return headerIconClassForKey(key, this.columns[key]);
        },

        columnLabel(key) {
            void this.currentLang;
            const label = this.columns[key]?.label;
            return label ? (t(label, this.currentLang) || label) : key;
        },

        cellValue(item, key) {
            if (!item || !key) {
                return '---';
            }

            const col = this.columns[key] || {};
            let raw = Object.prototype.hasOwnProperty.call(item, key) ? item[key] : item?.[key];

            if (key === 'doc_number' && this.formMode === 'document-record') {
                raw = item?.doc_number || item?.doc_code || raw;
            }

            if (col.type === 'boolean_status') {
                return this.booleanStatusText(raw);
            }
            if (col.type === 'boolean_yesno') {
                return this.booleanYesNoText(raw);
            }
            if (col.type === 'service_resolution') {
                return serviceRequestResolutionLabel(item);
            }
            if (col.type === 'date') {
                return this.formatDate(raw);
            }
            if (raw === 0) {
                return '0';
            }
            if (raw === null || raw === undefined || raw === '') {
                return '---';
            }

            return String(raw);
        },

        isPrimaryTextColumn(key) {
            const col = this.columns[key];
            return !!col?.primary && !['boolean_status', 'boolean_yesno', 'avatar', 'date', 'badge', 'image'].includes(col.type);
        },

        isBadgeColumn(key) {
            return this.columns[key]?.type === 'badge';
        },

        isStandardTextColumn(key) {
            const col = this.columns[key];
            return !!col && !col.primary && !['boolean_status', 'boolean_yesno', 'avatar', 'date', 'badge', 'image'].includes(col.type);
        },

        requestSort(key, direction) {
            this.sortConfig = [{ key, direction }];
            saveJson('sort', this.sortConfig);
            this.headerPopover = null;
            this.scheduleListFetch();
        },

        clearSort() {
            this.sortConfig = [];
            saveJson('sort', []);
            this.headerPopover = null;
            this.scheduleListFetch();
        },

        toggleColumn(key) {
            this.columnVisibility[key] = !this.columnVisibility[key];
            saveJson('colvis', this.columnVisibility);
            this.scheduleColumnResize();
        },

        selectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), true);
            saveJson('colvis', this.columnVisibility);
            this.scheduleColumnResize();
        },

        deselectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), false);
            saveJson('colvis', this.columnVisibility);
            this.scheduleColumnResize();
        },

        setRowsPerPage(v) {
            this.rowsPerPage = normalizeRowsPerPage(v, 10);
            this.currentPage = 1;
            saveJson('rows', this.rowsPerPage);
            saveJson('page', this.currentPage);
            this.scheduleListFetch();
        },

        goPage(p) {
            this.currentPage = normalizeCurrentPage(p, this.totalPages);
            saveJson('page', this.currentPage);
            this.scheduleListFetch();
        },

        formatDate(value) {
            if (!value) {
                return '---';
            }
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }
            const d = String(date.getDate()).padStart(2, '0');
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const y = date.getFullYear();
            return `${d}/${m}/${y}`;
        },

        initOpenSections() {
            const sections = {};
            this.formSections.forEach((section, index) => {
                sections[section.id] = index === 0;
            });
            this.openSections = sections;
        },

        toggleSection(id) {
            const wasOpen = this.openSections[id];
            const next = {};
            this.formSections.forEach((section) => {
                next[section.id] = false;
            });
            next[id] = !wasOpen;
            this.openSections = next;
        },

        fieldDisplayValue(key) {
            const field = this.formFieldMap[key];
            const value = this.form[key];

            if (field?.type === 'checkbox') {
                if (key === 'is_inactive') {
                    return value ? this.text('Ngưng sử dụng') : this.text('Đang sử dụng');
                }
                return this.booleanYesNoText(value);
            }

            if (value === null || value === undefined || value === '') {
                return '---';
            }

            if (field?.type === 'select') {
                const option = (field.options || []).find((entry) => String(entry.value) === String(value));
                if (option?.label) {
                    return this.text(option.label);
                }
                const nameKey = `${key.replace(/_id$/, '')}_name`;
                if (this.selectedItem?.[nameKey]) {
                    return this.selectedItem[nameKey];
                }
                if (key === 'department' && this.selectedItem?.department_name) {
                    return this.selectedItem.department_name;
                }
                if (key === 'position' && this.selectedItem?.position_name) {
                    return this.selectedItem.position_name;
                }
                if (key === 'role_id' && this.selectedItem?.role_name) {
                    return this.selectedItem.role_name;
                }
                if (key === 'building_block_id' && this.selectedItem?.building_block_name) {
                    return this.selectedItem.building_block_name;
                }
                if (key === 'recognition_id' && this.selectedItem?.recognition_name) {
                    return this.selectedItem.recognition_name;
                }
                return String(value);
            }

            if (field?.type === 'date') {
                return this.formatDate(value);
            }

            if (field?.type === 'number') {
                return String(value);
            }

            return String(value);
        },

        dialogModeAllowed(mode) {
            return this.allowedDialogModes.includes(mode);
        },

        modalHeading() {
            void this.currentLang;
            if (this.modalTitle) {
                return this.modalTitle;
            }

            const lang = this.currentLang;
            const prefix = this.dialogMode === 'view'
                ? t('Chi tiết', lang)
                : this.dialogMode === 'add'
                    ? t('Thêm mới', lang)
                    : this.dialogMode === 'copy'
                        ? t('Nhân bản', lang)
                        : t('Chỉnh sửa', lang);

            return `${prefix} ${t(this.entityLabel, lang) || this.entityLabel}`;
        },

        openDialog(mode, item = null) {
            void this.openDialogWithDetail(mode, item);
        },

        async openDialogWithDetail(mode, item = null) {
            if (!this.dialogModeAllowed(mode)) {
                return;
            }

            this.rowMenuOpen = null;
            this.dialogMode = mode;
            this.selectedItem = item;
            let source = item;
            if (hasViolationForm && item?.id && (mode === 'edit' || mode === 'view') && this.routes.show) {
                try {
                    const url = this.routes.show.replace('__ID__', encodeURIComponent(item.id));
                    const res = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (res.ok) {
                        const payload = await res.json();
                        source = payload.item || item;
                    }
                } catch (_) {
                    // Use list row if detail fetch fails.
                }
            }
            let data;
            if (source) {
                data = itemToForm(source);
                if (mode === 'copy') {
                    if ('employee_id' in data && data.employee_id) {
                        data.employee_id = `${data.employee_id}_copy`;
                    } else if ('department_id' in data && data.department_id) {
                        data.department_id = `${data.department_id}_copy`;
                    } else if ('code' in data && data.code) {
                        data.code = `${data.code}_copy`;
                    } else if ('id' in data && data.id && formFields.some((f) => f.key === 'id')) {
                        data.id = `${data.id}_copy`;
                    } else if ('name' in data && data.name) {
                        data.name = `${data.name} (Copy)`;
                    }
                }
            } else {
                data = emptyForm();
            }

            if (hasAssetForm) {
                data = applyAssetFormDefaults(mode, data, config);
            }

            if (hasServiceRequestForm) {
                data = applyServiceRequestFormDefaults(mode, data, config);
            }

            if (hasPetitionForm) {
                data = applyPetitionFormDefaults(mode, data, config);
            }

            if (hasDocumentRecordForm) {
                data = applyDocumentRecordFormDefaults(mode, data, config);
            }

            this.form = { ...data };
            this.initialForm = { ...data };
            this.initOpenSections();
            this.modalOpen = true;
            this.toast = null;
            if (hasServiceRequestForm) {
                this.$nextTick(() => {
                    this.srOnModalOpen?.();
                    this.initialForm = { ...this.form };
                });
            } else if (hasAssetReceptionForm) {
                this.$nextTick(() => {
                    this.arOnModalOpen?.();
                    this.initialForm = { ...this.form };
                });
            } else if (hasAssetReturnForm) {
                this.$nextTick(() => {
                    if (this.form.receiver_unit) {
                        this.ensureDepartmentField('receiver_unit');
                    }
                    this.initialForm = { ...this.form };
                });
            } else if (hasPetitionForm) {
                this.$nextTick(() => {
                    this.ptOnModalOpen?.();
                    this.initialForm = { ...this.form };
                });
            } else if (hasDocumentRecordForm) {
                this.$nextTick(() => {
                    this.drOnModalOpen?.();
                    this.initialForm = { ...this.form };
                });
            } else if (hasEvidenceField) {
                this.$nextTick(() => this.evidenceOnModalOpen?.());
            } else if (hasCkeditorField) {
                this.scheduleCatalogCkeditorMount();
            }
            if (hasViolationForm) {
                this.$nextTick(() => this.violationOnModalOpen?.());
            }
        },

        undoForm() {
            this.form = { ...this.initialForm };
            if (hasServiceRequestForm) {
                this.$nextTick(() => this.srOnModalOpen?.());
            } else if (hasAssetReceptionForm) {
                this.$nextTick(() => this.arOnModalOpen?.());
            } else if (hasAssetReturnForm && this.form.receiver_unit) {
                this.$nextTick(() => this.ensureDepartmentField('receiver_unit'));
            } else if (hasPetitionForm) {
                this.$nextTick(() => this.ptOnModalOpen?.());
            } else if (hasDocumentRecordForm) {
                this.$nextTick(() => this.drOnModalOpen?.());
            } else if (hasCkeditorField) {
                this.scheduleCatalogCkeditorMount();
            }
        },

        closeModal() {
            if (hasViolationForm) {
                if (this.violationScannerOpen) {
                    this.violationCloseScanner();
                    return;
                }
                if (this.violationPhotoCameraOpen) {
                    this.violationClosePhotoCamera();
                    return;
                }
                if (this.violationSignatureOpen) {
                    this.violationSignatureOpen = false;
                    return;
                }
                this.violationOnModalClose?.();
            }
            if (hasServiceRequestForm) {
                this.srOnModalClose?.();
            } else if (hasAssetReceptionForm) {
                this.arOnModalClose?.();
            } else if (hasDocumentRecordForm) {
                this.drOnModalClose?.();
            } else if (hasEvidenceField) {
                this.evidenceCleanupPanel?.();
            } else if (hasCkeditorField) {
                this.ckeditorMounting = false;
                resetCatalogCkeditorState(this);
                void this.destroyCatalogCkeditors();
            }
            window.dispatchEvent(new CustomEvent('modal-closed'));
            this.modalOpen = false;
        },

        async saveItem() {
            if (this.saving) {
                return;
            }

            this.saving = true;
            this.toast = null;

            try {
                const isEdit = this.dialogMode === 'edit' && this.selectedItem;
                const url = isEdit ? this.routes.update.replace('__ID__', this.selectedItem.id) : this.routes.store;
                const method = isEdit ? 'PUT' : 'POST';

                if (hasServiceRequestForm) {
                    this.srPrepareSave?.();
                }
                if (hasAssetReceptionForm) {
                    this.arPrepareSave?.();
                }
                if (config.formMode === 'asset-return') {
                    const returnDefaults = applyAssetFormDefaults(this.dialogMode, { ...this.form }, config);
                    Object.assign(this.form, returnDefaults);
                    if (this.form.receiver_unit) {
                        this.form.receiver_unit = String(this.form.receiver_unit).trim();
                        this.ensureDepartmentField('receiver_unit');
                    }
                }
                if (config.formMode === 'asset-gratitude') {
                    const gratitudeDefaults = applyAssetFormDefaults(this.dialogMode, { ...this.form }, config);
                    Object.assign(this.form, gratitudeDefaults);
                }
                if (hasDocumentRecordForm) {
                    this.drPrepareSave?.();
                }
                if (hasViolationForm && this.violationPrepareSave?.() === false) {
                    return;
                }

                if (hasCkeditorField) {
                    this.syncCatalogCkeditorsToForm();
                }

                if (!this.validateRequiredFormFields()) {
                    return;
                }

                const payload = { ...this.form };
                if (hasEvidenceField) {
                    this.evidenceEmitChange?.();
                }
                if (hasServiceRequestForm) {
                    this.srStripVirtualFields?.(payload);
                }
                if (hasAssetReceptionForm) {
                    this.arStripVirtualFields?.(payload);
                }
                if (hasViolationForm) {
                    payload.sync_student = this.violationShouldSyncStudent?.() === true;
                }
                this.formFields.forEach((field) => {
                    if (field.type === 'violation_verification') {
                        delete payload[field.key];
                    }
                    if (field.type === 'number' && payload[field.key] === '') {
                        payload[field.key] = null;
                    }
                    if (field.type === 'date' && (payload[field.key] === '' || payload[field.key] === undefined)) {
                        payload[field.key] = null;
                    }
                });
                if (config.formMode === 'asset-return') {
                    payload.asset_action = 'return';
                }

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 60000);

                let res;
                try {
                    res = await fetch(url, {
                        method,
                        signal: controller.signal,
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(payload),
                    });
                } finally {
                    clearTimeout(timeoutId);
                }

                const data = await readJsonResponse(res);
                if (!res.ok) {
                    const msg = data.message
                        || Object.values(data.errors || {}).flat().join(', ')
                        || 'Lưu thất bại';
                    throw new Error(msg);
                }

                if (this.serverPaginated) {
                    await this.fetchList();
                } else if (isEdit) {
                    const oldId = this.selectedItem.id;
                    if (config.formMode === 'asset-return' && data.item?.return_status === 'Đã trả') {
                        this.items = this.items.filter((i) => i.id !== oldId);
                        this.selectedIds = this.selectedIds.filter((id) => id !== oldId);
                        saveJson('selected', this.selectedIds);
                    } else {
                        const idx = this.items.findIndex((i) => i.id === oldId);
                        if (idx >= 0) {
                            this.items[idx] = data.item;
                        }
                        if (oldId !== data.item.id) {
                            this.selectedIds = this.selectedIds.map((id) => (id === oldId ? data.item.id : id));
                            saveJson('selected', this.selectedIds);
                        }
                    }
                } else {
                    this.items.unshift(data.item);
                }

                if (hasViolationForm && data.synced_student) {
                    this.violationRegisterSyncedStudent?.(data.synced_student);
                }

                this.showToast(data.message);
                setTimeout(() => this.closeModal(), 400);
            } catch (e) {
                const message = e?.name === 'AbortError'
                    ? 'Lưu thất bại: máy chủ không phản hồi kịp thời.'
                    : (e?.message || 'Lưu thất bại');
                this.showToast(message, 'error');
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(item) {
            this.rowMenuOpen = null;
            this.selectedItem = item;
            this.deleteOpen = true;
        },

        async deleteItem() {
            if (!this.selectedItem) return;
            const url = this.routes.destroy.replace('__ID__', this.selectedItem.id);
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await readJsonResponse(res);
                if (!res.ok) throw new Error(data.message || 'Xóa thất bại');
                if (this.serverPaginated) {
                    await this.fetchList();
                } else {
                    this.items = this.items.filter((i) => i.id !== this.selectedItem.id);
                }
                this.selectedIds = this.selectedIds.filter((id) => id !== this.selectedItem.id);
                saveJson('selected', this.selectedIds);
                this.deleteOpen = false;
                this.selectedItem = null;
                this.showToast(data.message || 'Đã xóa.');
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        exportExcel() {
            if (!this.routes.export) {
                return;
            }

            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) {
                    params.set(`filters[${key}]`, value);
                }
            });
            if (this.sortConfig[0]) {
                params.set('sort_key', this.sortConfig[0].key);
                params.set(
                    'sort_dir',
                    this.sortConfig[0].direction === 'descending' ? 'desc' : 'asc',
                );
            }
            const qs = params.toString();
            window.location.href = qs ? `${this.routes.export}?${qs}` : this.routes.export;
        },

        downloadImportTemplateFile() {
            if (!this.importColumns.length) {
                this.showToast('Trang này chưa cấu hình cột import.', 'error');
                return;
            }

            downloadImportTemplate(
                this.importColumns,
                this.importTemplateFilename,
                this.importTemplateOptions,
            );
        },

        async onImportFile(event) {
            if (!this.routes.importPreview) return;
            const file = event.target.files?.[0];
            if (!file) return;
            this.importLoading = true;
            const formData = new FormData();
            formData.append('file', file);
            try {
                const res = await fetch(this.routes.importPreview, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: formData,
                });
                const data = await readJsonResponse(res);
                if (!res.ok) throw new Error(data.message || 'Không đọc được file');
                this.importPreview = data.rows || [];
                this.importOpen = true;
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.importLoading = false;
            }
            event.target.value = '';
        },

        async pollImportProgress(progressUrl) {
            const started = Date.now();
            while (Date.now() - started < 30 * 60 * 1000) {
                const response = await fetch(progressUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await readJsonResponse(response);
                if (!response.ok) {
                    throw new Error(data.message || 'Không theo dõi được tiến trình import');
                }
                this.importProgress = data;
                if (data.status === 'completed') {
                    return data;
                }
                if (data.status === 'failed') {
                    throw new Error(data.message || 'Import thất bại');
                }
                await new Promise((resolve) => setTimeout(resolve, 800));
            }
            throw new Error('Import quá thời gian chờ');
        },

        async processImport() {
            this.importing = true;
            this.importProgress = null;
            try {
                const res = await fetch(this.routes.import, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ rows: this.importPreview, async: true }),
                });
                const data = await readJsonResponse(res);
                if (!res.ok) throw new Error(data.message || 'Import thất bại');

                if (data.async && data.progress_url) {
                    const result = await this.pollImportProgress(data.progress_url);
                    if (this.serverPaginated) {
                        await this.fetchList();
                    } else if (data.items) {
                        this.items = data.items;
                    }
                    this.importOpen = false;
                    this.importPreview = [];
                    this.showToast(result.message || 'Import thành công.');
                    return;
                }

                if (this.serverPaginated) {
                    await this.fetchList();
                } else if (data.items) {
                    this.items = data.items;
                }
                this.importOpen = false;
                this.importPreview = [];
                this.showToast(data.message || 'Import thành công.');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.importing = false;
                this.importProgress = null;
            }
        },

        ...(hasEvidenceField ? evidencePanelMethods() : {}),
        ...(hasViolationForm ? violationFormMethods() : {}),
        ...(hasServiceRequestForm ? serviceRequestFormMethods() : {}),
        ...(hasAssetReceptionForm ? assetReceptionFormMethods() : {}),
        ...(hasPetitionForm ? petitionFormMethods() : {}),
        ...(hasDocumentRecordForm ? documentRecordFormMethods() : {}),
    };
}

window.catalogTablePage = catalogTablePage;
