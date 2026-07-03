import { allColumnsVisibleMap } from './nttu-column-visibility.js';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const COL_STORAGE_KEY = 'nttu_project_files_colvis_v1';
const PREFS_STORAGE_KEY = 'nttu_project_files_prefs_v1';

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

async function readJson(res) {
    const body = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(body.message || Object.values(body.errors || {}).flat().join(', ') || 'Thao tác thất bại.');
    }
    return body;
}

async function walkEntry(entry, prefix, bucket) {
    if (entry.isFile) {
        const file = await new Promise((resolve, reject) => entry.file(resolve, reject));
        const path = prefix ? `${prefix}/${file.name}` : file.name;
        bucket.push({ file, path });
        return;
    }

    if (!entry.isDirectory) {
        return;
    }

    const reader = entry.createReader();
    const readAll = async () => {
        const batch = await new Promise((resolve, reject) => reader.readEntries(resolve, reject));
        if (!batch.length) {
            return;
        }

        for (const child of batch) {
            await walkEntry(child, prefix ? `${prefix}/${entry.name}` : entry.name, bucket);
        }

        await readAll();
    };

    await readAll();
}

async function collectFilesFromDataTransfer(dataTransfer) {
    const items = [...(dataTransfer?.items || [])];
    const entries = items
        .map((item) => (item.webkitGetAsEntry ? item.webkitGetAsEntry() : null))
        .filter(Boolean);

    if (entries.length) {
        const bucket = [];
        for (const entry of entries) {
            await walkEntry(entry, '', bucket);
        }
        if (bucket.length) {
            return bucket;
        }
    }

    return [...(dataTransfer?.files || [])].map((file) => ({
        file,
        path: file.webkitRelativePath || file.name,
    }));
}

export function registerProjectFiles(Alpine) {
    const savedPrefs = loadJson(PREFS_STORAGE_KEY, {});

    Alpine.data('projectFilesPage', (config) => ({
        routes: config.routes || {},
        canAdd: config.canAdd !== false,
        canEdit: config.canEdit !== false,
        canDelete: config.canDelete !== false,
        canExport: config.canExport !== false,
        currentPath: '',
        breadcrumbs: [{ label: '', path: '' }],
        items: [],
        loading: false,
        saving: false,
        toast: null,
        headerPopover: null,
        settingsOpen: false,
        rowMenuOpen: null,
        dragOver: false,
        filters: savedPrefs.filters || {},
        sortKey: savedPrefs.sortKey || 'name',
        sortDir: savedPrefs.sortDir || 'asc',
        columns: {
            name: { label: 'Tên' },
            type: { label: 'Loại' },
            size: { label: 'Dung lượng' },
            modified_at: { label: 'Sửa lần cuối' },
        },
        columnVisibility: loadJson(COL_STORAGE_KEY, {
            name: true,
            type: true,
            size: true,
            modified_at: true,
        }),
        editorOpen: false,
        editorFile: null,
        editorContent: '',
        editorOriginal: '',
        createOpen: false,
        createType: 'file',
        createName: '',
        renameOpen: false,
        deleteOpen: false,
        moveOpen: false,
        extractOpen: false,
        compressOpen: false,
        selected: null,
        selectedPaths: [],
        renameValue: '',
        moveDestination: '',
        extractDestination: '',
        compressName: '',
        deleteBulkMode: false,

        async init() {
            this.$watch('filters', () => this.persistTablePrefs(), { deep: true });
            this.$watch('sortKey', () => this.persistTablePrefs());
            this.$watch('sortDir', () => this.persistTablePrefs());
            await this.loadDirectory('');
        },

        persistTablePrefs() {
            saveJson(PREFS_STORAGE_KEY, {
                filters: this.filters,
                sortKey: this.sortKey,
                sortDir: this.sortDir,
            });
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => {
                if (this.toast?.message === message) this.toast = null;
            }, 3200);
        },

        get visibleColumnKeys() {
            return Object.keys(this.columns).filter((key) => this.columnVisibility[key]);
        },

        get filteredItems() {
            return this.items.filter((item) => Object.entries(this.filters).every(([key, value]) => {
                if (!value) return true;
                const q = String(value).toLowerCase();
                if (key === 'name') {
                    return String(item.name || '').toLowerCase().includes(q);
                }
                if (key === 'type') {
                    const label = item.type === 'directory' ? 'thư mục' : String(item.extension || 'file');
                    return label.toLowerCase().includes(q);
                }
                if (key === 'size') {
                    return String(item.size_label || '').toLowerCase().includes(q);
                }
                if (key === 'modified_at') {
                    return String(item.modified_at || '').toLowerCase().includes(q);
                }
                return true;
            }));
        },

        get sortedItems() {
            const items = [...this.filteredItems];
            if (!this.sortKey) {
                return items;
            }
            const key = this.sortKey === 'modified_at' ? 'modified_ts' : this.sortKey;
            const dir = this.sortDir === 'asc' ? 1 : -1;

            items.sort((a, b) => {
                let av = a[key];
                let bv = b[key];
                if (key === 'type') {
                    av = a.type === 'directory' ? '0' : `1${a.extension || ''}`;
                    bv = b.type === 'directory' ? '0' : `1${b.extension || ''}`;
                }
                if (av === bv) return 0;
                if (av === null || av === undefined || av === '') return 1;
                if (bv === null || bv === undefined || bv === '') return -1;
                if (av < bv) return -1 * dir;
                if (av > bv) return 1 * dir;
                return 0;
            });

            return items;
        },

        sortDirection(key) {
            const sortKey = key === 'modified_at' ? 'modified_ts' : key;
            const mapped = this.sortKey === 'modified_ts' ? 'modified_at' : this.sortKey;
            if (mapped !== key) return null;
            return this.sortDir;
        },

        clearSort() {
            this.sortKey = null;
            this.sortDir = 'asc';
            this.headerPopover = null;
        },

        isFiltered(key) {
            return !!this.filters[key];
        },

        sortIconClass(key) {
            return this.isFiltered(key) ? 'nttu-sort-filtered' : '';
        },

        setFilter(key, value) {
            this.filters = { ...this.filters, [key]: value };
        },

        clearColumnFilter(key) {
            this.setFilter(key, '');
            this.headerPopover = null;
        },

        requestSort(key) {
            const sortKey = key === 'modified_at' ? 'modified_ts' : key;
            if (this.sortKey === sortKey) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = sortKey;
                this.sortDir = 'asc';
            }
            this.headerPopover = null;
        },

        toggleColumn(key) {
            this.columnVisibility = { ...this.columnVisibility, [key]: !this.columnVisibility[key] };
            localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(this.columnVisibility));
        },

        selectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), true);
            localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(this.columnVisibility));
        },

        deselectAllColumns() {
            this.columnVisibility = allColumnsVisibleMap(Object.keys(this.columns), false);
            localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(this.columnVisibility));
        },

        closeRowMenu() {
            this.rowMenuOpen = null;
        },

        typeLabel(item) {
            if (item?.type === 'directory') return 'Thư mục';
            if (item?.is_archive) return 'File nén';
            return item?.extension || 'file';
        },

        get selectedSet() {
            return new Set(this.selectedPaths);
        },

        get selectedCount() {
            return this.selectedPaths.length;
        },

        get allVisibleSelected() {
            return this.sortedItems.length > 0
                && this.sortedItems.every((item) => this.selectedSet.has(item.path));
        },

        get someVisibleSelected() {
            return this.sortedItems.some((item) => this.selectedSet.has(item.path));
        },

        get selectedArchivePaths() {
            return this.selectedPaths.filter((path) => {
                const item = this.items.find((row) => row.path === path);
                return item?.is_archive;
            });
        },

        isRowSelected(path) {
            return this.selectedSet.has(path);
        },

        toggleRowSelect(path) {
            if (this.selectedSet.has(path)) {
                this.selectedPaths = this.selectedPaths.filter((itemPath) => itemPath !== path);
            } else {
                this.selectedPaths = [...this.selectedPaths, path];
            }
        },

        toggleSelectAllVisible() {
            if (this.allVisibleSelected) {
                const visible = new Set(this.sortedItems.map((item) => item.path));
                this.selectedPaths = this.selectedPaths.filter((path) => !visible.has(path));
            } else {
                const merged = new Set(this.selectedPaths);
                this.sortedItems.forEach((item) => merged.add(item.path));
                this.selectedPaths = [...merged];
            }
        },

        clearSelection() {
            this.selectedPaths = [];
        },

        pruneSelection() {
            const existing = new Set(this.items.map((item) => item.path));
            this.selectedPaths = this.selectedPaths.filter((path) => existing.has(path));
        },

        async loadDirectory(path) {
            this.loading = true;
            try {
                const url = new URL(this.routes.list, window.location.origin);
                url.searchParams.set('path', path || '');
                const res = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await readJson(res);
                this.currentPath = data.path || '';
                this.breadcrumbs = data.breadcrumbs || [];
                this.items = data.items || [];
                this.pruneSelection();
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        openDirectory(item) {
            if (item?.type !== 'directory') return;
            this.closeRowMenu();
            void this.loadDirectory(item.path);
        },

        goToPath(path) {
            void this.loadDirectory(path || '');
        },

        openParent() {
            const parts = (this.currentPath || '').split('/').filter(Boolean);
            parts.pop();
            void this.loadDirectory(parts.join('/'));
        },

        downloadItem(item) {
            if (!item?.path) return;
            void this.downloadPaths([item.path]);
        },

        async downloadPaths(paths) {
            const list = [...new Set((paths || []).filter(Boolean))];
            if (!list.length) return;

            if (list.length === 1) {
                const item = this.items.find((row) => row.path === list[0]);
                if (item?.type === 'file') {
                    const url = new URL(this.routes.download, window.location.origin);
                    url.searchParams.set('path', list[0]);
                    window.location.href = url.toString();
                    return;
                }
            }

            this.saving = true;
            try {
                const res = await fetch(this.routes.batchDownload, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/octet-stream',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ paths: list }),
                });

                if (!res.ok) {
                    const body = await res.json().catch(() => ({}));
                    throw new Error(body.message || 'Tải xuống thất bại.');
                }

                const blob = await res.blob();
                const disposition = res.headers.get('Content-Disposition') || '';
                const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
                const filename = match?.[1] || `download-${Date.now()}.zip`;
                const objectUrl = URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = objectUrl;
                anchor.download = filename;
                anchor.click();
                URL.revokeObjectURL(objectUrl);
                this.showToast(`Đã tải xuống ${list.length} mục.`);
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        downloadSelected() {
            void this.downloadPaths(this.selectedPaths);
        },

        openBulkCompress() {
            if (!this.selectedPaths.length) return;
            this.compressName = `archive-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.zip`;
            this.compressOpen = true;
        },

        openCompressItem(item) {
            if (!item?.path) return;
            this.closeRowMenu();
            if (!this.isRowSelected(item.path)) {
                this.selectedPaths = [...this.selectedPaths, item.path];
            }
            this.openBulkCompress();
        },

        async submitCompress() {
            if (!this.selectedPaths.length) return;
            this.saving = true;
            try {
                const res = await fetch(this.routes.compress, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        paths: this.selectedPaths,
                        path: this.currentPath || '',
                        name: this.compressName.trim(),
                    }),
                });
                const data = await readJson(res);
                this.compressOpen = false;
                this.clearSelection();
                this.applyListing(data.listing, data.message);
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        openBulkExtract() {
            const archives = this.selectedArchivePaths;
            if (!archives.length) {
                this.showToast('Chọn ít nhất một file nén (.zip, .tar.gz...).', 'error');
                return;
            }

            if (archives.length === 1) {
                const item = this.items.find((row) => row.path === archives[0]);
                if (item) {
                    this.openExtract(item);
                }
                return;
            }

            void this.submitExtractMany(archives);
        },

        async submitExtractMany(paths) {
            this.saving = true;
            try {
                const res = await fetch(this.routes.extract, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ paths }),
                });
                const data = await readJson(res);
                this.extractOpen = false;
                this.selected = null;
                this.clearSelection();
                this.applyListing(data.listing, data.message);
                if (data.errors?.length) {
                    this.showToast(data.errors.slice(0, 2).join(' | '), 'error');
                }
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async openEditor(item) {
            if (!item?.editable) {
                this.showToast('File này không hỗ trợ sửa trực tiếp. Hãy tải xuống.', 'error');
                return;
            }

            this.closeRowMenu();
            this.loading = true;
            try {
                const url = new URL(this.routes.show, window.location.origin);
                url.searchParams.set('path', item.path);
                const res = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await readJson(res);
                this.editorFile = data;
                this.editorContent = data.content || '';
                this.editorOriginal = data.content || '';
                this.editorOpen = true;
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        revertEditor() {
            this.editorContent = this.editorOriginal;
        },

        closeEditor() {
            this.editorOpen = false;
            this.editorFile = null;
            this.editorContent = '';
            this.editorOriginal = '';
        },

        get editorChanged() {
            return this.editorContent !== this.editorOriginal;
        },

        async saveEditor() {
            if (!this.editorFile?.path) return;
            this.saving = true;
            try {
                const res = await fetch(this.routes.update, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        path: this.editorFile.path,
                        content: this.editorContent,
                    }),
                });
                const data = await readJson(res);
                this.editorFile = data.file;
                this.editorOriginal = data.file?.content || this.editorContent;
                this.showToast(data.message || 'Đã lưu file.');
                await this.loadDirectory(this.currentPath);
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        openCreate(type = 'file') {
            this.createType = type;
            this.createName = '';
            this.createOpen = true;
        },

        async uploadSelectedFile(event) {
            const files = [...(event.target.files || [])];
            event.target.value = '';
            await this.uploadBatchEntries(files.map((file) => ({
                file,
                path: file.webkitRelativePath || file.name,
            })));
        },

        async uploadSelectedFolder(event) {
            const files = [...(event.target.files || [])];
            event.target.value = '';
            await this.uploadBatchEntries(files.map((file) => ({
                file,
                path: file.webkitRelativePath || file.name,
            })));
        },

        async onDrop(event) {
            event.preventDefault();
            this.dragOver = false;
            if (this.saving) return;

            try {
                const entries = await collectFilesFromDataTransfer(event.dataTransfer);
                await this.uploadBatchEntries(entries);
            } catch (err) {
                this.showToast(err.message || 'Không thể đọc dữ liệu kéo thả.', 'error');
            }
        },

        onDragOver(event) {
            event.preventDefault();
            this.dragOver = true;
        },

        onDragLeave(event) {
            if (event.currentTarget === event.target) {
                this.dragOver = false;
            }
        },

        async uploadBatchEntries(entries) {
            if (!entries.length) return;

            const formData = new FormData();
            formData.append('path', this.currentPath || '');
            entries.forEach(({ file, path }) => {
                formData.append('files[]', file);
                formData.append('paths[]', path);
            });

            this.saving = true;
            try {
                const res = await fetch(this.routes.batchUpload, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                const data = await readJson(res);
                this.applyListing(data.listing, data.message);
                if (data.errors?.length) {
                    this.showToast(data.errors.slice(0, 3).join(' | '), 'error');
                }
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async submitCreate() {
            const name = this.createName.trim();
            if (!name) return;

            this.saving = true;
            try {
                const res = await fetch(this.routes.store, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        type: this.createType === 'folder' ? 'folder' : 'file',
                        path: this.currentPath || '',
                        name,
                        content: '',
                    }),
                });
                const data = await readJson(res);
                this.createOpen = false;
                this.createName = '';
                this.applyListing(data.listing, data.message);
                if (this.createType === 'file' && data.path) {
                    const item = (data.listing?.items || []).find((row) => row.path === data.path);
                    if (item) await this.openEditor(item);
                }
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        openRename(item) {
            this.closeRowMenu();
            this.selected = item;
            this.renameValue = item?.name || '';
            this.renameOpen = true;
        },

        openMove(item) {
            this.closeRowMenu();
            this.selected = item;
            this.moveDestination = this.currentPath || '';
            this.moveOpen = true;
        },

        openExtract(item) {
            this.closeRowMenu();
            this.selected = item;
            const base = (item?.name || '').replace(/\.(tar\.gz|tgz|zip|tar|gz)$/i, '');
            const parent = (item?.path || '').split('/').slice(0, -1).join('/');
            this.extractDestination = parent ? `${parent}/${base}` : base;
            this.extractOpen = true;
        },

        async submitRename() {
            if (!this.selected?.path || !this.renameValue.trim()) return;
            this.saving = true;
            try {
                const res = await fetch(this.routes.rename, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        path: this.selected.path,
                        name: this.renameValue.trim(),
                    }),
                });
                const data = await readJson(res);
                this.renameOpen = false;
                this.selected = null;
                this.applyListing(data.listing, data.message);
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async submitMove() {
            if (!this.selected?.path) return;
            this.saving = true;
            try {
                const res = await fetch(this.routes.move, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        path: this.selected.path,
                        destination: this.moveDestination.trim() === '.' ? '' : this.moveDestination.trim(),
                    }),
                });
                const data = await readJson(res);
                this.moveOpen = false;
                this.selected = null;
                await this.loadDirectory(this.currentPath);
                this.showToast(data.message || 'Đã di chuyển.');
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async submitExtract() {
            if (!this.selected?.path) return;
            this.saving = true;
            try {
                const res = await fetch(this.routes.extract, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        path: this.selected.path,
                        destination: this.extractDestination.trim() || null,
                    }),
                });
                const data = await readJson(res);
                this.extractOpen = false;
                this.selected = null;
                if (data.listing) {
                    this.applyListing(data.listing, data.message);
                    if (data.destination) {
                        void this.loadDirectory(data.destination);
                    }
                } else {
                    this.showToast(data.message);
                }
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        openDelete(item) {
            this.closeRowMenu();
            this.selected = item;
            this.deleteBulkMode = false;
            this.deleteOpen = true;
        },

        openBulkDelete() {
            if (!this.selectedPaths.length) return;
            this.selected = null;
            this.deleteBulkMode = true;
            this.deleteOpen = true;
        },

        async submitDelete() {
            const paths = this.deleteBulkMode ? this.selectedPaths : (this.selected?.path ? [this.selected.path] : []);
            if (!paths.length) return;

            this.saving = true;
            try {
                const res = await fetch(this.routes.destroy, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(paths.length > 1 ? { paths } : { path: paths[0] }),
                });
                const data = await readJson(res);
                this.deleteOpen = false;
                this.selected = null;
                this.deleteBulkMode = false;
                this.clearSelection();
                this.applyListing(data.listing, data.message);
                if (data.errors?.length) {
                    this.showToast(data.errors.slice(0, 2).join(' | '), 'error');
                }
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        applyListing(listing, message) {
            if (listing) {
                this.currentPath = listing.path || this.currentPath;
                this.breadcrumbs = listing.breadcrumbs || this.breadcrumbs;
                this.items = listing.items || [];
                this.pruneSelection();
            }
            this.closeRowMenu();
            if (message) this.showToast(message);
        },
    }));
}
