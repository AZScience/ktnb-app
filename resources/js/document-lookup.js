import { renderFileTypeIcon, resolveFileIconType } from './file-type-icon.js';
import { normalizeCurrentPage, normalizeRowsPerPage, paginateSlice, ROWS_PER_PAGE_OPTIONS } from './nttu-pagination.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function isoToDisplay(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

function displayToIso(display) {
    if (!display || !display.includes('/')) return '';
    const [d, m, y] = display.split('/');
    return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
}

export function resolveOriginalFileUrl(value) {
    if (!value) return '';
    if (value.includes(':::')) {
        return value.split(':::').pop() || '';
    }
    return value;
}

export function getFileInfo(url, displayName = null) {
    if (!url && !displayName) {
        return { ext: '', name: 'Không có file', color: 'text-slate-400', bg: 'bg-slate-100', isLink: false, isPdf: false, isImage: false, isOffice: false };
    }

    let name = displayName || 'Tài liệu';
    const fileUrl = resolveOriginalFileUrl(url || '');

    if (!displayName) {
        if (url && url.includes(':::')) {
            name = url.split(':::')[0] || name;
        } else {
            try {
                const decoded = decodeURIComponent(fileUrl || url || '');
                const parts = decoded.split('/');
                name = (parts[parts.length - 1]?.split('?')[0] || decoded) || name;
            } catch (_) {
                name = fileUrl || url || name;
            }
        }
    }

    const isDirectLink = fileUrl.startsWith('http') && !fileUrl.includes('/storage/');

    const ext = (name.split('.').pop() || '').toLowerCase();
    let color = 'text-slate-500';
    let bg = 'bg-slate-100';

    if (ext === 'pdf') {
        color = 'text-rose-600';
        bg = 'bg-rose-50';
    } else if (['doc', 'docx'].includes(ext)) {
        color = 'text-blue-600';
        bg = 'bg-blue-50';
    } else if (['xls', 'xlsx', 'csv'].includes(ext)) {
        color = 'text-emerald-600';
        bg = 'bg-emerald-50';
    } else if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(ext)) {
        color = 'text-amber-600';
        bg = 'bg-amber-50';
    } else if (['zip', 'rar', '7z'].includes(ext)) {
        color = 'text-purple-600';
        bg = 'bg-purple-50';
    } else if (isDirectLink) {
        color = 'text-[var(--nttu-primary)]';
        bg = 'bg-cyan-50';
    }

    return {
        ext: ext ? ext.toUpperCase() : '',
        name,
        color,
        bg,
        iconType: resolveFileIconType(ext, isDirectLink),
        isLink: isDirectLink,
        isPdf: ext === 'pdf',
        isImage: ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp'].includes(ext),
        isOffice: ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext),
    };
}

export function registerDocumentLookup(Alpine) {
    Alpine.data('documentLookupPage', (config) => {
        const storageKey = 'doc_lookup_v2';
        let saved = {};
        try {
            saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        } catch (_) {}

        return {
            docTypes: config.docTypes || [],
            searchUrl: config.searchUrl || '',
            verifyUrlTemplate: config.verifyUrlTemplate || '',
            searchTerm: saved.searchTerm || '',
            selectedDocTypes: saved.selectedDocTypes || [],
            issueDateIso: saved.issueDateIso || '',
            searchMode: saved.searchMode || 'partial',
            searchLogic: saved.searchLogic || 'and',
            searchScopes: saved.searchScopes || ['title'],
            rowsPerPage: normalizeRowsPerPage(saved.rowsPerPage || 10, 10),
            currentPage: normalizeCurrentPage(saved.currentPage || 1, 1),
            leftPanelWidth: saved.leftPanelWidth || 420,
            typeMenuOpen: false,
            loading: false,
            searched: false,
            items: [],
            selectedDoc: null,
            fullPreview: false,
            passwordInput: '',
            unlockedIds: {},
            toast: null,
            searchTimer: null,

            rowsPerPageOptions: ROWS_PER_PAGE_OPTIONS,

            init() {
                this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 10);
                this.currentPage = normalizeCurrentPage(this.currentPage, this.totalPages);
                this.$watch('searchTerm', () => this.scheduleSearch());
                this.$watch('issueDateIso', () => this.scheduleSearch());
                this.$watch('searchMode', () => this.scheduleSearch());
                this.$watch('searchLogic', () => this.scheduleSearch());
                this.$watch('selectedDocTypes', () => this.scheduleSearch());
                this.$watch('searchScopes', () => this.scheduleSearch());

                if (this.hasActiveFilters) {
                    this.handleSearch();
                }
            },

            scheduleSearch() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => {
                    this.handleSearch();
                }, 350);
            },

            scopeOptions: [
                { key: 'doc_number', label: 'Số hiệu văn bản' },
                { key: 'title', label: 'Tiêu đề văn bản' },
                { key: 'abstract', label: 'Trích yếu nội dung văn bản' },
                { key: 'extracted_text', label: 'Nội dung văn bản' },
                { key: 'signer', label: 'Người ký' },
                { key: 'issuing_body', label: 'Cơ quan ban hành' },
                { key: 'original_file', label: 'Tên file văn bản' },
            ],

            persist() {
                localStorage.setItem(storageKey, JSON.stringify({
                    searchTerm: this.searchTerm,
                    selectedDocTypes: this.selectedDocTypes,
                    issueDateIso: this.issueDateIso,
                    searchMode: this.searchMode,
                    searchLogic: this.searchLogic,
                    searchScopes: this.searchScopes,
                    rowsPerPage: this.rowsPerPage,
                    currentPage: this.currentPage,
                    leftPanelWidth: this.leftPanelWidth,
                }));
            },

            get issueDateDisplay() {
                return isoToDisplay(this.issueDateIso);
            },

            get hasActiveFilters() {
                return !!(this.searchTerm.trim() || this.selectedDocTypes.length || this.issueDateIso);
            },

            get sortedItems() {
                return [...this.items];
            },

            get normalizedRowsPerPage() {
                return normalizeRowsPerPage(this.rowsPerPage, 10);
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.sortedItems.length / this.normalizedRowsPerPage));
            },

            get safeCurrentPage() {
                return normalizeCurrentPage(this.currentPage, this.totalPages);
            },

            get paginatedItems() {
                return paginateSlice(
                    this.sortedItems,
                    this.currentPage,
                    this.normalizedRowsPerPage,
                ).items;
            },

            get dynamicPlaceholder() {
                const labels = this.scopeOptions
                    .filter((s) => this.searchScopes.includes(s.key))
                    .map((s) => s.label.replace(' văn bản', '').toLowerCase());
                if (labels.length > 1) return `Tìm theo ${labels.join(' | ')}...`;
                return 'Nhập từ khóa tìm kiếm...';
            },

            fileInfo(item) {
                return getFileInfo(item?.original_file || '', item?.file_name || null);
            },

            fileIconHtml(item, className = 'h-7 w-7') {
                const info = this.fileInfo(item);
                return renderFileTypeIcon(info.iconType, className);
            },

            fileDisplayName(item) {
                if (item?.file_name) {
                    return item.file_name;
                }
                return this.fileInfo(item).name;
            },

            postedBy(item) {
                return item?.posted_by || '—';
            },

            docTypeLine(item) {
                const raw = item?.doc_type?.trim();
                if (!raw) return '—';
                return raw.replace(/\s*\([^)]+\)\s*$/, '') || raw;
            },

            metaSummary(item) {
                return `Người đăng: ${this.postedBy(item)} · Số VB: ${item?.doc_number?.trim() || '—'} · Loại: ${this.docTypeLine(item)}`;
            },

            previewFileUrl(item) {
                return resolveOriginalFileUrl(item?.original_file || '');
            },

            isUnlocked(item) {
                if (!item?.requires_password) return true;
                return !!this.unlockedIds[item.id];
            },

            requiresPassword(item) {
                return item?.requires_password && !this.isUnlocked(item);
            },

            previewUrl(item) {
                const url = this.previewFileUrl(item);
                if (!url) return '';
                const info = getFileInfo(url, item?.file_name || null);
                if (info.isPdf) return `${url}#toolbar=0`;
                if (info.isOffice) {
                    return `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}`;
                }
                if (info.isImage) return url;
                return `https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true`;
            },

            toggleScope(key) {
                if (this.searchScopes.includes(key)) {
                    if (this.searchScopes.length > 1) {
                        this.searchScopes = this.searchScopes.filter((s) => s !== key);
                    }
                } else {
                    this.searchScopes = [...this.searchScopes, key];
                }
                this.persist();
            },

            toggleDocType(name) {
                if (this.selectedDocTypes.includes(name)) {
                    this.selectedDocTypes = this.selectedDocTypes.filter((t) => t !== name);
                } else {
                    this.selectedDocTypes = [...this.selectedDocTypes, name];
                }
                this.persist();
            },

            clearDocTypes() {
                this.selectedDocTypes = [];
                this.persist();
            },

            async handleSearch(options = {}) {
                const requireFilters = options.requireFilters === true;

                if (!this.hasActiveFilters) {
                    this.searched = false;
                    this.items = [];
                    this.selectedDoc = null;
                    this.loading = false;
                    this.persist();
                    if (requireFilters) {
                        this.showToast('Nhập từ khóa hoặc chọn bộ lọc để tra cứu', 'error');
                    }
                    return;
                }
                this.persist();
                this.loading = true;
                this.searched = true;
                this.currentPage = 1;
                this.selectedDoc = null;
                try {
                    const params = new URLSearchParams();
                    if (this.searchTerm.trim()) params.set('q', this.searchTerm.trim());
                    this.selectedDocTypes.forEach((t) => params.append('doc_types[]', t));
                    if (this.issueDateDisplay) params.set('issue_date', this.issueDateDisplay);
                    params.set('mode', this.searchMode);
                    params.set('logic', this.searchLogic);
                    this.searchScopes.forEach((s) => params.append('scopes[]', s));

                    const res = await fetch(`${this.searchUrl}?${params}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('Không tra cứu được');
                    const data = await res.json();
                    this.items = data.items || [];
                    if (this.items.length === 1) {
                        this.selectDoc(this.items[0]);
                    }
                } catch (err) {
                    this.showToast(err.message || 'Lỗi tra cứu', 'error');
                    this.items = [];
                } finally {
                    this.loading = false;
                }
            },

            selectDoc(item) {
                this.selectedDoc = item;
            },

            closePreview() {
                this.selectedDoc = null;
            },

            async unlockPassword() {
                if (!this.selectedDoc || !this.passwordInput) return;
                const url = this.verifyUrlTemplate.replace('__ID__', this.selectedDoc.id);
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ password: this.passwordInput }),
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Sai mật khẩu');
                    this.unlockedIds[this.selectedDoc.id] = true;
                    this.selectedDoc = { ...this.selectedDoc, ...data.item, requires_password: false };
                    this.passwordInput = '';
                    this.showToast('Đã mở khóa tài liệu');
                } catch (err) {
                    this.showToast(err.message || 'Sai mật khẩu', 'error');
                }
            },

            openExternal(url) {
                if (url) window.open(url, '_blank', 'noopener');
            },

            startResize(e) {
                e.preventDefault();
                const startX = e.clientX;
                const startW = this.leftPanelWidth;
                const onMove = (ev) => {
                    this.leftPanelWidth = Math.max(250, Math.min(800, startW + (ev.clientX - startX)));
                };
                const onUp = () => {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                    this.persist();
                };
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            },

            showToast(message, type = 'success') {
                this.toast = { message, type };
                setTimeout(() => { this.toast = null; }, 30000);
            },

            setRowsPerPage(value) {
                this.rowsPerPage = normalizeRowsPerPage(value, 10);
                this.currentPage = 1;
                this.persist();
            },

            setPage(p) {
                this.currentPage = normalizeCurrentPage(p, this.totalPages);
                this.persist();
            },
        };
    });
}
