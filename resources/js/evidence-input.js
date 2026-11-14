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
        evidenceDeviceId: localStorage.getItem('nttu_evidence_device_id') || '',
        evidenceFacingMode: localStorage.getItem('nttu_evidence_facing_mode') || 'environment',
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
            if (!this.evidenceDeviceId || !this.evidenceDevices.length) {
                return (this.evidenceFacingMode || 'environment') === 'user';
            }
            const device = this.evidenceDevices.find((d) => d.deviceId === this.evidenceDeviceId);
            if (!device?.label) return (this.evidenceFacingMode || 'environment') === 'user';
            const label = device.label.toLowerCase();
            return label.includes('front') || label.includes('user') || label.includes('facing');
        },

        evidenceToggleCameraFacing() {
            if (this.evidenceDevices.length > 1) {
                const currentIndex = this.evidenceDevices.findIndex((d) => d.deviceId === this.evidenceDeviceId);
                const nextIndex = (currentIndex + 1) % this.evidenceDevices.length;
                this.evidenceDeviceId = this.evidenceDevices[nextIndex].deviceId;
                try {
                    localStorage.setItem('nttu_evidence_device_id', this.evidenceDeviceId);
                } catch (_) {}
                this.evidenceStartMedia('camera', this.evidenceDeviceId);
            } else {
                this.evidenceFacingMode = (this.evidenceFacingMode === 'user') ? 'environment' : 'user';
                try {
                    localStorage.setItem('nttu_evidence_facing_mode', this.evidenceFacingMode);
                } catch (_) {}
                this.evidenceDeviceId = '';
                try {
                    localStorage.removeItem('nttu_evidence_device_id');
                } catch (_) {}
                this.evidenceStartMedia('camera');
            }
            this.$nextTick(() => this.ensureCameraSelectorUI());
        },

        evidenceDeviceLabel(dev, index, total) {
            const raw = (dev?.label || '').toLowerCase();
            if (raw.includes('front') || raw.includes('user') || raw.includes('trước') || raw.includes('selfie')) {
                return '📷 Camera trước (Selfie)';
            }
            if (raw.includes('back') || raw.includes('rear') || raw.includes('sau') || raw.includes('environment')) {
                return '📷 Camera sau (Chính)';
            }
            if (index === 0 && total > 1) return dev?.label ? `📷 ${dev.label}` : '📷 Camera sau (1)';
            if (index === 1 && total > 1) return dev?.label ? `📷 ${dev.label}` : '📷 Camera trước (2)';
            return dev?.label ? `📷 ${dev.label}` : `📷 Camera ${index + 1}`;
        },

        async evidenceRefreshDevices() {
            try {
                if (!navigator.mediaDevices?.enumerateDevices) return;
                const devs = await navigator.mediaDevices.enumerateDevices();
                const videoDevs = devs.filter((d) => d.kind === 'videoinput');
                this.evidenceDevices = videoDevs;
                const currentTrack = this.evidenceStream?.getVideoTracks?.()?.[0];
                const activeId = currentTrack?.getSettings?.()?.deviceId;
                if (activeId) {
                    this.evidenceDeviceId = activeId;
                    try {
                        localStorage.setItem('nttu_evidence_device_id', activeId);
                    } catch (_) {}
                } else if (videoDevs.length && !this.evidenceDeviceId) {
                    this.evidenceDeviceId = videoDevs[0].deviceId;
                }
            } catch (_) {}
        },

        async evidenceListDevices() {
            try {
                const initial = await navigator.mediaDevices.getUserMedia({ video: true });
                initial.getTracks().forEach((t) => t.stop());
                await this.evidenceRefreshDevices();
            } catch (_) {}
        },

        evidenceStopStream() {
            if (this.evidenceStream) {
                this.evidenceStream.getTracks().forEach((t) => t.stop());
                this.evidenceStream = null;
            }
            const video = this.$refs.evidenceVideo || document.querySelector('.aspect-video video');
            if (video) video.srcObject = null;
        },

        async evidenceStartMedia(forceSource, forceDeviceId) {
            this.evidenceStopStream();
            const source = forceSource || this.evidenceSourceType;
            const deviceId = forceDeviceId !== undefined ? forceDeviceId : this.evidenceDeviceId;
            if (deviceId) {
                try {
                    localStorage.setItem('nttu_evidence_device_id', deviceId);
                } catch (_) {}
            }
            try {
                let stream;
                if (source === 'screen') {
                    stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
                } else {
                    const videoConstraints = deviceId
                        ? { deviceId: { ideal: deviceId } }
                        : { facingMode: { ideal: this.evidenceFacingMode || 'environment' } };
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: videoConstraints,
                            audio: this.evidenceMediaMode === 'video',
                        });
                    } catch (e1) {
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: true,
                                audio: this.evidenceMediaMode === 'video',
                            });
                        } catch (e2) {
                            stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        }
                    }
                }
                this.evidenceStream = stream;
                const video = this.$refs.evidenceVideo || document.querySelector('.aspect-video video');
                if (video) video.srcObject = stream;

                await this.evidenceRefreshDevices();
                this.$nextTick(() => {
                    this.ensureCameraSelectorUI();
                });
            } catch (err) {
                console.error('[Evidence] Error starting media:', err);
                this.showToast?.('Không thể truy cập camera hoặc màn hình.', 'error');
            }
        },

        ensureCameraSelectorUI() {
            document.querySelectorAll('.btn-toggle-cam').forEach((el) => el.remove());
            document.querySelectorAll('.evidence-camera-select-box').forEach((el) => el.remove());

            const aspectVideos = document.querySelectorAll('.aspect-video');
            aspectVideos.forEach((aspectVideo) => {
                // Ensure floating toggle button overlay on video stream container
                if (!aspectVideo.querySelector('.evidence-floating-cam-btn')) {
                    const overlay = document.createElement('button');
                    overlay.type = 'button';
                    overlay.className = 'evidence-floating-cam-btn absolute right-3 top-3 z-30 inline-flex items-center gap-1.5 rounded-full bg-black/60 hover:bg-black/80 text-white text-xs px-3 py-1.5 backdrop-blur-md shadow-md border border-white/20 transition-all cursor-pointer';
                    overlay.innerHTML = `
                        <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Trước / Sau</span>
                    `;
                    overlay.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.evidenceToggleCameraFacing();
                    };
                    aspectVideo.appendChild(overlay);
                }
            });
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
