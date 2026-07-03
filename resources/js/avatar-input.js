const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
const AVATAR_MAX_SIZE = 512;

function compressImageToDataUrl(img) {
    const canvas = document.createElement('canvas');
    const ratio = img.width / img.height;

    if (ratio > 1) {
        canvas.width = AVATAR_MAX_SIZE;
        canvas.height = AVATAR_MAX_SIZE / ratio;
    } else {
        canvas.width = AVATAR_MAX_SIZE * ratio;
        canvas.height = AVATAR_MAX_SIZE;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return null;
    }

    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', 0.7);
}

function loadImageFromFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = reader.result;
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

export function registerAvatarInput(Alpine) {
    const findCatalogPage = (el) => {
        let node = el?.parentElement;
        while (node) {
            const data = Alpine.$data(node);
            if (data?.form && (data?.routes !== undefined || data?.isProfilePage)) {
                return data;
            }
            node = node.parentElement;
        }
        return null;
    };

    Alpine.data('avatarInput', (fieldKey, label = 'Hình đại diện') => ({
        fieldKey,
        label,
        tab: 'url',
        stream: null,
        sourceType: 'camera',
        devices: [],
        selectedDeviceId: '',
        urlDraft: '',
        message: null,

        catalogPage() {
            return findCatalogPage(this.$el);
        },

        get disabled() {
            return this.catalogPage()?.dialogMode === 'view';
        },

        get preview() {
            return this.catalogPage()?.form?.[this.fieldKey] || '';
        },

        init() {
            this.$watch(() => this.catalogPage()?.modalOpen, (open) => {
                if (!open) {
                    this.destroy();
                    return;
                }
                this.tab = 'url';
                this.message = null;
                this.syncUrlDraft();
            });

            this.$watch(() => this.catalogPage()?.form?.[this.fieldKey], () => {
                this.syncUrlDraft();
            });

            this.syncUrlDraft();
        },

        syncUrlDraft() {
            const value = this.catalogPage()?.form?.[this.fieldKey] || '';
            this.urlDraft = value.startsWith('data:') ? '' : value;
        },

        setValue(value) {
            const catalog = this.catalogPage();
            if (catalog?.form) {
                catalog.form[this.fieldKey] = value;
            }
        },

        clearAvatar() {
            this.setValue('');
            this.urlDraft = '';
            this.message = null;
        },

        showMessage(text, type = 'success') {
            this.message = { text, type };
            setTimeout(() => {
                if (this.message?.text === text) {
                    this.message = null;
                }
            }, 2500);
        },

        checkUrl() {
            const url = (this.urlDraft || '').trim();
            if (!url) {
                this.showMessage('Vui lòng nhập đường dẫn hình ảnh.', 'error');
                return;
            }
            this.setValue(url);
            this.showMessage('Đã nhận URL');
        },

        onUrlInput(event) {
            this.urlDraft = event.target.value;
            this.setValue(this.urlDraft);
        },

        async onFileSelected(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                this.showMessage('Vui lòng chọn tệp hình ảnh.', 'error');
                return;
            }

            if (file.size > MAX_UPLOAD_BYTES) {
                this.showMessage('Tệp quá lớn. Vui lòng chọn ảnh dưới 5MB.', 'error');
                return;
            }

            try {
                const img = await loadImageFromFile(file);
                const dataUrl = compressImageToDataUrl(img);
                if (!dataUrl) {
                    throw new Error('Không xử lý được ảnh');
                }
                this.setValue(dataUrl);
                this.urlDraft = '';
                this.showMessage('Đã tải ảnh lên');
            } catch {
                this.showMessage('Không đọc được tệp ảnh.', 'error');
            }
        },

        async refreshDevices() {
            try {
                if (this.tab === 'capture' && !this.disabled && !this.stream) {
                    const initial = await navigator.mediaDevices.getUserMedia({ video: true });
                    initial.getTracks().forEach((track) => track.stop());
                }

                const devs = await navigator.mediaDevices.enumerateDevices();
                this.devices = devs.filter((device) => device.kind === 'videoinput');

                if (this.devices.length > 0 && !this.selectedDeviceId) {
                    const backCam = this.devices.find((device) => {
                        const name = device.label.toLowerCase();
                        return name.includes('back') || name.includes('rear');
                    });
                    this.selectedDeviceId = backCam ? backCam.deviceId : this.devices[0].deviceId;
                }
            } catch {
                // Browser may block device listing until permission is granted.
            }
        },

        get isFrontCamera() {
            if (!this.selectedDeviceId || this.devices.length === 0) {
                return true;
            }
            const device = this.devices.find((item) => item.deviceId === this.selectedDeviceId);
            if (!device) {
                return true;
            }
            const name = device.label.toLowerCase();
            return name.includes('front') || name.includes('user') || name.includes('facing');
        },

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }
            const video = this.$refs.video;
            if (video) {
                video.srcObject = null;
            }
        },

        async startStream(forceSource, forceDeviceId) {
            this.stopStream();
            const activeSource = forceSource || this.sourceType;
            const activeDeviceId = forceDeviceId || this.selectedDeviceId;

            try {
                let newStream;
                if (activeSource === 'screen') {
                    newStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                } else {
                    newStream = await navigator.mediaDevices.getUserMedia({
                        video: activeDeviceId ? { deviceId: { exact: activeDeviceId } } : true,
                    });
                }

                this.stream = newStream;
                const video = this.$refs.video;
                if (video) {
                    video.srcObject = newStream;
                }
            } catch (error) {
                let text = 'Không thể truy cập thiết bị. Vui lòng kiểm tra quyền truy cập.';
                if (error?.name === 'NotReadableError') {
                    text = 'Camera đang bị ứng dụng khác sử dụng. Hãy tắt chúng và thử lại.';
                } else if (error?.name === 'AbortError') {
                    text = 'Lỗi khởi động camera (Timeout). Hãy thử lại.';
                }
                this.showMessage(text, 'error');
            }
        },

        async switchTab(nextTab) {
            this.tab = nextTab;
            this.stopStream();
            if (nextTab === 'capture') {
                await this.refreshDevices();
            }
        },

        async setSourceType(type) {
            this.sourceType = type;
            this.stopStream();
        },

        async changeDevice(event) {
            this.selectedDeviceId = event.target.value;
            if (this.stream) {
                await this.startStream('camera', this.selectedDeviceId);
            }
        },

        capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            if (!video || !canvas) {
                return;
            }

            const context = canvas.getContext('2d');
            if (!context) {
                return;
            }

            const ratio = video.videoWidth / video.videoHeight;
            if (ratio > 1) {
                canvas.width = AVATAR_MAX_SIZE;
                canvas.height = AVATAR_MAX_SIZE / ratio;
            } else {
                canvas.width = AVATAR_MAX_SIZE * ratio;
                canvas.height = AVATAR_MAX_SIZE;
            }

            context.clearRect(0, 0, canvas.width, canvas.height);
            if (this.sourceType === 'camera' && this.isFrontCamera) {
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
            }

            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
            this.setValue(dataUrl);
            this.urlDraft = '';
            this.showMessage('Đã cập nhật ảnh đại diện');
            this.stopStream();
        },

        destroy() {
            this.stopStream();
        },
    }));
}
