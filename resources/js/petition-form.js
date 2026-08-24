export function petitionFormState() {
    return {
        ptSections: { citizen: true, content: true },
        ptIsExtractingAi: false,
        ptCameraOpen: false,
        ptCameras: [],
        ptSelectedCameraId: "",
        ptCameraStream: null,
    };
}

export function petitionFormMethods() {
    return {
        async ptExtractAi(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.ptIsExtractingAi = true;
            try {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                const base64 = await new Promise((resolve, reject) => {
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = error => reject(error);
                });
                await this.ptProcessExtraction(base64);
            } catch (error) {
                console.error(error);
                this.nttuShowErrorMessage?.('Lỗi trích xuất: ' + error.message);
            } finally {
                this.ptIsExtractingAi = false;
                event.target.value = '';
            }
        },

        async ptProcessExtraction(base64) {
            this.ptIsExtractingAi = true;
            try {
                const response = await fetch('/ai/extract/petition', {
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

                if (data.citizen_name) this.form.citizen_name = data.citizen_name;
                if (data.citizen_id) this.form.citizen_id = data.citizen_id;
                if (data.citizen_phone) this.form.citizen_phone = data.citizen_phone;
                
                let addressParts = [];
                if (data.citizen_class) addressParts.push("Lớp: " + data.citizen_class);
                if (data.citizen_unit) addressParts.push("Khoa/ĐV: " + data.citizen_unit);
                if (addressParts.length > 0) {
                    this.form.citizen_address = addressParts.join(", ");
                }
                
                if (data.summary) this.form.summary = data.summary;
                
                this.nttuShowSuccessMessage?.('Trích xuất thông tin thành công!');
            } catch (error) {
                throw error;
            } finally {
                this.ptIsExtractingAi = false;
            }
        },

        async ptOpenCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.nttuShowErrorMessage?.('Trình duyệt không hỗ trợ mở camera trực tiếp.');
                return;
            }
            this.ptCameraOpen = true;
            await this.ptEnumerateCameras();
            await this.ptStartCameraStream(this.ptSelectedCameraId);
        },
        
        async ptEnumerateCameras() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(t => t.stop());
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.ptCameras = devices.filter(d => d.kind === 'videoinput');
                if (this.ptCameras.length > 0 && !this.ptSelectedCameraId) {
                    const backCamera = this.ptCameras.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('sau'));
                    this.ptSelectedCameraId = backCamera ? backCamera.deviceId : this.ptCameras[0].deviceId;
                }
            } catch (e) {
                console.error(e);
            }
        },
        
        async ptSwitchCamera() {
            if (!this.ptSelectedCameraId) return;
            await this.ptStartCameraStream(this.ptSelectedCameraId);
        },

        async ptStartCameraStream(deviceId) {
            if (this.ptCameraStream) {
                this.ptCameraStream.getTracks().forEach(t => t.stop());
            }
            try {
                const constraints = deviceId ? { video: { deviceId: { exact: deviceId } } } : { video: { facingMode: 'environment' } };
                this.ptCameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (this.$refs.ptVideo) {
                    this.$refs.ptVideo.srcObject = this.ptCameraStream;
                }
            } catch (e) {
                console.error(e);
                this.nttuShowErrorMessage?.('Không thể mở camera được chọn.');
            }
        },

        ptCloseCamera() {
            this.ptCameraOpen = false;
            if (this.ptCameraStream) {
                this.ptCameraStream.getTracks().forEach(t => t.stop());
                this.ptCameraStream = null;
            }
            if (this.$refs.ptVideo) {
                this.$refs.ptVideo.srcObject = null;
            }
        },

        async ptCapturePhoto() {
            const video = this.$refs.ptVideo;
            const canvas = this.$refs.ptCanvas;
            if (!video || !canvas || video.videoWidth === 0) return;
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            const base64 = canvas.toDataURL('image/jpeg', 0.8);
            
            this.ptCloseCamera();
            
            try {
                await this.ptProcessExtraction(base64);
            } catch (error) {
                console.error(error);
                this.nttuShowErrorMessage?.('Lỗi trích xuất: ' + error.message);
            }
        },

        ptOnModalOpen() {
            this.ptSections = { citizen: true, content: true };
        },

        ptToggleAccepted() {
            if (this.isViewMode) return;
            this.form.is_accepted = !this.form.is_accepted;
        },

        ptToggleReturned() {
            if (this.isViewMode) return;
            this.form.is_returned = !this.form.is_returned;
        },

        ptToggleForwarded() {
            if (this.isViewMode) return;
            this.form.is_forwarded = !this.form.is_forwarded;
        },
    };
}
