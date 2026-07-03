import { alertMessage, getLanguage } from './language.js';

function normalizeDateInput(value) {
    if (!value) return '';
    if (String(value).includes('/')) {
        const [d, m, y] = String(value).split('/');
        if (d && m && y) {
            return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        }
    }
    return String(value);
}

function formatDateDisplay(value) {
    if (!value) return '';
    if (String(value).includes('-') && String(value).length === 10) {
        const [y, m, d] = String(value).split('-');
        return `${d}/${m}/${y}`;
    }
    return String(value);
}

function generateDocCode(type) {
    if (!type) return '';
    const now = new Date();
    const dateStr = `${String(now.getDate()).padStart(2, '0')}${String(now.getMonth() + 1).padStart(2, '0')}${now.getFullYear()}`;
    const prefix = type
        .split(' ')
        .map((word) => word[0] || '')
        .join('')
        .toUpperCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
    return `${prefix}${dateStr}001`;
}

export function documentRecordFormState(config) {
    return {
        drUploading: false,
        drUploadProgress: 0,
        drExtracting: false,
        drDepartmentOpen: false,
        drAssigneeOpen: false,
        docTypeOptions: config.docTypeOptions || [],
        departmentOptions: config.departmentOptions || [],
        employeeOptions: config.employeeOptions || [],
        urgencyOptions: config.urgencyOptions || ['Thường', 'Khẩn', 'Hỏa tốc'],
        confidentialityOptions: config.confidentialityOptions || ['Thường', 'Mật', 'Tối mật'],
        statusOptions: config.documentStatusOptions || config.statusOptions || ['Mới', 'Chờ duyệt', 'Đã duyệt', 'Cần bổ sung', 'Ban hành'],
        drUploadUrl: config.evidenceUploadUrl || '',
        drExtractUrl: config.routes?.extract || '',
    };
}

export function documentRecordFormMethods() {
    return {
        drPatchForm(patch) {
            this.form = { ...this.form, ...patch };
        },

        drModalTitle() {
            if (this.dialogMode === 'view') return 'Chi tiết hồ sơ';
            if (this.dialogMode === 'add') return 'Thêm hồ sơ mới';
            return 'Chỉnh sửa hồ sơ';
        },

        drOnModalOpen() {
            this.form.issue_date = normalizeDateInput(this.form.issue_date);
            this.form.received_date = normalizeDateInput(this.form.received_date);
            this.drDepartmentOpen = false;
            this.drAssigneeOpen = false;
        },

        drOnModalClose() {
            this.drUploading = false;
            this.drUploadProgress = 0;
            this.drExtracting = false;
        },

        drOnDocTypeChange() {
            if (this.dialogMode === 'add' && this.form.doc_type) {
                this.drPatchForm({ doc_code: generateDocCode(this.form.doc_type) });
            }
        },

        drSourceFileName() {
            const raw = String(this.form.original_file || '').trim();
            if (!raw) return '';
            if (raw.includes(':::')) return raw.split(':::')[0];
            try {
                return decodeURIComponent(raw.split('/').pop() || 'document');
            } catch {
                return raw.split('/').pop() || 'document';
            }
        },

        drMaybeTriggerAiFromSource() {
            if (this.isViewMode || this.drExtracting || this.drUploading) return;
            const raw = String(this.form.original_file || '').trim();
            if (!raw) return;
            const name = this.drSourceFileName() || 'document';
            this.drTriggerAiExtraction(name);
        },

        drShouldShowField(field) {
            const type = this.form.doc_type || '';
            const always = ['doc_type', 'title', 'abstract', 'original_file', 'status', 'ai_summary', 'extracted_text'];
            if (always.includes(field)) return true;
            if (field === 'doc_code') return false;
            if (type.includes('Công văn')) return field !== 'confidentiality';
            if (type.includes('Quyết định') || type.includes('Chỉ thị')) {
                return !['received_date', 'issuing_body', 'department'].includes(field);
            }
            if (type.includes('Thông báo') || type.includes('Lời mời')) {
                return !['doc_number', 'signer', 'confidentiality', 'issuing_body'].includes(field);
            }
            if (type.includes('Báo cáo') || type.includes('Tờ trình')) {
                return !['doc_number', 'issuing_body', 'signer', 'urgency', 'confidentiality'].includes(field);
            }
            return true;
        },

        drShowFilePassword() {
            return ['Mật', 'Tối mật'].includes(this.form.confidentiality || '');
        },

        drStatusClass() {
            const map = {
                'Mới': 'border-blue-300 bg-blue-50 text-blue-800',
                'Chờ duyệt': 'border-amber-300 bg-amber-50 text-amber-800',
                'Đã duyệt': 'border-emerald-300 bg-emerald-50 text-emerald-800',
                'Cần bổ sung': 'border-orange-300 bg-orange-50 text-orange-800',
                'Ban hành': 'border-slate-400 bg-slate-100 text-slate-800',
            };
            return map[this.form.status] || 'border-slate-300 bg-white text-slate-800';
        },

        drSelectedDepartments() {
            return String(this.form.department || '').split(',').map((s) => s.trim()).filter(Boolean);
        },

        drSelectedAssignees() {
            return String(this.form.assignee || '').split(',').map((s) => s.trim()).filter(Boolean);
        },

        drToggleDepartment(name) {
            if (this.isViewMode) return;
            const selected = this.drSelectedDepartments();
            const next = selected.includes(name)
                ? selected.filter((v) => v !== name)
                : [...selected, name];
            this.drPatchForm({ department: next.join(',') });
        },

        drToggleAssignee(name) {
            if (this.isViewMode) return;
            const selected = this.drSelectedAssignees();
            const next = selected.includes(name)
                ? selected.filter((v) => v !== name)
                : [...selected, name];
            this.drPatchForm({ assignee: next.join(',') });
        },

        drOriginalFileUrl() {
            const raw = this.form.original_file || '';
            if (!raw) return '';
            if (raw.includes(':::')) return raw.split(':::').pop();
            return raw;
        },

        drOriginalFileName() {
            const raw = this.form.original_file || '';
            if (!raw) return '';
            if (raw.includes(':::')) return raw.split(':::')[0];
            try {
                return decodeURIComponent(raw.split('/').pop() || 'Tệp đính kèm');
            } catch {
                return 'Tệp đính kèm';
            }
        },

        async drUploadFile(event) {
            const file = event.target.files?.[0];
            if (!file || this.isViewMode) return;

            const allowed = ['pdf', 'docx', 'doc', 'xlsx', 'txt', 'png', 'jpg', 'jpeg', 'zip', 'rar'];
            const ext = file.name.split('.').pop()?.toLowerCase() || '';
            if (!allowed.includes(ext)) {
                this.showToast?.('Hỗ trợ: PDF, DOCX, XLSX, TXT, PNG, JPG, ZIP, RAR', 'error');
                return;
            }
            if (file.size > 20 * 1024 * 1024) {
                this.showToast?.('Tệp quá lớn. Vui lòng chọn tệp dưới 20MB.', 'error');
                return;
            }
            if (!this.form.doc_type) {
                this.showToast?.('Vui lòng chọn Loại văn bản trước khi tải file.', 'error');
                return;
            }

            this.drUploading = true;
            this.drUploadProgress = 10;
            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(this.drUploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Tải file thất bại');

                this.drPatchForm({
                    original_file: data.item || `${data.name}:::${data.url}`,
                });
                this.drUploadProgress = 100;
                this.showToast?.(`Đã tải lên: ${data.name || file.name}`);
                await this.drTriggerAiExtraction(file.name);
            } catch (e) {
                this.showToast?.(e.message, 'error');
            } finally {
                this.drUploading = false;
                this.drUploadProgress = 0;
                event.target.value = '';
            }
        },

        async drTriggerAiExtraction(fileName) {
            if (this.drExtracting) return;

            const raw = String(this.form.original_file || '').trim();
            if (!raw) return;

            if (!this.drExtractUrl) {
                this.showToast?.('Chưa cấu hình API trích xuất văn bản.', 'error');
                return;
            }

            this.drExtracting = true;
            this.showToast?.('AI đang phân tích nội dung văn bản để trích xuất thông tin...');

            try {
                const res = await fetch(this.drExtractUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        original_file: raw,
                        doc_type: this.form.doc_type || '',
                        file_name: fileName || this.drSourceFileName() || '',
                    }),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Trích xuất văn bản thất bại.');
                }

                const patch = {};
                if (data.doc_number) patch.doc_number = data.doc_number;
                if (data.title) patch.title = data.title;
                if (data.ai_summary) patch.ai_summary = data.ai_summary;
                if (data.extracted_text) patch.extracted_text = data.extracted_text;
                if (data.abstract && !this.form.abstract) patch.abstract = data.abstract;

                this.drPatchForm(patch);
                this.showToast?.(data.message || 'Đã tự động điền Số hiệu, Tiêu đề và dữ liệu AI.');
                await this.drPersistExtractedFields?.();
            } catch (error) {
                this.showToast?.(error.message || 'Trích xuất văn bản thất bại.', 'error');
            } finally {
                this.drExtracting = false;
            }
        },

        drViewFile() {
            const url = this.drOriginalFileUrl();
            if (!url) return;
            if (this.isViewMode && this.drShowFilePassword() && this.form.file_password) {
                const pwd = window.prompt('Tài liệu bảo mật. Vui lòng nhập mật khẩu:');
                if (pwd !== this.form.file_password) {
                    alertMessage('Mật khẩu không đúng!', getLanguage());
                    return;
                }
            }
            window.open(url, '_blank');
        },

        drClearFile() {
            if (this.isViewMode) return;
            this.drPatchForm({ original_file: '' });
        },

        drPrepareSave() {
            if (this.form.issue_date) {
                this.form.issue_date = formatDateDisplay(this.form.issue_date);
            }
            if (this.form.received_date) {
                this.form.received_date = formatDateDisplay(this.form.received_date);
            }
        },

        async drPersistExtractedFields() {
            if (!this.selectedItem?.id || this.isViewMode || !this.routes?.update) {
                return;
            }

            this.drPrepareSave?.();
            const payload = { ...this.form };

            try {
                const res = await fetch(this.routes.update.replace('__ID__', this.selectedItem.id), {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    return;
                }

                const idx = this.items.findIndex((row) => row.id === this.selectedItem.id);
                if (idx >= 0 && data.item) {
                    this.items[idx] = data.item;
                }
                if (data.item) {
                    this.selectedItem = data.item;
                }
                this.initialForm = { ...this.form };
            } catch (_) {
                // Giữ dữ liệu trên form; người dùng vẫn có thể lưu thủ công.
            }
        },

        drCanSave() {
            return this.isChanged && this.form.title && !this.drUploading && !this.drExtracting;
        },
    };
}

export function applyDocumentRecordFormDefaults(mode, data, config) {
    if (config.formMode !== 'document-record') {
        return data;
    }

    if (mode === 'add') {
        const year = new Date().getFullYear();
        const count = (config.items || []).length;
        data.doc_code = data.doc_code || `CV-${year}-${String(count + 1).padStart(3, '0')}`;
        data.received_date = data.received_date || new Date().toISOString().slice(0, 10);
        data.urgency = data.urgency || 'Thường';
        data.confidentiality = data.confidentiality || 'Thường';
        data.status = data.status || 'Mới';
        data.title = data.title || '';
        data.abstract = data.abstract || '';
        data.ai_summary = data.ai_summary || '';
        data.extracted_text = data.extracted_text || '';
    }

    return data;
}
