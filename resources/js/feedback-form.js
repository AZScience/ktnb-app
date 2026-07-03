const PROOF_FILE_ACCEPT = 'image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt';

function isImageFile(file) {
    if (!file) return false;
    return file.type.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp)$/i.test(file.name);
}

function isVideoFile(file) {
    if (!file) return false;
    return file.type.startsWith('video/') || /\.(mp4|webm|mov|avi|mkv)$/i.test(file.name);
}

function isDocumentFile(file) {
    if (!file) return false;
    if (isImageFile(file) || isVideoFile(file)) return false;
    return /\.(pdf|docx?|xlsx?|pptx?|txt)$/i.test(file.name)
        || file.type.startsWith('application/')
        || file.type === 'text/plain';
}

export function registerFeedbackForm(Alpine) {
    Alpine.data('feedbackFormPage', (config) => ({
        email: config.email || '',
        employeeName: config.employeeName || '',
        shiftDate: config.shiftDate || new Date().toISOString().split('T')[0],
        proofFileAccept: PROOF_FILE_ACCEPT,
        files: {
            proof_printed: [],
            proof_online: [],
            proof_incident: [],
            proof_facility: [],
        },
        submitting: false,
        success: false,
        successMessage: '',
        sheetWarning: false,

        fields: [
            {
                key: 'proof_printed',
                label: 'Minh chứng tờ in kiểm tra',
                hint: 'Ảnh, video hoặc tài liệu (PDF, Word, Excel…) — tờ in/biên bản có chữ ký',
            },
            {
                key: 'proof_online',
                label: 'Minh chứng lớp trực tuyến',
                hint: 'Ảnh chụp màn hình, video ghi hình hoặc tài liệu liên quan giám sát online',
            },
            {
                key: 'proof_incident',
                label: 'Minh chứng ghi nhận không phù hợp',
                hint: 'Ảnh, video hoặc tài liệu về sự việc phát sinh trong ca trực',
            },
            {
                key: 'proof_facility',
                label: 'Minh chứng cơ sở vật chất',
                hint: 'Ảnh, video hoặc tài liệu về tình trạng CSVC sau ca trực',
            },
        ],

        addFiles(key, event) {
            const picked = Array.from(event.target.files || []);
            this.files[key] = [...this.files[key], ...picked];
            event.target.value = '';
        },

        removeFile(key, index) {
            const file = this.files[key][index];
            if (file?._previewUrl) {
                URL.revokeObjectURL(file._previewUrl);
                delete file._previewUrl;
            }
            this.files[key] = this.files[key].filter((_, i) => i !== index);
        },

        previewUrl(file) {
            if (!file || (!isImageFile(file) && !isVideoFile(file))) {
                return '';
            }
            if (!file._previewUrl) {
                file._previewUrl = URL.createObjectURL(file);
            }
            return file._previewUrl;
        },

        isImageFile,
        isVideoFile,
        isDocumentFile,

        clearAll() {
            if (!window.confirm('Xóa hết câu trả lời và file đã chọn?')) return;
            this.shiftDate = new Date().toISOString().split('T')[0];
            Object.keys(this.files).forEach((k) => { this.files[k] = []; });
        },

        async submitForm(event) {
            event.preventDefault();
            const hasFile = Object.values(this.files).some((list) => list.length > 0);
            if (!hasFile) {
                alert('Vui lòng đính kèm ít nhất một minh chứng.');
                return;
            }
            this.submitting = true;
            const form = event.target;
            const fd = new FormData(form);
            Object.entries(this.files).forEach(([key, list]) => {
                fd.delete(`${key}[]`);
                list.forEach((file) => fd.append(`${key}[]`, file));
            });
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Gửi thất bại');
                }
                const sheet = data.sheet || {};
                this.sheetWarning = sheet.success === false && sheet.skipped !== true;
                this.success = true;
                this.successMessage = data.message || '';
                this.clearAllSilent();
                setTimeout(() => {
                    this.success = false;
                    this.successMessage = '';
                    this.sheetWarning = false;
                }, this.sheetWarning ? 12000 : 5000);
            } catch (err) {
                alert(err.message || 'Gửi thất bại');
            } finally {
                this.submitting = false;
            }
        },

        clearAllSilent() {
            Object.keys(this.files).forEach((k) => { this.files[k] = []; });
        },
    }));
}
