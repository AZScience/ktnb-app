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

export function ensureDepartmentInOptions(options, value) {
    if (!value) {
        return options || [];
    }

    const normalized = String(value).trim();
    if (!normalized) {
        return options || [];
    }

    const list = options || [];
    if (list.some((option) => String(option.value) === normalized)) {
        return list;
    }

    return [...list, { value: normalized, label: normalized }];
}

export function assetReceptionFormState() {
    return {
        arShowEvidence: false,
        arIsExtractingAi: false,
        arCameraOpen: false,
        arCameraStream: null,
        arCameras: [],
        arSelectedCameraId: "",
    };
}

export function assetReceptionFormMethods() {
    return {
        async arExtractAi(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.arIsExtractingAi = true;
            try {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                const base64 = await new Promise((resolve, reject) => {
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = error => reject(error);
                });
                await this.arProcessExtraction(base64);
            } catch (error) {
                console.error(error);
                this.nttuShowErrorMessage?.('Lỗi trích xuất: ' + error.message);
            } finally {
                this.arIsExtractingAi = false;
                event.target.value = '';
            }
        },

        async arProcessExtraction(base64) {
            this.arIsExtractingAi = true;
            try {
                const response = await fetch('/ai/extract/asset-reception', {
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

                if (data.giver_name) this.form.giver_name = data.giver_name;
                if (data.giver_employee_code) this.form.giver_employee_code = data.giver_employee_code;
                if (data.giver_id) this.form.giver_id = data.giver_id;
                if (data.giver_class) this.form.giver_class = data.giver_class;
                
                if (data.giver_unit) {
                    this.form.giver_unit = data.giver_unit;
                    this.arEnsureDepartment(data.giver_unit);
                }
                
                if (data.giver_phone) this.form.giver_phone = data.giver_phone;
                if (data.asset_state) this.form.asset_state = data.asset_state;
                if (data.content) this.form.content = data.content;
                
                if (data.reception_day) this.form.reception_day = String(data.reception_day).padStart(2, '0');
                if (data.reception_month) this.form.reception_month = String(data.reception_month).padStart(2, '0');
                if (data.reception_year) this.form.reception_year = String(data.reception_year);
                
                this.arSyncReceptionDate();
                this.nttuShowSuccessMessage?.('Trích xuất thông tin thành công!');
            } catch (error) {
                throw error;
            } finally {
                this.arIsExtractingAi = false;
            }
        },

        async arOpenCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.nttuShowErrorMessage?.('Trình duyệt không hỗ trợ mở camera trực tiếp.');
                return;
            }
            this.arCameraOpen = true;
            await this.arEnumerateCameras();
            await this.arStartCameraStream(this.arSelectedCameraId);
        },
        
        async arEnumerateCameras() {
            try {
                // Request permission first to get labels
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(t => t.stop());
                
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.arCameras = devices.filter(d => d.kind === 'videoinput');
                if (this.arCameras.length > 0 && !this.arSelectedCameraId) {
                    const backCamera = this.arCameras.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('sau'));
                    this.arSelectedCameraId = backCamera ? backCamera.deviceId : this.arCameras[0].deviceId;
                }
            } catch (e) {
                console.error(e);
            }
        },
        
        async arSwitchCamera() {
            if (!this.arSelectedCameraId) return;
            await this.arStartCameraStream(this.arSelectedCameraId);
        },

        async arStartCameraStream(deviceId) {
            if (this.arCameraStream) {
                this.arCameraStream.getTracks().forEach(t => t.stop());
            }
            try {
                const constraints = deviceId ? { video: { deviceId: { exact: deviceId } } } : { video: { facingMode: 'environment' } };
                this.arCameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (this.$refs.arVideo) {
                    this.$refs.arVideo.srcObject = this.arCameraStream;
                }
            } catch (e) {
                console.error(e);
                this.nttuShowErrorMessage?.('Không thể mở camera được chọn.');
            }
        },

        arCloseCamera() {
            this.arCameraOpen = false;
            if (this.arCameraStream) {
                this.arCameraStream.getTracks().forEach(t => t.stop());
                this.arCameraStream = null;
            }
            if (this.$refs.arVideo) {
                this.$refs.arVideo.srcObject = null;
            }
        },

        async arCapturePhoto() {
            const video = this.$refs.arVideo;
            const canvas = this.$refs.arCanvas;
            if (!video || !canvas || video.videoWidth === 0) return;
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            const base64 = canvas.toDataURL('image/jpeg', 0.8);
            
            this.arCloseCamera();
            
            try {
                await this.arProcessExtraction(base64);
            } catch (error) {
                console.error(error);
                this.nttuShowErrorMessage?.('Lỗi trích xuất: ' + error.message);
            }
        },
        arOnModalOpen() {
            const reception = parseDmYParts(this.form.reception_date);
            this.form.reception_day = reception.day;
            this.form.reception_month = reception.month;
            this.form.reception_year = reception.year;

            if ((this.dialogMode === 'add' || this.dialogMode === 'copy') && this.staffDefault) {
                this.form.receiving_staff = this.staffDefault;
            } else if (!this.form.receiving_staff && this.staffDefault) {
                this.form.receiving_staff = this.staffDefault;
            }

            this.arShowEvidence = Boolean(this.form.evidence);

            if (this.form.giver_unit) {
                this.arEnsureDepartment(this.form.giver_unit);
            }
            if (this.form.building_block) {
                this.arEnsureBuildingBlock(this.form.building_block);
            }

            this.$nextTick(() => this.evidenceOnModalOpen?.());
        },

        arOnModalClose() {
            this.arShowEvidence = false;
            this.evidenceCleanupPanel?.();
        },

        arSyncReceptionDate() {
            const composed = composeDmY(this.form.reception_day, this.form.reception_month, this.form.reception_year);
            if (composed) {
                this.form.reception_date = composed;
            }
        },

        arPrepareSave() {
            this.arSyncReceptionDate();
            if (!this.form.receiving_staff && this.staffDefault) {
                this.form.receiving_staff = this.staffDefault;
            }
            if (this.form.giver_unit) {
                this.form.giver_unit = String(this.form.giver_unit).trim();
                this.arEnsureDepartment(this.form.giver_unit);
            }
            if (this.form.building_block) {
                this.form.building_block = String(this.form.building_block).trim();
                this.arEnsureBuildingBlock(this.form.building_block);
            }
            this.evidenceEmitChange?.();
        },

        arEnsureBuildingBlock(value) {
            if (!value || value === 'Khác') {
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

        arBuildingLabel(value) {
            if (!value || value === 'Khác') {
                return value || '---';
            }
            const normalized = String(value).trim();
            const options = this.buildingOptions || [];
            const match = options.find((option) => String(option.value) === normalized);

            return match?.label || normalized || '---';
        },

        arEnsureDepartment(value) {
            this.departmentOptions = ensureDepartmentInOptions(this.departmentOptions, value);
        },

        arStripVirtualFields(payload) {
            ['reception_day', 'reception_month', 'reception_year'].forEach((key) => delete payload[key]);
        },
    };
}
