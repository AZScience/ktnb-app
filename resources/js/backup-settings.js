import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice, ROWS_PER_PAGE_OPTIONS } from './nttu-pagination.js';

const ROWS_PER_PAGE_OPTIONS_EXTENDED = [...ROWS_PER_PAGE_OPTIONS, 50, 100];
const COLUMN_STORAGE_KEY = 'nttu_backup_colvis_v1';
const TABLE_STORAGE_PREFIX = 'nttu_backup_table_v1';

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

export function registerBackupSettings(Alpine) {
    Alpine.data('backupSettingsPage', (config) => ({
        scope: config.scope || 'database',
        files: config.files || [],
        routes: config.routes || {},
        canExport: config.canExport !== false,
        canImport: config.canImport !== false,
        canEdit: config.canEdit !== false,
        canDelete: config.canDelete !== false,
        rowsPerPage: normalizeRowsPerPage(loadJson(`${TABLE_STORAGE_PREFIX}_rows`, 20), 20),
        currentPage: normalizeCurrentPage(loadJson(`${TABLE_STORAGE_PREFIX}_page`, 1), 1),
        sortKey: loadJson(`${TABLE_STORAGE_PREFIX}_sort_key`, 'timestamp'),
        sortDir: loadJson(`${TABLE_STORAGE_PREFIX}_sort_dir`, 'desc'),
        rowsPerPageOptions: ROWS_PER_PAGE_OPTIONS_EXTENDED,
        filters: loadJson(`${TABLE_STORAGE_PREFIX}_filters`, {}),
        headerPopover: null,
        rowMenuOpen: null,
        settingsOpen: false,
        columns: {
            name: { label: 'Tên file' },
            size: { label: 'Dung lượng' },
            date: { label: 'Ngày tạo' },
        },
        columnVisibility: loadJson(COLUMN_STORAGE_KEY, {
            name: true,
            size: true,
            date: true,
        }),
        renameOpen: false,
        deleteOpen: false,
        restoreOpen: false,
        purgeOpen: false,
        selected: null,
        renameValue: '',
        purgeFromDate: '',
        purgeToDate: '',
        purgeDataTypes: config.purgeDataTypes || [],
        purgeSelectedTypes: [],
        purging: false,
        actionBusy: false,
        toast: null,

        init() {
            this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 20);
            this.currentPage = normalizeCurrentPage(this.currentPage, this.totalPages);
            const flashRaw = sessionStorage.getItem('backup_flash');
            if (flashRaw) {
                try {
                    const flash = JSON.parse(flashRaw);
                    if (flash?.message) {
                        this.showToast(flash.message, flash.type || 'success');
                    }
                } catch {
                    // ignore invalid flash payload
                }
                sessionStorage.removeItem('backup_flash');
            }
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = null; }, 30000);
        },

        persistFlash(message, type = 'success') {
            sessionStorage.setItem('backup_flash', JSON.stringify({ message, type }));
        },

        finishAction(message, type = 'success') {
            this.persistFlash(message, type);
            window.location.reload();
        },

        get filteredFiles() {
            return this.files.filter((file) => Object.entries(this.filters).every(([key, value]) => {
                if (!value) return true;
                const haystack = String(file[key] ?? '').toLowerCase();
                return haystack.includes(String(value).toLowerCase());
            }));
        },

        get sortedFiles() {
            const items = [...this.filteredFiles];
            if (!this.sortKey) {
                return items;
            }
            const key = this.sortKey;
            const dir = this.sortDir === 'asc' ? 1 : -1;

            items.sort((a, b) => {
                const aVal = a[key];
                const bVal = b[key];
                if (aVal === bVal) return 0;
                if (aVal === null || aVal === undefined) return 1;
                if (bVal === null || bVal === undefined) return -1;
                if (aVal < bVal) return -1 * dir;
                if (aVal > bVal) return 1 * dir;
                return 0;
            });

            return items;
        },

        get normalizedRowsPerPage() {
            return normalizeRowsPerPage(this.rowsPerPage, 20);
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.sortedFiles.length / this.normalizedRowsPerPage));
        },

        get safePage() {
            return normalizeCurrentPage(this.currentPage, this.totalPages);
        },

        get startIndex() {
            return paginateSlice(this.sortedFiles, this.currentPage, this.normalizedRowsPerPage).startIndex;
        },

        get currentItems() {
            return paginateSlice(this.sortedFiles, this.currentPage, this.normalizedRowsPerPage).items;
        },

        get visibleColumnKeys() {
            return Object.keys(this.columns).filter((key) => this.columnVisibility[key]);
        },

        toggleColumn(key) {
            this.columnVisibility = { ...this.columnVisibility, [key]: !this.columnVisibility[key] };
            saveJson(COLUMN_STORAGE_KEY, this.columnVisibility);
        },

        selectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), true);
            saveJson(COLUMN_STORAGE_KEY, this.columnVisibility);
        },

        deselectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), false);
            saveJson(COLUMN_STORAGE_KEY, this.columnVisibility);
        },

        columnSortKey(key) {
            return key === 'date' ? 'timestamp' : key;
        },

        fileUrl(template, file) {
            if (!file || !template) return '';
            return template.replace('__FILE__', encodeURIComponent(file.name));
        },

        scopedActionUrl(template, file) {
            const url = this.fileUrl(template, file);
            if (!url) return '';
            const sep = url.includes('?') ? '&' : '?';
            return `${url}${sep}scope=${encodeURIComponent(this.scope)}`;
        },

        async readActionResponse(res) {
            const body = await res.json().catch(() => ({}));
            if (!res.ok) {
                const msg = body.message
                    || Object.values(body.errors || {}).flat().join(', ')
                    || 'Thao tác thất bại.';
                throw new Error(msg);
            }
            return body;
        },

        async submitRename() {
            if (!this.selected || !this.renameValue.trim()) return;
            this.actionBusy = true;
            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('new_name', this.renameValue.trim());
                const res = await fetch(this.scopedActionUrl(this.routes.rename, this.selected), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                const body = await this.readActionResponse(res);
                this.renameOpen = false;
                this.finishAction(body.message || 'Đã đổi tên file backup.');
            } catch (err) {
                this.showToast(err.message || 'Không thể đổi tên file.', 'error');
            } finally {
                this.actionBusy = false;
            }
        },

        async submitDelete() {
            const deleteUrl = this.scopedActionUrl(this.routes.destroy, this.selected);
            if (!deleteUrl) return;
            this.actionBusy = true;
            try {
                const formData = new FormData();
                formData.append('_method', 'DELETE');
                formData.append('_token', csrfToken());
                const res = await fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                const body = await this.readActionResponse(res);
                this.files = this.files.filter((file) => file.name !== this.selected?.name);
                this.deleteOpen = false;
                this.selected = null;
                this.finishAction(body.message || 'Đã xóa file backup.');
            } catch (err) {
                this.showToast(err.message || 'Không thể xóa file.', 'error');
            } finally {
                this.actionBusy = false;
            }
        },

        async submitRestore() {
            const restoreUrl = this.scopedActionUrl(this.routes.restore, this.selected);
            if (!restoreUrl) return;
            this.actionBusy = true;
            try {
                const res = await fetch(restoreUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const body = await this.readActionResponse(res);
                this.restoreOpen = false;
                this.finishAction(body.message || 'Đã phục hồi dữ liệu thành công.');
            } catch (err) {
                this.showToast(err.message || 'Không thể phục hồi dữ liệu.', 'error');
            } finally {
                this.actionBusy = false;
            }
        },

        downloadUrl(file) {
            return this.scopedActionUrl(this.routes.download, file);
        },

        setFilter(key, value) {
            this.filters = { ...this.filters, [key]: value };
            this.currentPage = 1;
            saveJson(`${TABLE_STORAGE_PREFIX}_filters`, this.filters);
            saveJson(`${TABLE_STORAGE_PREFIX}_page`, this.currentPage);
        },

        clearColumnFilter(key) {
            this.setFilter(key, '');
            this.headerPopover = null;
        },

        isFiltered(key) {
            return !!this.filters[key];
        },

        sortIconClass(key) {
            return this.isFiltered(key) ? 'nttu-sort-filtered' : '';
        },

        requestSort(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = key === 'name' ? 'asc' : 'desc';
            }
            this.currentPage = 1;
            saveJson(`${TABLE_STORAGE_PREFIX}_sort_key`, this.sortKey);
            saveJson(`${TABLE_STORAGE_PREFIX}_sort_dir`, this.sortDir);
            saveJson(`${TABLE_STORAGE_PREFIX}_page`, this.currentPage);
            this.headerPopover = null;
        },

        sortDirection(key) {
            return this.sortKey === key ? this.sortDir : null;
        },

        clearSort() {
            this.sortKey = null;
            this.sortDir = 'desc';
            this.currentPage = 1;
            saveJson(`${TABLE_STORAGE_PREFIX}_sort_key`, null);
            saveJson(`${TABLE_STORAGE_PREFIX}_sort_dir`, this.sortDir);
            saveJson(`${TABLE_STORAGE_PREFIX}_page`, this.currentPage);
            this.headerPopover = null;
        },

        goPage(page) {
            this.currentPage = normalizeCurrentPage(page, this.totalPages);
            saveJson(`${TABLE_STORAGE_PREFIX}_page`, this.currentPage);
        },

        setRowsPerPage(value) {
            this.rowsPerPage = normalizeRowsPerPage(value, 20);
            this.currentPage = 1;
            saveJson(`${TABLE_STORAGE_PREFIX}_rows`, this.rowsPerPage);
            saveJson(`${TABLE_STORAGE_PREFIX}_page`, this.currentPage);
        },

        formatSize(bytes) {
            return `${(Number(bytes || 0) / 1024).toFixed(1)} KB`;
        },

        closeRowMenu() {
            this.rowMenuOpen = null;
        },

        openRename(file) {
            this.selected = file;
            this.renameValue = file.name.replace(/\.zip$/i, '');
            this.closeRowMenu();
            this.renameOpen = true;
        },

        openDelete(file) {
            this.selected = file;
            this.closeRowMenu();
            this.deleteOpen = true;
        },

        openRestore(file) {
            this.selected = file;
            this.closeRowMenu();
            this.restoreOpen = true;
        },

        openPurgeDialog() {
            this.purgeFromDate = '';
            this.purgeToDate = '';
            this.purgeSelectedTypes = [];
            this.purgeOpen = true;
        },

        get canPurge() {
            return !this.purging
                && Boolean(this.purgeFromDate)
                && Boolean(this.purgeToDate)
                && this.purgeSelectedTypes.length > 0;
        },

        isPurgeTypeSelected(key) {
            return this.purgeSelectedTypes.includes(key);
        },

        togglePurgeType(key) {
            if (this.purgeSelectedTypes.includes(key)) {
                this.purgeSelectedTypes = this.purgeSelectedTypes.filter((item) => item !== key);
                return;
            }
            this.purgeSelectedTypes = [...this.purgeSelectedTypes, key];
        },

        selectAllPurgeTypes() {
            this.purgeSelectedTypes = this.purgeDataTypes.map((item) => item.key);
        },

        clearPurgeTypes() {
            this.purgeSelectedTypes = [];
        },

        async purgeData() {
            if (!this.routes.purgeData || !this.canPurge) {
                return;
            }
            this.purging = true;
            try {
                const res = await fetch(this.routes.purgeData, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        from_date: this.purgeFromDate,
                        to_date: this.purgeToDate,
                        types: this.purgeSelectedTypes,
                    }),
                });
                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(body.message || 'Không thể xóa dữ liệu.');

                this.purgeOpen = false;
                this.finishAction(body.message || 'Đã xóa dữ liệu theo khoảng ngày.');
            } catch (err) {
                this.showToast(err.message || 'Không thể xóa dữ liệu.', 'error');
            } finally {
                this.purging = false;
            }
        },
    }));
}
