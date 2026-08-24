import { allColumnsVisibleMap } from './nttu-column-visibility.js';
import { normalizeCurrentPage, normalizeRowsPerPage } from './nttu-pagination.js';
import { incidentBadgeClass } from './nttu-incident-colors.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

const STATUS_LABELS = {
    pending_review: 'Chờ duyệt',
    approved: 'Đã duyệt',
    rejected: 'Từ chối',
    completed: 'Hoàn thành',
};

const STATUS_CLASS = {
    pending_review: 'bg-amber-100 text-amber-800 border-amber-300',
    approved: 'bg-green-600 text-white border-green-600',
    rejected: 'bg-red-600 text-white border-red-600',
    completed: 'bg-blue-100 text-blue-800 border-blue-200',
};

const ALL_COLUMNS = {
    external: [
        { key: 'timestamp', label: 'Thời gian' },
        { key: 'room', label: 'Phòng' },
        { key: 'period', label: 'Tiết' },
        { key: 'classId', label: 'Lớp' },
        { key: 'studentCount', label: 'Sĩ số' },
        { key: 'actualStudentCount', label: 'SV tham gia' },
        { key: 'submittedBy', label: 'Giảng viên' },
        { key: 'className', label: 'Môn học' },
        { key: 'incident', label: 'Việc phát sinh' },
        { key: 'isNotification', label: 'Thông báo' },
        { key: 'incidentDetail', label: 'Chi tiết sự việc' },
        { key: 'status', label: 'Tình trạng' },
    ],
    online: [
        { key: 'timestamp', label: 'Thời gian' },
        { key: 'period', label: 'Tiết' },
        { key: 'classId', label: 'Lớp' },
        { key: 'studentCount', label: 'Sĩ số' },
        { key: 'actualStudentCount', label: 'SV tham gia' },
        { key: 'lecturer', label: 'Giảng viên' },
        { key: 'className', label: 'Môn học' },
        { key: 'incident', label: 'Việc phát sinh' },
        { key: 'evidence', label: 'Minh chứng' },
        { key: 'incidentDetail', label: 'Chi tiết sự việc' },
        { key: 'status', label: 'Tình trạng' },
    ],
};

function todayKey() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function itemDateKey(item, mode) {
    if (mode === 'external') {
        const raw = item.scheduleDate || item.timestampRaw || '';
        if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10);
        if (/^\d{2}\/\d{2}\/\d{4}/.test(raw)) {
            const [d, m, y] = raw.split('/');
            return `${y}-${m}-${d}`;
        }
    }
    const ts = item.timestampRaw;
    return ts ? ts.slice(0, 10) : '';
}

const COLUMN_ICONS = {
    timestamp: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    room: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
    period: 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14',
    classId: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824 2.998 12.078 12.078 0 01.665-6.479L12 14z',
    studentCount: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    actualStudentCount: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    submittedBy: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    lecturer: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    className: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    incident: 'M13 10V3L4 14h7v7l9-11h-7z',
    isNotification: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    incidentDetail: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    evidence: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    status: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
};

export function registerCheckinMonitor(Alpine) {
    Alpine.data('checkinMonitorPage', (config) => ({
        mode: config.mode || 'external',
        items: config.items || [],
        routes: config.routes || {},
        canEdit: config.canEdit !== false,
        canDelete: config.canDelete !== false,
        searchTerm: '',
        statusFilter: '',
        columnFilters: {},
        selectedIds: [],
        rowsPerPage: 10,
        currentPage: 1,
        reviewOpen: false,
        reviewMode: 'edit',
        selected: null,
        reviewForm: {},
        fullImage: null,
        saving: false,
        toast: null,
        sortKey: 'timestamp',
        sortDir: 'desc',
        colMenuOpen: false,
        openFilterCol: null,
        rowMenuOpen: null,
        detailCollapsed: true,
        visibleColumns: {},
        refreshTimer: null,
        lastRefresh: null,
        viewMode: config.mode === 'external' ? 'grid' : 'table',

        init() {
            const rowsKey = `checkin_monitor_${this.mode}_rows`;
            const saved = localStorage.getItem(rowsKey);
            if (saved) this.rowsPerPage = normalizeRowsPerPage(saved, 10);
            this.rowsPerPage = normalizeRowsPerPage(this.rowsPerPage, 10);

            const viewKey = `checkin_monitor_${this.mode}_view`;
            const savedView = localStorage.getItem(viewKey);
            if (savedView === 'grid' || savedView === 'table') {
                this.viewMode = savedView;
            }

            const visKey = `checkin_monitor_${this.mode}_cols`;
            try {
                this.visibleColumns = JSON.parse(localStorage.getItem(visKey) || '{}');
            } catch (_) {
                this.visibleColumns = {};
            }

            ALL_COLUMNS[this.mode].forEach((col) => {
                if (this.visibleColumns[col.key] === undefined) {
                    this.visibleColumns[col.key] = true;
                }
            });

            const prefsKey = `checkin_monitor_${this.mode}_prefs`;
            try {
                const savedPrefs = JSON.parse(localStorage.getItem(prefsKey) || '{}');
                if (typeof savedPrefs.searchTerm === 'string') this.searchTerm = savedPrefs.searchTerm;
                if (typeof savedPrefs.statusFilter === 'string') this.statusFilter = savedPrefs.statusFilter;
                if (savedPrefs.columnFilters && typeof savedPrefs.columnFilters === 'object') {
                    this.columnFilters = savedPrefs.columnFilters;
                }
                if (savedPrefs.sortKey) this.sortKey = savedPrefs.sortKey;
                if (savedPrefs.sortDir === 'asc' || savedPrefs.sortDir === 'desc') {
                    this.sortDir = savedPrefs.sortDir;
                }
                if (savedPrefs.currentPage) {
                    this.currentPage = normalizeCurrentPage(savedPrefs.currentPage, 1);
                }
            } catch (_) {}

            this.$watch('searchTerm', () => this.persistTablePrefs());
            this.$watch('statusFilter', () => this.persistTablePrefs());
            this.$watch('columnFilters', () => this.persistTablePrefs(), { deep: true });
            this.$watch('sortKey', () => this.persistTablePrefs());
            this.$watch('sortDir', () => this.persistTablePrefs());
            this.$watch('currentPage', () => this.persistTablePrefs());

            if (this.routes.feed) {
                this.refreshTimer = setInterval(() => this.refreshItems(), 60000);
            }
        },

        destroy() {
            if (this.refreshTimer) clearInterval(this.refreshTimer);
        },

        statusLabel(status) {
            return STATUS_LABELS[status] || status || '—';
        },

        statusClass(status) {
            return STATUS_CLASS[status] || 'bg-slate-100 text-slate-600 border-slate-200';
        },

        get allColumns() {
            return ALL_COLUMNS[this.mode] || [];
        },

        get normalizedRowsPerPage() {
            return normalizeRowsPerPage(this.rowsPerPage, 10);
        },

        get pageSize() {
            return this.viewMode === 'grid' ? 12 : this.normalizedRowsPerPage;
        },

        setViewMode(mode) {
            this.viewMode = mode;
            this.currentPage = 1;
            localStorage.setItem(`checkin_monitor_${this.mode}_view`, mode);
        },

        cardPhoto(item) {
            if (this.mode === 'external') {
                const urls = item.photoUrls || [];
                return urls[0] || item.photoUrl || null;
            }
            return item.evidence || null;
        },

        photoCount(item) {
            if (this.mode === 'external') {
                return (item.photoUrls || []).length || (item.photoUrl ? 1 : 0);
            }
            return item.evidence ? 1 : 0;
        },

        cardAccentClass(status) {
            if (status === 'approved') return 'from-green-50 to-emerald-100 border-green-200';
            if (status === 'rejected') return 'from-red-50 to-rose-100 border-red-200';
            return 'from-amber-50 to-orange-50 border-amber-200';
        },

        clearFilters() {
            this.searchTerm = '';
            this.statusFilter = '';
            this.columnFilters = {};
            this.currentPage = 1;
            this.persistTablePrefs();
        },

        persistTablePrefs() {
            localStorage.setItem(`checkin_monitor_${this.mode}_prefs`, JSON.stringify({
                searchTerm: this.searchTerm,
                statusFilter: this.statusFilter,
                columnFilters: this.columnFilters,
                sortKey: this.sortKey,
                sortDir: this.sortDir,
                currentPage: this.currentPage,
            }));
        },

        hasActiveFilters() {
            return Boolean(
                this.searchTerm.trim()
                || this.statusFilter
                || Object.values(this.columnFilters).some(Boolean),
            );
        },

        get columns() {
            return this.allColumns.filter((col) => this.visibleColumns[col.key] !== false);
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
            localStorage.setItem(`checkin_monitor_${this.mode}_cols`, JSON.stringify(this.visibleColumns));
        },

        selectAllColumns() {
            this.visibleColumns = allColumnsVisibleMap(
                this.allColumns.map((col) => col.key),
                true,
            );
            localStorage.setItem(`checkin_monitor_${this.mode}_cols`, JSON.stringify(this.visibleColumns));
        },

        deselectAllColumns() {
            this.visibleColumns = allColumnsVisibleMap(
                this.allColumns.map((col) => col.key),
                false,
            );
            localStorage.setItem(`checkin_monitor_${this.mode}_cols`, JSON.stringify(this.visibleColumns));
        },

        requestSort(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = 'asc';
            }
        },

        requestSortAsc(key) {
            this.sortKey = key;
            this.sortDir = 'asc';
            this.openFilterCol = null;
        },

        requestSortDesc(key) {
            this.sortKey = key;
            this.sortDir = 'desc';
            this.openFilterCol = null;
        },

        clearSort() {
            this.sortKey = 'timestamp';
            this.sortDir = 'desc';
            this.openFilterCol = null;
        },

        reviewStatusClass() {
            const s = this.reviewForm.status;
            if (s === 'approved') return 'text-green-700 bg-green-50 border-green-200';
            if (s === 'rejected') return 'text-red-700 bg-red-50 border-red-200';
            return 'text-amber-700 bg-amber-50 border-amber-200';
        },

        get stats() {
            const today = todayKey();
            const twoHoursAgo = Date.now() - 120 * 60 * 1000;
            const todayItems = this.items.filter((item) => itemDateKey(item, this.mode) === today);
            const ongoing = todayItems.filter((item) => {
                const ts = item.timestampRaw ? new Date(item.timestampRaw).getTime() : 0;
                return ts > twoHoursAgo && item.status !== 'rejected';
            }).length;
            const approvedToday = todayItems.filter((item) => item.status === 'approved').length;
            return { ongoing, totalToday: todayItems.length, approvedToday };
        },

        get filteredItems() {
            let rows = [...this.items];
            const q = this.searchTerm.trim().toLowerCase();
            if (q) {
                rows = rows.filter((item) =>
                    ['classId', 'className', 'lecturer', 'submittedBy'].some((k) =>
                        String(item[k] ?? '').toLowerCase().includes(q),
                    ),
                );
            }
            if (this.statusFilter) {
                rows = rows.filter((item) => item.status === this.statusFilter);
            }
            Object.entries(this.columnFilters).forEach(([key, val]) => {
                if (!val) return;
                const needle = String(val).toLowerCase();
                rows = rows.filter((item) => String(item[key] ?? '').toLowerCase().includes(needle));
            });

            const dir = this.sortDir === 'asc' ? 1 : -1;
            const key = this.sortKey;
            rows.sort((a, b) => {
                const av = String(a[key] ?? '');
                const bv = String(b[key] ?? '');
                return av.localeCompare(bv, 'vi') * dir;
            });

            return rows;
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredItems.length / this.pageSize));
        },

        get safePage() {
            return Math.min(Math.max(1, this.currentPage), this.totalPages);
        },

        get paginatedItems() {
            const start = (this.safePage - 1) * this.pageSize;
            return this.filteredItems.slice(start, start + this.pageSize);
        },

        rowNumber(index) {
            return (this.safePage - 1) * this.pageSize + index + 1;
        },

        toggleFilterCol(key) {
            this.openFilterCol = this.openFilterCol === key ? null : key;
        },

        incidentBadgeClass(value) {
            return incidentBadgeClass(value);
        },

        cellValue(item, key) {
            if (key === 'status') return this.statusLabel(item.status);
            if (key === 'isNotification') return item.isNotification ? 'Có' : 'Không';
            if (key === 'evidence') return item.evidence ? 'Có ảnh' : '—';
            if (key === 'incident') {
                const val = item.incident;
                return val && val !== 'none' ? val : '—';
            }
            if (key === 'incidentDetail') return item.incidentDetail || '—';
            return item[key] ?? '—';
        },

        setColumnFilter(key, value) {
            if (value) this.columnFilters[key] = value;
            else delete this.columnFilters[key];
            this.currentPage = 1;
        },

        toggleSelect(id) {
            if (this.selectedIds.includes(id)) {
                this.selectedIds = this.selectedIds.filter((x) => x !== id);
            } else {
                this.selectedIds = [...this.selectedIds, id];
            }
        },

        toggleSelectAll() {
            const ids = this.paginatedItems.map((i) => i.id);
            const all = ids.every((id) => this.selectedIds.includes(id));
            if (all) {
                this.selectedIds = this.selectedIds.filter((id) => !ids.includes(id));
            } else {
                this.selectedIds = [...new Set([...this.selectedIds, ...ids])];
            }
        },

        columnIcon(key) {
            return COLUMN_ICONS[key] || COLUMN_ICONS.status;
        },

        closeRowMenu() {
            this.rowMenuOpen = null;
        },

        openReview(item, mode = 'edit') {
            this.closeRowMenu();
            this.selected = item;
            this.reviewMode = mode;
            this.detailCollapsed = true;
            this.reviewForm = {
                status: item.status || 'pending_review',
                actual_student_count: item.actualStudentCount ?? item.studentCount ?? '',
                incident: item.incident || '',
                incident_detail: item.incidentDetail || '',
            };
            this.reviewOpen = true;
        },

        closeReview() {
            this.reviewOpen = false;
            this.selected = null;
            this.fullImage = null;
        },

        mapUrl(item) {
            const lat = item.latitude;
            const lng = item.longitude;
            if (!lat || !lng) return '';
            return `https://www.google.com/maps?q=${lat},${lng}`;
        },

        async refreshItems() {
            if (!this.routes.feed) return;
            try {
                const res = await fetch(this.routes.feed, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data.items)) {
                    this.items = data.items;
                    this.lastRefresh = new Date().toLocaleTimeString('vi-VN');
                }
            } catch (_) {}
        },

        async saveReview() {
            if (!this.canEdit) return;
            if (!this.selected || !this.routes.update) return;
            this.saving = true;
            try {
                const url = this.routes.update.replace('__ID__', this.selected.id);
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.reviewForm),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Lưu thất bại');
                const idx = this.items.findIndex((i) => i.id === this.selected.id);
                if (idx >= 0 && data.item) this.items[idx] = data.item;
                this.showToast(data.message || 'Đã lưu');
                this.closeReview();
            } catch (err) {
                this.showToast(err.message || 'Lỗi', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteSelected() {
            if (!this.canDelete) return;
            if (!this.selectedIds.length || !this.routes.bulkDestroy) return;
            if (!window.confirm(`Xóa ${this.selectedIds.length} bản ghi đã chọn?`)) return;
            await this.deleteIds(this.selectedIds);
        },

        async deleteSingle(id) {
            if (!this.canDelete) return;
            this.closeRowMenu();
            if (!window.confirm('Bạn có chắc chắn muốn xóa bản ghi này?')) return;
            await this.deleteIds([id]);
        },

        async deleteIds(ids) {
            if (!this.routes.bulkDestroy || !ids.length) return;
            try {
                const res = await fetch(this.routes.bulkDestroy, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ ids }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Xóa thất bại');
                this.items = this.items.filter((i) => !ids.includes(i.id));
                this.selectedIds = this.selectedIds.filter((id) => !ids.includes(id));
                this.showToast(data.message || 'Đã xóa');
            } catch (err) {
                this.showToast(err.message || 'Lỗi', 'error');
            }
        },

        setRowsPerPage(n) {
            this.rowsPerPage = normalizeRowsPerPage(n, 10);
            this.currentPage = 1;
            localStorage.setItem(`checkin_monitor_${this.mode}_rows`, String(this.rowsPerPage));
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = null; }, 30000);
        },
    }));
}
