function parseEvidenceItem(item) {
    if (!item) return { name: '', data: '' };
    if (item.includes(':::')) {
        const idx = item.indexOf(':::');
        return { name: item.slice(0, idx), data: item.slice(idx + 3) };
    }
    return { name: '', data: item };
}

function isImageItem(name, data) {
    const id = `${name} ${data}`.toLowerCase();
    return data.startsWith('data:image') || /\.(png|jpe?g|gif|webp|bmp|svg)(\?|$)/i.test(id);
}

function isVideoItem(name, data) {
    const id = `${name} ${data}`.toLowerCase();
    return data.startsWith('data:video') || /\.(mp4|webm|mov|avi|mkv)(\?|$)/i.test(id);
}

async function compressImageBlob(blob) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        const objectUrl = URL.createObjectURL(blob);
        img.onload = () => {
            const canvas = document.createElement('canvas');
            const maxSize = 1000;
            let { width, height } = img;
            if (width > height) {
                if (width > maxSize) {
                    height *= maxSize / width;
                    width = maxSize;
                }
            } else if (height > maxSize) {
                width *= maxSize / height;
                height = maxSize;
            }
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d')?.drawImage(img, 0, 0, width, height);
            URL.revokeObjectURL(objectUrl);
            resolve(canvas.toDataURL('image/jpeg', 0.6));
        };
        img.onerror = reject;
        img.src = objectUrl;
    });
}

export function evidencePanelState(uploadUrl = '') {
    return {
        evidenceUploadUrl: uploadUrl,
        evidenceTab: 'upload',
        evidenceMediaMode: 'photo',
        evidenceSourceType: 'camera',
        evidenceItems: [],
        evidenceStream: null,
        evidenceDevices: [],
        evidenceDeviceId: '',
        evidenceIsRecording: false,
        evidenceRecordingTime: 0,
        evidenceIsUploading: false,
        evidencePendingLink: '',
        evidenceEditingIndex: null,
        evidenceEditingName: '',
        evidencePreviewItem: null,
        evidenceRecorder: null,
        evidenceChunks: [],
        evidenceRecordingTimer: null,
        evidenceSyncing: false,
        evidenceFieldKey: 'evidence',
        evidencePanelExpanded: false,
    };
}

export function evidencePanelMethods() {
    return {
        evidenceParseItem: parseEvidenceItem,
        evidenceIsImage: isImageItem,
        evidenceIsVideo: isVideoItem,

        evidenceInitFromForm() {
            const key = this.evidenceFieldKey || 'evidence';
            const value = this.form?.[key] || '';
            const next = value ? value.split('|').filter(Boolean) : [];
            this.evidenceItems = next;
        },

        evidenceEmitChange() {
            if (this.evidenceSyncing) return;
            const key = this.evidenceFieldKey || 'evidence';
            const next = this.evidenceItems.join('|');
            if ((this.form?.[key] || '') !== next) {
                this.evidenceSyncing = true;
                this.form[key] = next;
                this.evidenceSyncing = false;
            }
        },

        evidenceSetItems(updater) {
            const next = typeof updater === 'function' ? updater([...this.evidenceItems]) : updater;
            this.evidenceItems = next;
            this.evidenceEmitChange();
        },

        evidenceAddItem(data, name) {
            const finalName = name || (data.startsWith('data:image') ? `Anh_${Date.now()}` : (data.startsWith('data:video') ? `Video_${Date.now()}` : `Tep_${Date.now()}`));
            this.evidenceSetItems((items) => [...items, `${finalName}:::${data}`]);
        },

        evidenceRemoveItem(index) {
            this.evidenceSetItems((items) => items.filter((_, i) => i !== index));
        },

        evidenceUpdateItemName(index, newName) {
            const { data } = parseEvidenceItem(this.evidenceItems[index]);
            const trimmed = newName.trim() || `Tep_${index + 1}`;
            this.evidenceSetItems((items) => items.map((item, i) => (i === index ? `${trimmed}:::${data}` : item)));
            this.evidenceEditingIndex = null;
        },

        evidenceFormatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        },

        evidenceIsFrontCamera() {
            if (!this.evidenceDeviceId || !this.evidenceDevices.length) return true;
            const device = this.evidenceDevices.find((d) => d.deviceId === this.evidenceDeviceId);
            if (!device?.label) return true;
            const label = device.label.toLowerCase();
            return label.includes('front') || label.includes('user') || label.includes('facing');
        },

        async evidenceListDevices() {
            try {
                const initial = await navigator.mediaDevices.getUserMedia({ video: true });
                initial.getTracks().forEach((t) => t.stop());
                const devs = await navigator.mediaDevices.enumerateDevices();
                const videoDevs = devs.filter((d) => d.kind === 'videoinput');
                this.evidenceDevices = videoDevs;
                if (videoDevs.length && !this.evidenceDeviceId) {
                    const back = videoDevs.find((d) => /back|rear/i.test(d.label));
                    this.evidenceDeviceId = back?.deviceId || videoDevs[0].deviceId;
                }
            } catch (_) {}
        },

        evidenceStopStream() {
            if (this.evidenceStream) {
                this.evidenceStream.getTracks().forEach((t) => t.stop());
                this.evidenceStream = null;
            }
            const video = this.$refs.evidenceVideo;
            if (video) video.srcObject = null;
        },

        async evidenceStartMedia(forceSource, forceDeviceId) {
            this.evidenceStopStream();
            const source = forceSource || this.evidenceSourceType;
            const deviceId = forceDeviceId || this.evidenceDeviceId;
            try {
                let stream;
                if (source === 'screen') {
                    stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
                } else {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: deviceId ? { deviceId: { exact: deviceId } } : true,
                        audio: this.evidenceMediaMode === 'video',
                    });
                }
                this.evidenceStream = stream;
                const video = this.$refs.evidenceVideo;
                if (video) video.srcObject = stream;
            } catch (err) {
                this.showToast?.('Không thể truy cập camera hoặc màn hình.', 'error');
            }
        },

        async evidenceUploadBlob(blob, fileName) {
            if (!this.evidenceUploadUrl) {
                throw new Error('Chưa cấu hình upload minh chứng.');
            }

            let uploadBlob = blob;
            let uploadName = fileName;
            if (blob.type.startsWith('image/')) {
                try {
                    const base64 = await compressImageBlob(blob);
                    const res = await fetch(base64);
                    uploadBlob = await res.blob();
                    if (!uploadName.match(/\.(jpe?g|png|webp)$/i)) {
                        uploadName = uploadName.replace(/\.[^.]+$/, '') + '.jpg';
                    }
                } catch (_) {}
            }
            const formData = new FormData();
            formData.append('file', uploadBlob, uploadName);
            const res = await fetch(this.evidenceUploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });
            const payload = await res.json();
            if (!res.ok) throw new Error(payload.message || 'Tải file thất bại');
            return payload.url || payload.item?.split(':::')[1] || '';
        },

        async evidenceCapturePhoto() {
            const video = this.$refs.evidenceVideo;
            const canvas = this.$refs.evidenceCanvas;
            if (!video || !canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.evidenceSourceType === 'camera' && this.evidenceIsFrontCamera()) {
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(video, 0, 0);
            this.evidenceIsUploading = true;
            try {
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
                if (!blob) throw new Error('Không tạo được ảnh chụp.');
                const fileName = `Anh_${Date.now()}.png`;
                const url = await this.evidenceUploadBlob(blob, fileName);
                this.evidenceAddItem(url, fileName);
                this.showToast?.('Đã chụp và tải ảnh lên hệ thống');
            } catch (err) {
                this.showToast?.(err.message || 'Lỗi chụp ảnh', 'error');
            } finally {
                this.evidenceIsUploading = false;
            }
        },

        evidenceStartRecording() {
            if (!this.evidenceStream) return;
            this.evidenceChunks = [];
            const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus')
                ? 'video/webm;codecs=vp9,opus'
                : 'video/webm';
            const recorder = new MediaRecorder(this.evidenceStream, { mimeType });
            recorder.ondataavailable = (e) => {
                if (e.data.size > 0) this.evidenceChunks.push(e.data);
            };
            recorder.onstop = async () => {
                const blob = new Blob(this.evidenceChunks, { type: mimeType });
                this.evidenceIsUploading = true;
                try {
                    const fileName = `Video_${Date.now()}.webm`;
                    const url = await this.evidenceUploadBlob(blob, fileName);
                    this.evidenceAddItem(url, fileName);
                    this.showToast?.('Đã lưu video và tải lên hệ thống');
                } catch (err) {
                    this.showToast?.(err.message || 'Lỗi lưu video', 'error');
                } finally {
                    this.evidenceIsUploading = false;
                }
            };
            recorder.start();
            this.evidenceRecorder = recorder;
            this.evidenceIsRecording = true;
            this.evidenceRecordingTime = 0;
            this.evidenceRecordingTimer = setInterval(() => {
                this.evidenceRecordingTime += 1;
            }, 1000);
        },

        evidenceStopRecording() {
            if (this.evidenceRecorder && this.evidenceIsRecording) {
                this.evidenceRecorder.stop();
                this.evidenceIsRecording = false;
            }
            if (this.evidenceRecordingTimer) {
                clearInterval(this.evidenceRecordingTimer);
                this.evidenceRecordingTimer = null;
            }
        },

        async evidenceHandleFiles(fileList) {
            const files = Array.from(fileList || []);
            if (!files.length) return;
            this.evidenceIsUploading = true;
            try {
                for (const file of files) {
                    const url = await this.evidenceUploadBlob(file, file.name);
                    this.evidenceAddItem(url, file.name);
                }
                this.showToast?.(`Đã tải ${files.length} tệp lên thành công`);
            } catch (err) {
                this.showToast?.(err.message || 'Lỗi tải tệp', 'error');
            } finally {
                this.evidenceIsUploading = false;
            }
        },

        evidenceNormalizeLink(raw) {
            const trimmed = String(raw || '').trim();
            if (!trimmed) return null;
            try {
                return new URL(trimmed).toString();
            } catch {
                try {
                    return new URL(`https://${trimmed}`).toString();
                } catch {
                    return null;
                }
            }
        },

        evidenceAddLink() {
            const normalized = this.evidenceNormalizeLink(this.evidencePendingLink);
            if (!normalized) {
                this.showToast?.('Liên kết không hợp lệ. Hãy nhập URL đầy đủ.', 'error');
                return;
            }
            this.evidenceAddItem(normalized, normalized.replace(/^https?:\/\//, ''));
            this.evidencePendingLink = '';
        },

        evidenceOpenPreview(item) {
            this.evidencePreviewItem = item;
        },

        evidenceClosePreview() {
            this.evidencePreviewItem = null;
        },

        evidenceCleanupPanel() {
            this.evidenceStopRecording();
            this.evidenceStopStream();
            this.evidenceClosePreview();
            this.evidenceTab = 'upload';
        },

        evidenceOnModalOpen() {
            const key = this.evidenceFieldKey || 'evidence';
            this.evidencePanelExpanded = Boolean(this.form?.[key]);
            this.evidenceInitFromForm();
        },
    };
}
