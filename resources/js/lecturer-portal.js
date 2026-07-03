import { evidencePanelMethods, evidencePanelState } from './evidence-input.js';

export function registerLecturerPortal(Alpine) {
    Alpine.data('lecturerPortalPage', (config) => ({
        searchDate: new Date().toISOString().split('T')[0],
        searchClass: '',
        classInfo: null,
        lat: '',
        lng: '',
        locationText: 'Chưa lấy GPS',
        locationLoading: false,
        locationError: '',
        locationDenied: false,
        openSection: 1,
        submitted: false,
        isSubmitting: false,
        searchUrl: config.searchUrl || '',
        authUrl: config.authUrl || '',
        submitUrl: config.submitUrl || '',
        googleClientId: config.googleClientId || '',
        googleUser: config.googleUser || null,
        appUrl: config.appUrl || '',
        csrfToken: config.csrfToken || '',
        authError: '',
        authLoading: false,
        loadingClass: false,
        googleInitialized: false,
        pageOrigin: '',
        isViewMode: false,
        form: { evidence: '' },
        toastMessage: '',
        toastType: 'success',

        ...evidencePanelState(config.evidenceUploadUrl || ''),

        init() {
            this.pageOrigin = window.location.origin;
            this.evidenceSourceType = 'camera';
            this.evidenceTab = 'camera';

            this.$watch('googleUser', (user) => {
                if (user) {
                    this.$nextTick(() => this.evidenceListDevices());
                } else if (this.googleClientId) {
                    this.$nextTick(() => this.initGoogleSignIn());
                }
            });

            if (this.googleUser) {
                this.$nextTick(() => this.evidenceListDevices());
            } else if (this.googleClientId) {
                this.$nextTick(() => this.initGoogleSignIn());
            }
        },

        get requiredOrigins() {
            const origins = new Set();
            const current = window.location.origin;
            if (current) origins.add(current);
            if (this.appUrl) origins.add(this.appUrl.replace(/\/$/, ''));
            try {
                const url = new URL(current || this.appUrl || 'http://127.0.0.1');
                const host = url.hostname;
                const port = url.port ? `:${url.port}` : '';
                const protocol = url.protocol;
                if (host === '127.0.0.1') {
                    origins.add(`${protocol}//localhost${port}`);
                }
                if (host === 'localhost') {
                    origins.add(`${protocol}//127.0.0.1${port}`);
                }
            } catch (_) {}
            return [...origins].filter(Boolean);
        },

        get mapEmbedUrl() {
            if (!this.lat || !this.lng) return '';
            return `https://www.openstreetmap.org/export/embed.html?bbox=${this.lng - 0.01}%2C${this.lat - 0.01}%2C${this.lng + 0.01}%2C${this.lat + 0.01}&layer=mapnik&marker=${this.lat}%2C${this.lng}`;
        },

        get evidenceCount() {
            return this.evidenceItems.length;
        },

        get canSubmit() {
            return this.googleUser && this.classInfo && this.lat && this.lng && this.evidenceCount > 0 && !this.isSubmitting;
        },

        get submitLabel() {
            if (this.isSubmitting) return 'Đang gửi...';
            const count = this.evidenceCount;
            return count > 0 ? `GỬI ${count} MINH CHỨNG` : 'GỬI CHECK-IN';
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            if (type === 'error') {
                alert(message);
            }
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => {
                this.toastMessage = '';
            }, 4000);
        },

        initGoogleSignIn() {
            if (this.googleInitialized || !this.googleClientId) return;

            const render = () => {
                const container = this.$refs.googleButton;
                if (!container || !window.google?.accounts?.id) return;

                window.google.accounts.id.initialize({
                    client_id: this.googleClientId,
                    callback: (response) => this.handleGoogleCredential(response),
                    auto_select: false,
                    cancel_on_tap_outside: true,
                });

                container.innerHTML = '';
                window.google.accounts.id.renderButton(container, {
                    type: 'standard',
                    theme: 'outline',
                    size: 'large',
                    text: 'signin_with',
                    shape: 'pill',
                    locale: 'vi',
                    width: 280,
                });

                this.googleInitialized = true;
            };

            if (window.google?.accounts?.id) {
                render();
            } else {
                const wait = setInterval(() => {
                    if (window.google?.accounts?.id) {
                        clearInterval(wait);
                        render();
                    }
                }, 200);
                setTimeout(() => clearInterval(wait), 15000);
            }
        },

        async handleGoogleCredential(response) {
            if (!response?.credential) return;

            this.authError = '';
            this.authLoading = true;

            try {
                const res = await fetch(this.authUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({ credential: response.credential }),
                });

                const json = await res.json();
                if (json.success && json.user) {
                    this.googleUser = json.user;
                    this.googleInitialized = false;
                } else {
                    this.authError = json.message || 'Không thể đăng nhập Google.';
                }
            } catch (_) {
                this.authError = 'Lỗi kết nối khi xác thực Google.';
            } finally {
                this.authLoading = false;
            }
        },

        async search() {
            if (!this.googleUser) return alert('Vui lòng đăng nhập Google trước.');
            if (!this.searchClass.trim()) return alert('Nhập mã lớp');

            this.loadingClass = true;
            try {
                const res = await fetch(`${this.searchUrl}?date=${this.searchDate}&class=${encodeURIComponent(this.searchClass)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (res.status === 401) {
                    this.googleUser = null;
                    alert('Phiên Google đã hết hạn. Vui lòng đăng nhập lại.');
                    return;
                }

                const json = await res.json();
                if (json.success) {
                    this.classInfo = json.data;
                    this.openSection = 2;
                } else {
                    alert(json.message || 'Không tìm thấy lớp');
                    this.classInfo = null;
                }
            } catch (_) {
                alert('Lỗi tra cứu lớp');
            } finally {
                this.loadingClass = false;
            }
        },

        getLocation() {
            this.locationError = '';
            this.locationDenied = false;

            if (!navigator.geolocation) {
                this.locationError = 'Trình duyệt không hỗ trợ định vị GPS.';
                return;
            }

            if (!window.isSecureContext) {
                this.locationError = 'GPS chỉ hoạt động trên HTTPS hoặc localhost. Vui lòng mở trang qua địa chỉ bảo mật.';
                return;
            }

            this.locationLoading = true;
            this.locationText = 'Đang lấy vị trí...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.lat = position.coords.latitude;
                    this.lng = position.coords.longitude;
                    this.locationText = `${this.lat.toFixed(5)}, ${this.lng.toFixed(5)}`;
                    this.locationError = '';
                    this.locationDenied = false;
                    this.locationLoading = false;
                    this.openSection = 4;
                },
                (error) => {
                    this.lat = '';
                    this.lng = '';
                    this.locationText = 'Chưa lấy GPS';
                    this.locationDenied = error?.code === 1;
                    this.locationError = this.geolocationErrorMessage(error);
                    this.locationLoading = false;
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
            );
        },

        geolocationErrorMessage(error) {
            switch (error?.code) {
                case 1:
                    return 'Bạn đã từ chối quyền vị trí. Hãy cho phép truy cập Vị trí trong trình duyệt rồi bấm "Thử lại" bên dưới.';
                case 2:
                    return 'Không xác định được vị trí. Bật GPS/định vị trên điện thoại hoặc máy tính và thử lại.';
                case 3:
                    return 'Hết thời gian chờ GPS. Ra ngoài trời hoặc gần cửa sổ rồi thử lại.';
                default:
                    if (/denied/i.test(error?.message || '')) {
                        return 'Bạn đã từ chối quyền vị trí. Hãy cho phép truy cập Vị trí trong trình duyệt rồi bấm "Thử lại".';
                    }

                    return error?.message || 'Không lấy được tọa độ GPS.';
            }
        },

        toggleSection(n) {
            this.openSection = this.openSection === n ? 0 : n;
        },

        onSubmitSuccess() {
            this.submitted = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        resetForm() {
            this.submitted = false;
            this.classInfo = null;
            this.searchClass = '';
            this.lat = '';
            this.lng = '';
            this.locationText = 'Chưa lấy GPS';
            this.locationError = '';
            this.locationDenied = false;
            this.locationLoading = false;
            this.openSection = 1;
            this.isSubmitting = false;
            this.form.evidence = '';
            this.evidenceItems = [];
            this.evidenceCleanupPanel();
        },

        buildSubmitPayload(form) {
            const data = new FormData(form);
            data.set('evidence', this.evidenceItems.join('|'));
            data.delete('photos[]');
            return data;
        },

        async submitCheckin(event) {
            event.preventDefault();
            if (!this.validateBeforeSubmit()) return;

            this.isSubmitting = true;
            const form = event.target;

            try {
                const res = await fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: this.buildSubmitPayload(form),
                });

                if (res.status === 401) {
                    this.googleUser = null;
                    alert('Phiên Google đã hết hạn. Vui lòng đăng nhập lại.');
                    return;
                }

                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(json.message || 'Gửi check-in thất bại.');
                }

                this.evidenceCleanupPanel();
                this.onSubmitSuccess();
            } catch (err) {
                this.showToast(err.message || 'Gửi check-in thất bại.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        validateBeforeSubmit() {
            if (!this.googleUser) {
                alert('Vui lòng đăng nhập Google trước');
                return false;
            }
            if (!this.classInfo) {
                alert('Vui lòng tìm và xác nhận lớp học trước');
                return false;
            }
            if (!this.lat || !this.lng) {
                this.openSection = 3;
                if (this.locationDenied) {
                    alert('Vui lòng cho phép quyền Vị trí (GPS) trong trình duyệt, sau đó bấm "Thử lại GPS".');
                } else {
                    alert('Vui lòng lấy tọa độ GPS');
                }
                return false;
            }
            if (!this.evidenceCount) {
                alert('Vui lòng cung cấp ít nhất 1 minh chứng');
                return false;
            }
            if (this.evidenceIsUploading) {
                alert('Đang tải minh chứng lên, vui lòng đợi...');
                return false;
            }
            return true;
        },

        ...evidencePanelMethods(),
    }));
}
