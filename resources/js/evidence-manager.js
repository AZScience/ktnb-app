import { alertMessage, getLanguage } from './language.js';

function parseEvidenceItem(item) {
    if (!item) return { name: '', data: '' };
    if (item.includes(':::')) {
        const idx = item.indexOf(':::');
        return { name: item.slice(0, idx), data: item.slice(idx + 3) };
    }
    return { name: '', data: item };
}

function isImageItem(item) {
    const { name, data } = parseEvidenceItem(item);
    const target = (data || name || '').toLowerCase();
    return target.startsWith('data:image')
        || /\.(jpg|jpeg|png|webp|gif|svg)(\?|$)/i.test(target)
        || target.includes('/image/');
}

function isVideoItem(item) {
    const { data } = parseEvidenceItem(item);
    const target = (data || '').toLowerCase();
    return target.startsWith('data:video') || /\.(mp4|webm|ogg|mov)(\?|$)/i.test(target);
}

function isDocumentItem(item) {
    return !isImageItem(item) && !isVideoItem(item);
}

const SOURCE_BADGE = {
    direct: 'bg-blue-600',
    online: 'bg-purple-600',
    homeroom: 'bg-amber-600',
    practice: 'bg-emerald-600',
    exams: 'bg-rose-600',
    checkin: 'bg-green-600',
    requests: 'bg-sky-600',
    petitions: 'bg-teal-600',
    asset_check: 'bg-orange-600',
    violations: 'bg-red-600',
    document_records: 'bg-blue-800',
    shift_schedule: 'bg-slate-900',
};

export function registerEvidenceManager(Alpine) {
    Alpine.data('evidenceManager', (config = {}) => ({
        routes: config.routes || {},
        canDelete: !!config.can_delete,
        viewMode: 'grid',
        items: [],
        loading: true,
        searchTerm: '',
        filterSource: 'all',
        filterType: 'all',
        filterDate: '',
        currentPage: 1,
        itemsPerPage: 12,
        selectedEvidence: null,
        previewItem: null,
        deleteTarget: null,
        deleting: false,

        sourceOptions: [
            { value: 'all', label: 'Tất cả nguồn' },
            { value: 'direct', label: 'Lớp học trực tiếp' },
            { value: 'online', label: 'Lớp học online' },
            { value: 'homeroom', label: 'Cố vấn học tập' },
            { value: 'practice', label: 'Thực hành ngoài' },
            { value: 'exams', label: 'Thi kết thúc môn' },
            { value: 'checkin', label: 'Check-in Giảng viên' },
            { value: 'requests', label: 'Tiếp nhận yêu cầu' },
            { value: 'petitions', label: 'Tiếp nhận đơn thư' },
            { value: 'asset_check', label: 'Nhận - Trả tài sản' },
            { value: 'violations', label: 'Sinh viên vi phạm' },
            { value: 'document_records', label: 'Quản lý hồ sơ' },
            { value: 'shift_schedule', label: 'Lịch trực hệ thống' },
        ],

        init() {
            this.loadData();
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        async loadData() {
            if (!this.routes.data) return;
            this.loading = true;
            try {
                const res = await fetch(this.routes.data, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.items = data.items || [];
                this.canDelete = !!data.can_delete;
            } catch {
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        parseItem: parseEvidenceItem,
        isImage: isImageItem,
        isVideo: isVideoItem,
        isDocument: isDocumentItem,
        badgeClass(source) {
            return SOURCE_BADGE[source] || 'bg-slate-600';
        },

        previewUrl(item) {
            return parseEvidenceItem(item).data;
        },

        thumbUrl(item) {
            const data = parseEvidenceItem(item?.items?.[0] || '').data;
            return data;
        },

        get filteredItems() {
            const q = this.searchTerm.trim().toLowerCase();
            return this.items.filter((item) => {
                const matchesSearch = !q
                    || item.title?.toLowerCase().includes(q)
                    || item.description?.toLowerCase().includes(q)
                    || item.submitted_by_name?.toLowerCase().includes(q);
                const matchesSource = this.filterSource === 'all' || item.source === this.filterSource;

                let matchesDate = true;
                if (this.filterDate && item.date) {
                    const d = new Date(item.date);
                    const f = new Date(this.filterDate);
                    matchesDate = d.getFullYear() === f.getFullYear()
                        && d.getMonth() === f.getMonth()
                        && d.getDate() === f.getDate();
                }

                let matchesType = true;
                if (this.filterType === 'image') {
                    matchesType = item.items?.some((i) => isImageItem(i));
                } else if (this.filterType === 'video') {
                    matchesType = item.items?.some((i) => isVideoItem(i));
                } else if (this.filterType === 'document') {
                    matchesType = item.items?.some((i) => isDocumentItem(i));
                }

                return matchesSearch && matchesSource && matchesDate && matchesType;
            });
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredItems.length / this.itemsPerPage));
        },

        get paginatedItems() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredItems.slice(start, start + this.itemsPerPage);
        },

        get hasActiveFilters() {
            return !!(this.searchTerm || this.filterSource !== 'all' || this.filterType !== 'all' || this.filterDate);
        },

        clearFilters() {
            this.searchTerm = '';
            this.filterSource = 'all';
            this.filterType = 'all';
            this.filterDate = '';
            this.currentPage = 1;
        },

        setPage(page) {
            this.currentPage = Math.min(Math.max(1, page), this.totalPages);
        },

        openDetail(item) {
            this.selectedEvidence = item;
        },

        closeDetail() {
            this.selectedEvidence = null;
            this.previewItem = null;
        },

        openPreview(item) {
            this.previewItem = item;
        },

        closePreview() {
            this.previewItem = null;
        },

        confirmDelete(item, event) {
            if (event) event.stopPropagation();
            this.deleteTarget = item;
        },

        async handleDelete() {
            if (!this.deleteTarget || this.deleting) return;
            const base = this.routes.destroy;
            if (!base) return;

            this.deleting = true;
            try {
                const res = await fetch(`${base}/${encodeURIComponent(this.deleteTarget.id)}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Không thể xóa');
                this.items = data.items || [];
                if (this.selectedEvidence?.id === this.deleteTarget.id) {
                    this.closeDetail();
                }
                this.deleteTarget = null;
            } catch (err) {
                alertMessage(err.message || 'Lỗi khi xóa minh chứng', getLanguage());
            } finally {
                this.deleting = false;
            }
        },

        mapsUrl(location) {
            if (!location?.latitude || !location?.longitude) return '#';
            return `https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}`;
        },
    }));
}
