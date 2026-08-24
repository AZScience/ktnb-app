import { columnKeyIconPaths, iconNamePaths } from './nttu-icons.js';
import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice, ROWS_PER_PAGE_OPTIONS } from './nttu-pagination.js';

const PERMISSION_KEYS = ['access', 'view', 'add', 'edit', 'delete', 'import', 'export'];

const PERMISSION_LABELS = {
    access: { label: 'Truy cập', tone: 'blue' },
    view: { label: 'Xem', tone: 'slate' },
    add: { label: 'Thêm', tone: 'green' },
    edit: { label: 'Sửa', tone: 'green' },
    delete: { label: 'Xóa', tone: 'red' },
    import: { label: 'Import', tone: 'green' },
    export: { label: 'Export', tone: 'green' },
};

const PERMISSION_ICON_PATHS = {
    access: iconNamePaths.shield,
    view: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
    add: 'M12 4v16m8-8H4',
    edit: iconNamePaths.note,
    delete: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
    import: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
    export: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l4 4m0 0l-4-4m-4 4V4',
    'check-square': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    package: iconNamePaths.package,
};

const COLUMN_HEADER_ICONS = {
    name: columnKeyIconPaths.code,
    note: columnKeyIconPaths.content,
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

function allModules(categories) {
    return (categories || []).flatMap((cat) => cat.modules || []);
}

function emptyPermissions(categories) {
    const perms = {};
    allModules(categories).forEach((module) => {
        perms[module.id] = Object.fromEntries(PERMISSION_KEYS.map((key) => [key, false]));
    });
    return perms;
}

function canonicalModuleId(moduleId, aliases) {
    return aliases?.[moduleId] || moduleId;
}

function normalizeRolePermissions(categories, rolePerms, aliases) {
    const merged = emptyPermissions(categories);

    Object.entries(rolePerms || {}).forEach(([moduleId, actions]) => {
        const canonical = canonicalModuleId(moduleId, aliases);
        if (!merged[canonical]) {
            return;
        }

        PERMISSION_KEYS.forEach((key) => {
            if (actions?.[key]) {
                merged[canonical][key] = true;
            }
        });
    });

    return merged;
}

export function registerPermissionSettings(Alpine) {
    Alpine.data('permissionSettingsPage', (config) => ({
        moduleCategories: config.moduleCategories || [],
        permissionAliases: config.permissionAliases || {},
        actionLabels: config.actionLabels || {},
        roles: config.roles || [],
        routes: config.routes || {},

        canAdd: config.canAdd !== false,
        canEdit: config.canEdit !== false,
        canDelete: config.canDelete !== false,

        columns: {
            name: { label: 'Tên vai trò' },
            note: { label: 'Ghi chú' },
        },

        loading: false,
        saving: false,
        deleting: false,
        toast: null,

        currentPage: normalizeCurrentPage(loadJson('nttu_permissions_page_v1', 1), 1),
        rowsPerPage: normalizeRowsPerPage(loadJson('nttu_permissions_rows_v1', 20), 20),
        sortConfig: loadJson('nttu_permissions_sort_v1', []),
        columnVisibility: loadJson('nttu_permissions_colvis_v1', { name: true, note: true }),
        filters: loadJson('nttu_permissions_filters_v1', { name: '', note: '' }),

        headerPopover: null,
        settingsOpen: false,
        rowsPerPageOptions: ROWS_PER_PAGE_OPTIONS_EXTENDED,

        dialogOpen: false,
        dialogMode: 'add',
        deleteOpen: false,
        selectedRole: null,
        form: { id: '', name: '', note: '', permissions: {} },
        initialForm: { id: '', name: '', note: '', permissions: {} },
        expandedGroups: {},

        permissionKeys: PERMISSION_KEYS,
        permissionLabels: PERMISSION_LABELS,
        permissionIconPaths: PERMISSION_ICON_PATHS,

        init() {
            this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 20);
            this.currentPage = normalizeCurrentPage(this.currentPage, this.totalPages);
            this.$watch('currentPage', (v) => localStorage.setItem('nttu_permissions_page_v1', String(v)));
        },

        get visibleColumnKeys() {
            return Object.keys(this.columns).filter((key) => this.columnVisibility[key]);
        },

        moduleIcon(name) {
            return iconNamePaths[name] || iconNamePaths.layout;
        },

        permissionIcon(key) {
            return this.permissionIconPaths[key] || iconNamePaths.shield;
        },

        columnHeaderIconPath(key) {
            return COLUMN_HEADER_ICONS[key] || '';
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
            saveJson('nttu_permissions_colvis_v1', this.columnVisibility);
        },

        selectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), true);
            saveJson('nttu_permissions_colvis_v1', this.columnVisibility);
        },

        deselectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), false);
            saveJson('nttu_permissions_colvis_v1', this.columnVisibility);
        },

        get hasActiveFilters() {
            return Object.values(this.filters).some((v) => !!v);
        },

        clearFilters() {
            this.filters = { name: '', note: '' };
            saveJson('nttu_permissions_filters_v1', this.filters);
            this.currentPage = 1;
        },

        get filteredItems() {
            return this.roles.filter((item) => Object.entries(this.filters).every(([key, value]) => {
                if (!value) return true;
                return String(item[key] ?? '').toLowerCase().includes(String(value).toLowerCase());
            }));
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

        get isChanged() {
            return JSON.stringify(this.form) !== JSON.stringify(this.initialForm);
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = null; }, 30000);
        },

        setFilter(key, value) {
            this.filters = { ...this.filters, [key]: value };
            this.currentPage = 1;
            saveJson('nttu_permissions_filters_v1', this.filters);
        },

        requestSort(key, direction) {
            this.sortConfig = [{ key, direction }];
            saveJson('nttu_permissions_sort_v1', this.sortConfig);
            this.headerPopover = null;
        },

        clearSort() {
            this.sortConfig = [];
            saveJson('nttu_permissions_sort_v1', []);
            this.headerPopover = null;
        },

        goPage(page) {
            this.currentPage = normalizeCurrentPage(page, this.totalPages);
        },

        setRowsPerPage(value) {
            this.rowsPerPage = normalizeRowsPerPage(value, 20);
            this.currentPage = 1;
            saveJson('nttu_permissions_rows_v1', this.rowsPerPage);
            localStorage.setItem('nttu_permissions_page_v1', '1');
        },

        toggleGroup(title) {
            this.expandedGroups = { ...this.expandedGroups, [title]: !this.expandedGroups[title] };
        },

        isGroupExpanded(title) {
            return !!this.expandedGroups[title];
        },

        openDialog(mode, role = null) {
            if (mode === 'add' && !this.canAdd) {
                return;
            }
            if (mode === 'edit' && !this.canEdit) {
                return;
            }

            this.dialogMode = mode;
            this.selectedRole = role;
            this.expandedGroups = {};

            if (mode === 'edit' && role) {
                this.form = {
                    id: role.id,
                    name: role.name || '',
                    note: role.note || '',
                    permissions: normalizeRolePermissions(
                        this.moduleCategories,
                        role.permissions || {},
                        this.permissionAliases,
                    ),
                };
            } else {
                this.form = {
                    id: '',
                    name: '',
                    note: '',
                    permissions: emptyPermissions(this.moduleCategories),
                };
            }

            this.initialForm = JSON.parse(JSON.stringify(this.form));
            this.dialogOpen = true;
        },

        closeDialog() {
            this.dialogOpen = false;
            this.selectedRole = null;
        },

        undoForm() {
            this.form = JSON.parse(JSON.stringify(this.initialForm));
            this.expandedGroups = {};
        },

        togglePermission(moduleId, key, value) {
            const current = this.form.permissions[moduleId] || Object.fromEntries(PERMISSION_KEYS.map((k) => [k, false]));
            const updated = { ...current, [key]: value };
            if (value && key !== 'access') {
                updated.access = true;
                if (key !== 'view') updated.view = true;
            }
            this.form.permissions = { ...this.form.permissions, [moduleId]: updated };
        },

        toggleRowPermissions(moduleId, value) {
            const modulePerms = Object.fromEntries(PERMISSION_KEYS.map((key) => [key, value]));
            this.form.permissions = { ...this.form.permissions, [moduleId]: modulePerms };
        },

        toggleColumnPermissions(key, value) {
            const next = { ...this.form.permissions };
            allModules(this.moduleCategories).forEach((module) => {
                const current = next[module.id] || Object.fromEntries(PERMISSION_KEYS.map((k) => [k, false]));
                const updated = { ...current, [key]: value };
                if (value && key !== 'access') {
                    updated.access = true;
                    if (key !== 'view') updated.view = true;
                }
                next[module.id] = updated;
            });
            this.form.permissions = next;
        },

        isColumnChecked(key) {
            return allModules(this.moduleCategories).every((module) => this.form.permissions?.[module.id]?.[key]);
        },

        isRowChecked(moduleId) {
            return PERMISSION_KEYS.every((key) => this.form.permissions?.[moduleId]?.[key]);
        },

        toggleAllPermissions() {
            const allChecked = allModules(this.moduleCategories).every((module) => this.isRowChecked(module.id));
            allModules(this.moduleCategories).forEach((module) => this.toggleRowPermissions(module.id, !allChecked));
        },

        async saveRole() {
            if (!this.form.name?.trim()) return;
            this.saving = true;

            const payload = {
                name: this.form.name.trim(),
                note: this.form.note || '',
                permissions: this.form.permissions,
            };

            const url = this.dialogMode === 'edit' && this.form.id
                ? this.routes.updateUrl.replace('__ID__', this.form.id)
                : this.routes.storeUrl;

            try {
                const res = await fetch(url, {
                    method: this.dialogMode === 'edit' ? 'PUT' : 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(body.message || 'Lưu thất bại');

                if (this.dialogMode === 'edit') {
                    this.roles = this.roles.map((role) => (role.id === body.item.id ? body.item : role));
                } else {
                    this.roles = [...this.roles, body.item].sort((a, b) => String(a.name).localeCompare(String(b.name), 'vi'));
                }

                this.closeDialog();
                this.showToast(body.message || 'Đã lưu thông tin vai trò.');
            } catch (err) {
                this.showToast(err.message || 'Có lỗi xảy ra khi lưu.', 'error');
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(role) {
            this.selectedRole = role;
            this.deleteOpen = true;
        },

        async deleteRole() {
            if (!this.selectedRole || !this.routes.destroyUrl) return;
            this.deleting = true;

            try {
                const res = await fetch(this.routes.destroyUrl.replace('__ID__', this.selectedRole.id), {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(body.message || 'Xóa thất bại');

                this.roles = this.roles.filter((role) => role.id !== this.selectedRole.id);
                this.deleteOpen = false;
                this.selectedRole = null;
                this.showToast(body.message || 'Đã xóa vai trò.');
            } catch (err) {
                this.showToast(err.message || 'Có lỗi xảy ra khi xóa.', 'error');
            } finally {
                this.deleting = false;
            }
        },
    }));
}
