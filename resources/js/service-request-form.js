function formatDmY(date = new Date()) {
    const d = String(date.getDate()).padStart(2, '0');
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const y = date.getFullYear();
    return `${d}/${m}/${y}`;
}

function parseDmYParts(value) {
    if (!value || !String(value).includes('/')) {
        const now = new Date();
        return {
            day: String(now.getDate()).padStart(2, '0'),
            month: String(now.getMonth() + 1).padStart(2, '0'),
            year: String(now.getFullYear()),
        };
    }

    const [day, month, year] = String(value).split('/');
    return { day: day || '', month: month || '', year: year || '' };
}

function composeDmY(day, month, year) {
    if (!day || !month || !year) {
        return '';
    }

    return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`;
}

function isTruthyFlag(value) {
    return value === true || value === 1 || value === '1' || value === 'true';
}

export function serviceRequestResolutionCode(item) {
    if (!item) {
        return '';
    }
    if (isTruthyFlag(item.is_processed_immediately)) {
        return 'immediate';
    }
    if (item.appointment_date && String(item.appointment_date).trim()) {
        return 'appointment';
    }
    if (item.note && String(item.note).trim()) {
        return 'other';
    }
    return '';
}

export function serviceRequestResolutionLabel(item) {
    const code = serviceRequestResolutionCode(item);
    if (code === 'immediate') {
        return 'Hỗ trợ ngay';
    }
    if (code === 'appointment') {
        return 'Hẹn ngày trả lời';
    }
    if (code === 'other') {
        return 'Lý do khác';
    }
    return '---';
}

export function serviceRequestFormState() {
    return {
        srSections: { info: true, resolution: true, feedback: true },
        srShowEvidence: false,
        srAppointmentEnabled: false,
        srOtherNoteEnabled: false,
        srIsExtractingAi: false,
        srCameraOpen: false,
        srCameraStream: null,
        srCameras: [],
        srSelectedCameraId: "",
    };
}

export function serviceRequestFormMethods() {
    return {
        async srExtractAi(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.srIsExtractingAi = true;
            try {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                const base64 = await new Promise((resolve, reject) => {
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = error => reject(error);
                });
                await this.srProcessExtraction(base64);
            } catch (error) {
                console.error(error);
                this.nttuShowErrorMessage?.('Lỗi trích xuất: ' + error.message);
            } finally {
                this.srIsExtractingAi = false;
                event.target.value = '';
            }
        },

        async srProcessExtraction(base64) {
            this.srIsExtractingAi = true;
            try {
                const response = await fetch('/ai/extract/service-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ image: base64 }),
                });

                if (!response.ok) throw new Error('Không thể trích xuất (Lỗi HTTP ' + response.status + ')');
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                if (data.ticket_number) this.form.ticket_number = data.ticket_number;
                if (data.request_type) {
                    this.form.request_type = data.request_type;
                    this.srEnsureRequestType(data.request_type);
                }
                if (data.student_name) this.form.student_name = data.student_name;
                if (data.student_id) this.form.student_id = data.student_id;
                if (data.class) this.form.class = data.class;
                if (data.department) {
                    this.form.department = data.department;
                    this.srEnsureDepartment(data.department);
                }
                if (data.phone) this.form.phone = data.phone;
                if (data.content) this.form.content = data.content;
                
                if (data.is_processed_immediately !== undefined) {
                    this.form.is_processed_immediately = Boolean(data.is_processed_immediately);
                }
                
                if (data.appointment_date) {
                    this.form.appointment_date = data.appointment_date;
                    this.srAppointmentEnabled = true;
                }
                
                if (data.note) {
                    this.form.note = data.note;
                    this.srOtherNoteEnabled = true;
                }

                if (data.reception_day) this.form.reception_day = String(data.reception_day).padStart(2, '0');
                if (data.reception_month) this.form.reception_month = String(data.reception_month).padStart(2, '0');
                if (data.reception_year) this.form.reception_year = String(data.reception_year);
                
                if (data.resolution_day) this.form.resolution_day = String(data.resolution_day).padStart(2, '0');
                if (data.resolution_month) this.form.resolution_month = String(data.resolution_month).padStart(2, '0');
                if (data.resolution_year) this.form.resolution_year = String(data.resolution_year);

                this.srSyncReceptionDate();
                this.srSyncResolutionDate();
                this.nttuShowSuccessMessage?.('Trích xuất thông tin thành công!');
            } catch (error) {
                throw error;
            } finally {
                this.srIsExtractingAi = false;
            }
        },

        async srOpenCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.nttuShowErrorMessage?.('Trình duyệt không hỗ trợ mở camera trực tiếp.');
                return;
            }
            this.srCameraOpen = true;
            await this.srEnumerateCameras();
            await this.srStartCameraStream(this.srSelectedCameraId);
        },
        
        async srEnumerateCameras() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(t => t.stop());
                
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.srCameras = devices.filter(d => d.kind === 'videoinput');
                if (this.srCameras.length > 0 && !this.srSelectedCameraId) {
                    const backCamera = this.srCameras.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('sau'));
                    this.srSelectedCameraId = backCamera ? backCamera.deviceId : this.srCameras[0].deviceId;
                }
            } catch (e) {
                console.error(e);
            }
        },
        
        async srSwitchCamera() {
            if (!this.srSelectedCameraId) return;
            await this.srStartCameraStream(this.srSelectedCameraId);
        },

        async srStartCameraStream(deviceId) {
            if (this.srCameraStream) {
                this.srCameraStream.getTracks().forEach(t => t.stop());
            }
            try {
                const constraints = deviceId ? { video: { deviceId: { exact: deviceId } } } : { video: { facingMode: 'environment' } };
                this.srCameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (this.$refs.srVideo) {
                    this.$refs.srVideo.srcObject = this.srCameraStream;
                }
            } catch (e) {
                console.error(e);
                this.nttuShowErrorMessage?.('Không thể mở camera được chọn.');
            }
        },

        srCloseCamera() {
            this.srCameraOpen = false;
            if (this.srCameraStream) {
                this.srCameraStream.getTracks().forEach(t => t.stop());
                this.srCameraStream = null;
            }
            if (this.$refs.srVideo) {
                this.$refs.srVideo.srcObject = null;
            }
        },

        async srCapturePhoto() {
            const video = this.$refs.srVideo;
            const canvas = this.$refs.srCanvas;
            if (!video || !canvas || video.videoWidth === 0) return;
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            const base64 = canvas.toDataURL('image/jpeg', 0.8);
            
            this.srCloseCamera();
            
            try {
                await this.srProcessExtraction(base64);
            } catch (error) {
                console.error(error);
                this.nttuShowErrorMessage?.('Lỗi trích xuất: ' + error.message);
            }
        },

        srOnModalOpen() {
            const reception = parseDmYParts(this.form.reception_date);
            this.form.reception_day = reception.day;
            this.form.reception_month = reception.month;
            this.form.reception_year = reception.year;

            const resolution = parseDmYParts(this.form.resolution_date);
            this.form.resolution_day = resolution.day;
            this.form.resolution_month = resolution.month;
            this.form.resolution_year = resolution.year;

            this.srShowEvidence = Boolean(this.form.evidence);
            this.srAppointmentEnabled = Boolean(this.form.appointment_date);
            this.srOtherNoteEnabled = Boolean(this.form.note && String(this.form.note).trim());
            this.srSections = { info: true, resolution: true, feedback: true };
            if (!this.form.request_type || !String(this.form.request_type).trim()) {
                this.form.request_type = 'Phiếu yêu cầu hỗ trợ';
            }
            if (this.form.request_type) {
                this.srEnsureRequestType(this.form.request_type);
            }
            if (this.form.department) {
                this.srEnsureDepartment(this.form.department);
            }
            if (this.form.building_block) {
                this.srEnsureBuildingBlock(this.form.building_block);
            }

            this.$nextTick(() => this.evidenceOnModalOpen?.());
        },

        srOnModalClose() {
            this.srShowEvidence = false;
            this.evidenceCleanupPanel?.();
        },

        srSyncReceptionDate() {
            const composed = composeDmY(this.form.reception_day, this.form.reception_month, this.form.reception_year);
            if (composed) {
                this.form.reception_date = composed;
            }
        },

        srSyncResolutionDate() {
            const composed = composeDmY(this.form.resolution_day, this.form.resolution_month, this.form.resolution_year);
            if (composed) {
                this.form.resolution_date = composed;
            }
        },

        srToggleProcessedImmediately() {
            if (this.isViewMode) return;
            this.form.is_processed_immediately = !this.form.is_processed_immediately;
        },

        srToggleAppointment() {
            if (this.isViewMode) return;
            this.srAppointmentEnabled = !this.srAppointmentEnabled;
            if (this.srAppointmentEnabled && !this.form.appointment_date) {
                const date = new Date();
                date.setDate(date.getDate() + 3);
                this.form.appointment_date = formatDmY(date);
            }
            if (!this.srAppointmentEnabled) {
                this.form.appointment_date = '';
            }
        },

        srToggleOtherNote() {
            if (this.isViewMode) return;
            this.srOtherNoteEnabled = !this.srOtherNoteEnabled;
            if (!this.srOtherNoteEnabled) {
                this.form.note = '';
            } else if (!this.form.note) {
                this.form.note = '';
            }
        },

        srPrepareSave() {
            this.srSyncReceptionDate();
            this.srSyncResolutionDate();
            if (!this.srAppointmentEnabled) {
                this.form.appointment_date = '';
            }
            if (!this.srOtherNoteEnabled) {
                this.form.note = '';
            }
            if (this.form.request_type) {
                this.form.request_type = String(this.form.request_type).trim();
                this.srEnsureRequestType(this.form.request_type);
            }
            if (this.form.department) {
                this.form.department = String(this.form.department).trim();
                this.srEnsureDepartment(this.form.department);
            }
            if (this.form.building_block) {
                this.form.building_block = String(this.form.building_block).trim();
                this.srEnsureBuildingBlock(this.form.building_block);
            }
            this.evidenceEmitChange?.();
        },

        srEnsureRequestType(value) {
            if (!value) {
                return;
            }
            const normalized = String(value).trim();
            if (!normalized) {
                return;
            }
            const options = this.requestTypeOptions || [];
            if (!options.some((option) => String(option.value) === normalized)) {
                this.requestTypeOptions = [...options, { value: normalized, label: normalized }];
            }
        },

        srEnsureDepartment(value) {
            if (!value) {
                return;
            }
            const normalized = String(value).trim();
            if (!normalized) {
                return;
            }
            const options = this.departmentOptions || [];
            if (!options.some((option) => String(option.value) === normalized)) {
                this.departmentOptions = [...options, { value: normalized, label: normalized }];
            }
        },

        srEnsureBuildingBlock(value) {
            if (!value) {
                return;
            }
            const normalized = String(value).trim();
            if (!normalized) {
                return;
            }
            const options = this.buildingOptions || [];
            if (!options.some((option) => String(option.value) === normalized)) {
                this.buildingOptions = [...options, { value: normalized, label: normalized }];
            }
        },

        srBuildingLabel(value) {
            const normalized = String(value || '').trim();
            if (!normalized) {
                return '---';
            }
            const options = this.buildingOptions || [];
            const match = options.find((option) => String(option.value) === normalized);
            return match?.label || normalized;
        },

        srRequestTypeLabel(value) {
            const normalized = String(value || '').trim();
            if (!normalized) {
                return '---';
            }
            const options = this.requestTypeOptions || [];
            const match = options.find((option) => String(option.value) === normalized);
            return match?.label || normalized;
        },

        srStripVirtualFields(payload) {
            [
                'reception_day', 'reception_month', 'reception_year',
                'resolution_day', 'resolution_month', 'resolution_year',
            ].forEach((key) => delete payload[key]);
        },
    };
}
