async function compressImageDataUrl(dataUrl, maxWidth = 1024, maxHeight = 1024, quality = 0.7) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;

            if (width > height) {
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
            } else if (height > maxHeight) {
                width = Math.round((width * maxHeight) / height);
                height = maxHeight;
            }

            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                reject(new Error('Không thể xử lý ảnh.'));
                return;
            }
            ctx.drawImage(img, 0, 0, width, height);
            resolve(canvas.toDataURL('image/jpeg', quality));
        };
        img.onerror = () => reject(new Error('Không thể đọc file ảnh.'));
        img.src = dataUrl;
    });
}

export function registerSystemParameters(Alpine) {
    Alpine.data('systemParametersPage', (config) => ({
        tab: 'interface',
        localParams: {},
        savedParams: {},
        saveUrl: config.saveUrl,
        verifyUrls: config.verifyUrls,
        lecturerPortalUrl: config.lecturerPortalUrl || '',
        canEdit: config.canEdit !== false,
        aiModelOptions: [
            { value: 'gemini-3.1-flash', label: 'Gemini 3.1 Flash (Mới nhất - Nhanh & Mạnh)' },
            { value: 'gemini-3.1-pro', label: 'Gemini 3.1 Pro (Mới nhất - Tư duy sâu)' },
            { value: 'gemini-3.0-flash', label: 'Gemini 3.0 Flash' },
            { value: 'gemini-2.5-flash', label: 'Gemini 2.5 Flash' },
            { value: 'gemini-2.0-flash', label: 'Gemini 2.0 Flash' },
            { value: 'gemini-1.5-flash', label: 'Gemini 1.5 Flash' },
            { value: 'gemini-1.5-flash-8b', label: 'Gemini 1.5 Flash-8b' },
            { value: 'gemini-1.5-pro', label: 'Gemini 1.5 Pro' },
        ],
        toast: null,
        isSaving: false,
        isTestingEmail: false,
        isVerifyingAi: false,
        isVerifyingGoogle: false,
        isVerifyingSummaryGoogle: false,
        isVerifyingEvidence: false,
        isVerifyingLecturerPortal: false,
        isUploadingImage: false,
        hasChanges: false,

        init() {
            const initial = this.normalizeParams(config.params || {});
            this.localParams = Alpine.reactive(initial);
            this.savedParams = JSON.parse(JSON.stringify(initial));
            this.$watch('localParams', () => {
                this.hasChanges = JSON.stringify(this.normalizeParams(this.localParams)) !== JSON.stringify(this.savedParams);
            }, { deep: true });

            window.addEventListener('beforeunload', (event) => {
                if (!this.hasChanges) return;
                event.preventDefault();
                event.returnValue = '';
            });
        },

        normalizeParams(params) {
            const normalized = {};
            Object.entries(params || {}).forEach(([key, value]) => {
                if (value === null || value === undefined || String(value).toLowerCase() === 'null') {
                    normalized[key] = '';
                } else {
                    normalized[key] = String(value);
                }
            });
            return normalized;
        },

        get isChanged() {
            return this.hasChanges;
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = null; }, 30000);
        },

        patchParam(key, value) {
            this.localParams[key] = value;
        },

        undo() {
            const restored = JSON.parse(JSON.stringify(this.savedParams));
            Object.keys(this.localParams).forEach((key) => delete this.localParams[key]);
            Object.assign(this.localParams, restored);
            this.showToast('Đã hoàn tác thay đổi');
        },

        async parseJsonResponse(response) {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch {
                if (response.status === 419) {
                    throw new Error('Phiên làm việc hết hạn. Vui lòng tải lại trang và thử lại.');
                }
                if (response.status === 413) {
                    throw new Error('Dữ liệu quá lớn. Hãy dùng ảnh nhỏ hơn hoặc dán URL thay vì base64.');
                }
                throw new Error(text?.slice(0, 200) || `Lỗi máy chủ (HTTP ${response.status}).`);
            }
        },

        async save() {
            if (!this.canEdit) return;
            if (!this.isChanged || this.isSaving) return;
            this.isSaving = true;
            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ params: this.normalizeParams(this.localParams) }),
                });
                const result = await this.parseJsonResponse(response);
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Không thể lưu tham số.');
                }
                const next = this.normalizeParams(result.params);
                Object.keys(this.localParams).forEach((key) => delete this.localParams[key]);
                Object.assign(this.localParams, next);
                this.savedParams = JSON.parse(JSON.stringify(next));
                this.hasChanges = false;
                this.showToast('Đã cập nhật tham số hệ thống.');
            } catch (error) {
                this.showToast(error.message || 'Lỗi lưu trữ.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        async postVerify(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            return this.parseJsonResponse(response);
        },

        async verifyGoogle() {
            if (this.isVerifyingGoogle) return;
            this.isVerifyingGoogle = true;
            try {
                const result = await this.postVerify(this.verifyUrls.googleSheet, {
                    googleSheetId: this.localParams.googleSheetId,
                    googleServiceAccountEmail: this.localParams.googleServiceAccountEmail,
                    googlePrivateKey: this.localParams.googlePrivateKey,
                    faqSheetTabName: this.localParams.faqSheetTabName,
                });
                this.showToast(result.message || 'Hoàn tất.', result.success ? 'success' : 'error');
            } catch (error) {
                this.showToast(error.message || 'Lỗi kết nối.', 'error');
            } finally {
                this.isVerifyingGoogle = false;
            }
        },

        async verifySummaryGoogle() {
            if (this.isVerifyingSummaryGoogle) return;
            this.isVerifyingSummaryGoogle = true;
            try {
                const result = await this.postVerify(this.verifyUrls.summaryGoogleSheet, {
                    summaryReportGoogleSheetId: this.localParams.summaryReportGoogleSheetId,
                    googleServiceAccountEmail: this.localParams.googleServiceAccountEmail,
                    googlePrivateKey: this.localParams.googlePrivateKey,
                });
                this.showToast(result.message || 'Hoàn tất.', result.success ? 'success' : 'error');
            } catch (error) {
                this.showToast(error.message || 'Lỗi kết nối.', 'error');
            } finally {
                this.isVerifyingSummaryGoogle = false;
            }
        },

        async verifyEvidence() {
            if (this.isVerifyingEvidence) return;
            this.isVerifyingEvidence = true;
            try {
                const result = await this.postVerify(this.verifyUrls.evidence, {
                    feedbackSheetId: this.localParams.feedbackSheetId,
                    googleSheetId: this.localParams.googleSheetId,
                    feedbackTabName: this.localParams.feedbackTabName,
                    googleDriveFolderId: this.localParams.googleDriveFolderId,
                    evidenceServiceAccountEmail: this.localParams.evidenceServiceAccountEmail,
                    googleServiceAccountEmail: this.localParams.googleServiceAccountEmail,
                    evidencePrivateKey: this.localParams.evidencePrivateKey,
                    googlePrivateKey: this.localParams.googlePrivateKey,
                });
                const type = result.success ? 'success' : 'error';
                const message = result.success
                    ? (result.message || 'Tất cả kết nối tốt!')
                    : (result.message || 'Kiểm tra thất bại.');
                this.showToast(message, type);
            } catch (error) {
                this.showToast(error.message || 'Lỗi kết nối.', 'error');
            } finally {
                this.isVerifyingEvidence = false;
            }
        },

        async verifyAi() {
            if (this.isVerifyingAi) return;
            if (!this.localParams.aiApiKey) {
                this.showToast('Vui lòng nhập API Key trước khi kiểm tra.', 'error');
                return;
            }
            this.isVerifyingAi = true;
            try {
                const result = await this.postVerify(this.verifyUrls.ai, {
                    aiApiKey: this.localParams.aiApiKey,
                    aiModel: this.localParams.aiModel,
                });
                this.showToast(result.message || 'Hoàn tất.', result.success ? 'success' : 'error');
            } catch (error) {
                this.showToast(error.message || 'Lỗi kết nối.', 'error');
            } finally {
                this.isVerifyingAi = false;
            }
        },

        async testEmail() {
            if (this.isTestingEmail) return;
            if (!this.localParams.smtpHost || !this.localParams.smtpUser || !this.localParams.smtpPass) {
                this.showToast('Vui lòng nhập đầy đủ Host, User và Pass.', 'error');
                return;
            }
            this.isTestingEmail = true;
            try {
                const result = await this.postVerify(this.verifyUrls.email, {
                    smtpHost: this.localParams.smtpHost,
                    smtpPort: this.localParams.smtpPort,
                    smtpUser: this.localParams.smtpUser,
                    smtpPass: this.localParams.smtpPass,
                    smtpFromName: this.localParams.smtpFromName,
                });
                this.showToast(result.message || 'Hoàn tất.', result.success ? 'success' : 'error');
            } catch (error) {
                this.showToast(error.message || 'Lỗi kết nối.', 'error');
            } finally {
                this.isTestingEmail = false;
            }
        },

        async handleImageUpload(event, key, maxWidth, maxHeight, quality) {
            const file = event.target.files?.[0];
            if (!file || this.isUploadingImage) return;
            this.isUploadingImage = true;
            const reader = new FileReader();
            reader.onload = async (loadEvent) => {
                try {
                    const compressed = await compressImageDataUrl(loadEvent.target?.result, maxWidth, maxHeight, quality);
                    this.patchParam(key, compressed);
                    const label = key === 'loginImageUrl' ? 'Ảnh nền mới' : 'Ảnh logo';
                    this.showToast(`Đã tải ${label}. Nhấn "Lưu tất cả thay đổi" để áp dụng.`);
                } catch (error) {
                    this.showToast(error.message || 'Không thể tối ưu hóa ảnh này.', 'error');
                } finally {
                    this.isUploadingImage = false;
                    event.target.value = '';
                }
            };
            reader.onerror = () => {
                this.showToast('Không thể đọc file ảnh.', 'error');
                this.isUploadingImage = false;
                event.target.value = '';
            };
            reader.readAsDataURL(file);
        },

        bannerPreviewHeight() {
            const height = parseInt(this.localParams.bannerHeight, 10);
            return Number.isFinite(height) && height > 0 ? height : 40;
        },

        hasBanner() {
            return !!(this.localParams.bannerUrl && String(this.localParams.bannerUrl).trim());
        },

        hasLoginImage() {
            return !!(this.localParams.loginImageUrl && String(this.localParams.loginImageUrl).trim());
        },

        hasGoogleClientId() {
            return !!(this.localParams.googleClientId && String(this.localParams.googleClientId).trim());
        },

        async verifyLecturerPortal() {
            if (this.isVerifyingLecturerPortal) return;
            this.isVerifyingLecturerPortal = true;
            try {
                const result = await this.postVerify(this.verifyUrls.lecturerPortal, {
                    googleClientId: this.localParams.googleClientId,
                    lecturerPortalEmailDomains: this.localParams.lecturerPortalEmailDomains,
                });
                this.showToast(result.message || 'Hoàn tất.', result.success ? 'success' : 'error');
            } catch (error) {
                this.showToast(error.message || 'Lỗi kết nối.', 'error');
            } finally {
                this.isVerifyingLecturerPortal = false;
            }
        },
    }));
}
