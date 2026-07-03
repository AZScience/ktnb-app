import {
    decodeViolationImage,
    scanViolationVideoFrame,
    violationFindStudent,
    violationParseScannedCode,
} from './violation-card-scanner.js';

let violationHtml5QrcodeModule = null;

const violationRuntimeStore = new WeakMap();

const VIOLATION_QR_READER_ID = 'violation-qr-reader';

async function loadViolationHtml5Qrcode() {
    if (!violationHtml5QrcodeModule) {
        violationHtml5QrcodeModule = await import('html5-qrcode');
    }

    return violationHtml5QrcodeModule;
}

function violationRuntime(component) {
    if (!violationRuntimeStore.has(component)) {
        violationRuntimeStore.set(component, {
            scanner: null,
            initToken: 0,
            cameras: null,
            activeCameraId: null,
            preferredFacingMode: null,
            turboInterval: null,
            turboBusy: false,
            scanHandled: false,
        });
    }

    return violationRuntimeStore.get(component);
}

function violationFormatsForMode(mode, module) {
    const F = module.Html5QrcodeSupportedFormats;
    if (mode === 'qr') {
        return [F.QR_CODE, F.AZTEC, F.DATA_MATRIX, F.PDF_417];
    }

    return [
        F.CODE_128,
        F.CODE_39,
        F.CODABAR,
        F.EAN_13,
        F.EAN_8,
        F.UPC_A,
        F.UPC_E,
        F.ITF,
        F.PDF_417,
        F.DATA_MATRIX,
        F.AZTEC,
    ];
}

function violationScannerConfig(scanMode) {
    return {
        fps: 20,
        qrbox: (viewW, viewH) => ({
            width: Math.round(viewW * 0.94),
            height: Math.round(viewH * (scanMode === 'barcode' ? 0.82 : 0.94)),
        }),
        disableFlip: false,
    };
}

function violationVideoFrameToBlob(video, mimeType = 'image/png', quality = 0.98) {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return Promise.reject(new Error('Không chụp được ảnh từ camera.'));
    }

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
            } else {
                reject(new Error('Không chụp được ảnh từ camera.'));
            }
        }, mimeType, quality);
    });
}

function violationPrepareReaderElement(element, panel) {
    if (!element) {
        return;
    }

    const width = panel?.clientWidth || element.clientWidth || 320;
    const height = panel?.clientHeight || element.clientHeight || 320;
    element.style.width = `${width}px`;
    element.style.height = `${height}px`;
    element.style.minHeight = `${height}px`;
}

async function violationWaitForReaderSize(element, maxAttempts = 40) {
    const panel = element?.closest?.('.violation-scanner-panel');

    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
        violationPrepareReaderElement(element, panel);
        const width = element?.clientWidth || panel?.clientWidth || 0;
        const height = element?.clientHeight || panel?.clientHeight || 0;
        if (width > 0 && height > 0) {
            return true;
        }

        await new Promise((resolve) => {
            setTimeout(resolve, attempt < 10 ? 32 : 64);
        });
    }

    violationPrepareReaderElement(element, panel);
    const width = element?.clientWidth || panel?.clientWidth || 0;
    const height = element?.clientHeight || panel?.clientHeight || 0;

    return width > 0 && height > 0;
}

async function compressImageDataUrl(dataUrl, maxSize = 1000, quality = 0.85) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let { width, height } = img;
            if (width > height && width > maxSize) {
                height = Math.round((height * maxSize) / width);
                width = maxSize;
            } else if (height > maxSize) {
                width = Math.round((width * maxSize) / height);
                height = maxSize;
            }
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d')?.drawImage(img, 0, 0, width, height);
            resolve(canvas.toDataURL('image/jpeg', quality));
        };
        img.onerror = reject;
        img.src = dataUrl;
    });
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

async function dataUrlToBlob(dataUrl) {
    const res = await fetch(dataUrl);
    return res.blob();
}

export function violationFormState(config = {}) {
    return {
        violationRoutes: config.violationRoutes || {},
        violationStudents: config.violationStudents || [],
        violationSearchStatus: 'idle',
        violationStudentInSystem: false,
        violationStudentNotInSystem: false,
        violationCompareStatus: 'idle',
        violationCompareConfidence: null,
        violationScannerOpen: false,
        violationScannerLoading: false,
        violationScannerDecoding: false,
        violationScannerExtracting: false,
        violationScannerError: null,
        violationScannerSuccess: false,
        violationScannerFocusActive: false,
        violationScannerFocusBox: null,
        violationScannerMode: 'barcode',
        violationDocumentExtracting: false,
        violationPhotoCameraOpen: false,
        violationPhotoCameraLabel: '',
        violationPhotoCameraField: '',
        violationPhotoStream: null,
        violationPhotoZoom: 1,
        violationPhotoFrameW: 0.6,
        violationPhotoFrameH: 0.45,
        violationPhotoFrameX: 0.5,
        violationPhotoFrameY: 0.5,
        violationPhotoDragging: false,
        violationSignatureOpen: false,
        violationSignatureDrawing: false,
    };
}

export function violationFormMethods() {
    return {
        violationBuildStudentMap() {
            const map = {};
            (this.violationStudents || []).forEach((student) => {
                const id = String(student.id || '').trim().toLowerCase();
                const citizen = String(student.citizen_id || student.identifier || '').trim().toLowerCase();
                if (id) map[id] = student;
                if (citizen) map[citizen] = student;
            });
            this.violationStudentMap = map;
        },

        violationStudentCode() {
            return String(this.form.student_id || this.form.identifier || '').trim();
        },

        violationResolveStudentState() {
            const code = this.violationStudentCode().toLowerCase();
            if (!code) {
                this.violationStudentInSystem = false;
                this.violationStudentNotInSystem = false;

                return;
            }

            const student = this.violationStudentMap?.[code];
            if (student) {
                this.violationStudentInSystem = true;
                this.violationStudentNotInSystem = false;
            } else {
                this.violationStudentInSystem = false;
                this.violationStudentNotInSystem = true;
            }
        },

        violationStudentFieldEditable() {
            if (this.isViewMode) {
                return false;
            }

            return !this.violationStudentInSystem;
        },

        violationOnStudentIdInput(value) {
            this.form.student_id = value;
            this.form.identifier = value;
            if (this.violationSearchStatus !== 'idle') {
                this.violationSearchStatus = 'idle';
            }
            this.violationResolveStudentState();
        },

        violationEnsureSelectOption(fieldKey, value) {
            if (!value) return;
            const field = (this.formFields || []).find((f) => f.key === fieldKey);
            if (!field || field.type !== 'select') return;
            const options = field.options || [];
            if (!options.some((o) => String(o.value) === String(value))) {
                field.options = [...options, { value, label: value }];
            }
        },

        violationApplyStudent(student) {
            this.form.full_name = student.name || '';
            this.form.student_id = student.id || '';
            this.form.identifier = student.citizen_id || student.identifier || this.form.identifier || '';
            if (student.class) {
                this.form['class'] = student.class;
                this.violationEnsureSelectOption('class', student.class);
            }
            const dept = student.department || student.major || '';
            if (dept) {
                this.form.department = dept;
                this.violationEnsureSelectOption('department', dept);
            }
        },

        violationSearchStudent() {
            const code = this.violationStudentCode().toLowerCase();
            if (!code) {
                this.showToast?.('Vui lòng nhập MSSV hoặc CCCD trước khi tìm.', 'error');
                return;
            }

            const student = this.violationStudentMap[code];
            if (student) {
                this.violationApplyStudent(student);
                this.violationSearchStatus = 'success';
                this.violationStudentInSystem = true;
                this.violationStudentNotInSystem = false;
                this.showToast?.(`Đã tìm thấy sinh viên: ${student.name} (${student.id})`);
                setTimeout(() => { this.violationSearchStatus = 'idle'; }, 2000);
            } else {
                this.violationSearchStatus = 'error';
                this.violationStudentInSystem = false;
                this.violationStudentNotInSystem = true;
                this.showToast?.('Không có thông tin sinh viên này trong hệ thống.', 'error');
                setTimeout(() => { this.violationSearchStatus = 'idle'; }, 3000);
            }
        },

        violationHandleScan(code, fromDocument = false) {
            this.violationBuildStudentMap();
            const parsed = violationParseScannedCode(code);
            const match = violationFindStudent(this.violationStudentMap, parsed.candidates);
            const verb = fromDocument ? 'trích xuất' : 'quét';

            if (match?.student) {
                this.violationApplyStudent(match.student);
                if (parsed.isCCCD) {
                    this.form.identifier = match.matchedCode;
                }
                this.violationSearchStatus = 'success';
                this.violationStudentInSystem = true;
                this.violationStudentNotInSystem = false;
                this.showToast?.(`Đã ${verb} mã "${parsed.displayCode}" · Tìm thấy: ${match.student.name} (${match.student.id})`);
                setTimeout(() => { this.violationSearchStatus = 'idle'; }, 2000);
                return;
            }

            const fallbackCode = parsed.candidates[0] || '';
            if (parsed.isCCCD) {
                this.form.full_name = parsed.scannedName;
                this.form.identifier = fallbackCode;
                this.form.student_id = '';
                this.showToast?.(`Đã ${verb} CCCD "${parsed.displayCode}" · ${parsed.scannedName || 'Chưa có tên'}. Sinh viên chưa có trong hệ thống.`, 'error');
            } else {
                this.form.student_id = fallbackCode;
                this.form.identifier = fallbackCode;
                this.showToast?.(`Đã ${verb} mã "${parsed.displayCode}" · Không tìm thấy sinh viên trong danh sách.`, 'error');
            }
            this.violationSearchStatus = 'error';
            this.violationStudentInSystem = false;
            this.violationStudentNotInSystem = true;
            this.violationResolveStudentState();
            setTimeout(() => { this.violationSearchStatus = 'idle'; }, 3000);
        },

        async violationExtractFromDocumentPhoto() {
            if (!this.form.document_photo) {
                this.showToast?.('Chưa có ảnh giấy tờ.', 'error');
                return;
            }

            this.violationDocumentExtracting = true;
            try {
                const blob = await dataUrlToBlob(this.form.document_photo);
                const decodedText = await decodeViolationImage(blob, 'auto');
                if (!decodedText) {
                    throw new Error('Không đọc được mã trên ảnh giấy tờ. Hãy chụp lại rõ hơn, đủ sáng và đưa mã vào giữa khung.');
                }

                this.violationHandleScan(decodedText, true);
            } catch (err) {
                const message = String(err?.message || 'Không đọc được mã trên ảnh giấy tờ.');
                this.showToast?.(message, 'error');
            } finally {
                this.violationDocumentExtracting = false;
            }
        },

        async violationStartScanner() {
            if (this.isViewMode) {
                return;
            }

            if (!window.isSecureContext) {
                this.showToast?.('Camera chỉ hoạt động trên HTTPS hoặc localhost. Hãy truy cập qua http://127.0.0.1:8000.', 'error');
                return;
            }

            this.violationScannerOpen = true;
            this.violationScannerSuccess = false;
            this.violationScannerDecoding = false;
            this.violationScannerExtracting = false;
            this.violationScannerError = null;
            this.violationScannerFocusActive = false;
            this.violationScannerFocusBox = null;
            await this.$nextTick();
            await this.violationInitScanner();
        },

        async violationInitScanner(options = {}) {
            const { cameraId = null, facingMode = null } = options;
            const rt = violationRuntime(this);
            const token = ++rt.initToken;
            this.violationScannerLoading = true;
            this.violationScannerError = null;

            try {
                await this.$nextTick();
                await new Promise((resolve) => {
                    requestAnimationFrame(() => requestAnimationFrame(resolve));
                });

                const readerEl = document.getElementById(VIOLATION_QR_READER_ID);
                if (!readerEl) {
                    throw new Error('Không tìm thấy vùng quét.');
                }

                const hasSize = await violationWaitForReaderSize(readerEl);
                if (!hasSize) {
                    throw new Error('Vùng quét chưa sẵn sàng. Vui lòng thử lại.');
                }

                const module = await loadViolationHtml5Qrcode();
                if (token !== rt.initToken) {
                    return;
                }

                await this.violationCleanupScanner();

                const { Html5Qrcode } = module;
                const scanner = new Html5Qrcode(VIOLATION_QR_READER_ID, {
                    formatsToSupport: violationFormatsForMode(this.violationScannerMode, module),
                    useBarCodeDetectorIfSupported: true,
                    verbose: false,
                });
                rt.scanner = scanner;

                const config = violationScannerConfig(this.violationScannerMode);
                const onSuccess = (decodedText) => {
                    this.violationOnScannerDecoded(decodedText);
                };

                const cameraCandidates = cameraId
                    ? (facingMode ? [cameraId, { facingMode }] : [cameraId])
                    : facingMode
                        ? [{ facingMode }]
                        : await this.violationScannerCameraCandidates(module);
                if (token !== rt.initToken) {
                    return;
                }

                rt.scanHandled = false;
                let lastError = null;
                let started = false;
                for (const camera of cameraCandidates) {
                    if (token !== rt.initToken) {
                        return;
                    }

                    try {
                        await scanner.start(camera, config, onSuccess, () => {});
                        if (typeof camera === 'string') {
                            rt.activeCameraId = camera;
                        } else if (camera?.facingMode) {
                            rt.preferredFacingMode = camera.facingMode;
                        }
                        this.violationSyncActiveCameraFromTrack();
                        await this.violationEnableCameraAutofocus();
                        this.violationStartTurboScan();
                        started = true;
                        return;
                    } catch (err) {
                        lastError = err;
                    }
                }

                if (!started) {
                    throw lastError || new Error('Không thể mở camera.');
                }
            } catch (err) {
                let message = String(err?.message || 'Không thể khởi động camera.');
                if (err?.name === 'NotAllowedError') {
                    message = 'Bạn đã chặn quyền camera. Vui lòng cấp quyền trong trình duyệt.';
                } else if (err?.name === 'NotReadableError') {
                    message = 'Camera đang bị ứng dụng khác sử dụng.';
                }
                this.violationScannerError = message;
                this.showToast?.(message, 'error');
            } finally {
                if (token === rt.initToken) {
                    this.violationScannerLoading = false;
                }
            }
        },

        async violationScannerCameraCandidates(module) {
            const rt = violationRuntime(this);
            const { Html5Qrcode } = module;

            if (!rt.cameras) {
                rt.cameras = await Html5Qrcode.getCameras();
            }

            const candidates = [];
            const add = (value) => {
                const key = typeof value === 'string' ? value : JSON.stringify(value);
                if (!candidates.some((item) => (typeof item === 'string' ? item : JSON.stringify(item)) === key)) {
                    candidates.push(value);
                }
            };

            if (rt.activeCameraId) {
                add(rt.activeCameraId);
            }

            const backCamera = (rt.cameras || []).find((camera) => /back|rear|environment/i.test(camera.label || ''));
            if (backCamera) {
                add(backCamera.id);
            }

            const frontCamera = (rt.cameras || []).find((camera) => /front|user|face/i.test(camera.label || ''));
            if (frontCamera) {
                add(frontCamera.id);
            }

            (rt.cameras || []).forEach((camera) => add(camera.id));
            add({ facingMode: 'environment' });
            add({ facingMode: 'user' });

            return candidates;
        },

        violationGetScannerVideo() {
            return document.querySelector(`#${VIOLATION_QR_READER_ID} video`);
        },

        violationSyncActiveCameraFromTrack() {
            const rt = violationRuntime(this);
            const track = this.violationGetScannerVideo()?.srcObject?.getVideoTracks?.()?.[0];
            const settings = track?.getSettings?.();
            if (settings?.deviceId) {
                rt.activeCameraId = settings.deviceId;
            }
            if (settings?.facingMode) {
                rt.preferredFacingMode = settings.facingMode;
            }
        },

        violationActiveCameraLabel() {
            const rt = violationRuntime(this);
            const camera = (rt.cameras || []).find((item) => item.id === rt.activeCameraId);
            if (camera?.label) {
                return camera.label;
            }

            if (rt.preferredFacingMode === 'user') {
                return 'Camera trước';
            }

            if (rt.preferredFacingMode === 'environment') {
                return 'Camera sau';
            }

            return 'Camera hiện tại';
        },

        violationScannerFocusStyle() {
            const box = this.violationScannerFocusBox;
            if (!box) {
                return 'display:none';
            }

            return `left:${(box.x * 100).toFixed(2)}%;top:${(box.y * 100).toFixed(2)}%;width:${(box.w * 100).toFixed(2)}%;height:${(box.h * 100).toFixed(2)}%`;
        },

        async violationEnableCameraAutofocus() {
            const track = this.violationGetScannerVideo()?.srcObject?.getVideoTracks?.()?.[0];
            if (!track?.applyConstraints) {
                return;
            }

            const attempts = [
                {
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                    advanced: [{ focusMode: 'continuous' }],
                },
                { advanced: [{ focusMode: 'continuous' }] },
                { focusMode: 'continuous' },
                { focusMode: 'auto' },
            ];

            for (const constraints of attempts) {
                try {
                    await track.applyConstraints(constraints);
                    return;
                } catch {
                    // try next constraint shape
                }
            }
        },

        async violationTapToFocus(event) {
            const panel = event.currentTarget;
            const video = this.violationGetScannerVideo();
            const track = video?.srcObject?.getVideoTracks?.()?.[0];
            if (!track?.applyConstraints || !panel) {
                return;
            }

            const rect = panel.getBoundingClientRect();
            const x = Math.min(Math.max((event.clientX - rect.left) / rect.width, 0), 1);
            const y = Math.min(Math.max((event.clientY - rect.top) / rect.height, 0), 1);

            const attempts = [
                { advanced: [{ pointsOfInterest: [{ x, y }], focusMode: 'single-shot' }] },
                { focusMode: 'single-shot' },
            ];

            for (const constraints of attempts) {
                try {
                    await track.applyConstraints(constraints);
                    setTimeout(() => {
                        this.violationEnableCameraAutofocus();
                    }, 700);
                    return;
                } catch {
                    // unsupported on this device/browser
                }
            }
        },

        violationUpdateScannerFocus(hit) {
            if (hit?.focusBox) {
                this.violationScannerFocusBox = hit.focusBox;
                this.violationScannerFocusActive = true;
                return;
            }

            this.violationScannerFocusBox = null;
            this.violationScannerFocusActive = false;
        },

        violationOnScannerDecoded(decodedText) {
            const rt = violationRuntime(this);
            const code = String(decodedText || '').trim();
            if (!code || !this.violationScannerOpen || rt.scanHandled) {
                return;
            }

            rt.scanHandled = true;
            this.violationScannerSuccess = true;
            this.violationStopTurboScan();
            this.violationStopScanner({ keepOpen: false });
            this.violationScannerOpen = false;
            this.violationHandleScan(code, false);
        },

        violationStartTurboScan() {
            const rt = violationRuntime(this);
            this.violationStopTurboScan();

            rt.turboInterval = setInterval(async () => {
                if (!this.violationScannerOpen || rt.scanHandled || rt.turboBusy || this.violationScannerDecoding || this.violationScannerExtracting) {
                    return;
                }

                const video = this.violationGetScannerVideo();
                if (!video?.videoWidth) {
                    return;
                }

                rt.turboBusy = true;
                try {
                    const hit = await scanViolationVideoFrame(video, this.violationScannerMode);
                    this.violationUpdateScannerFocus(hit);
                    if (hit?.text) {
                        this.violationOnScannerDecoded(hit.text);
                    }
                } catch {
                    // ignore frame decode errors
                } finally {
                    rt.turboBusy = false;
                }
            }, this.violationScannerMode === 'barcode' ? 280 : 350);
        },

        violationStopTurboScan() {
            const rt = violationRuntime(this);
            if (rt.turboInterval) {
                clearInterval(rt.turboInterval);
                rt.turboInterval = null;
            }
            rt.turboBusy = false;
        },

        async violationDecodeScannerImage(fileOrBlob) {
            this.violationScannerDecoding = true;
            this.violationScannerError = null;

            try {
                const decodedText = await decodeViolationImage(fileOrBlob, 'auto');
                if (!decodedText) {
                    throw new Error('Không đọc được mã QR/Barcode trên ảnh. Hãy chụp lại gần hơn, đủ sáng và giữ mã trong khung.');
                }

                this.violationOnScannerDecoded(decodedText);
            } catch (err) {
                const message = String(err?.message || 'Không đọc được mã trên ảnh.');
                this.violationScannerError = message;
                this.showToast?.(message, 'error');
            } finally {
                this.violationScannerDecoding = false;
            }
        },

        violationApplyExtractedCard(data, { closeScanner = false } = {}) {
            this.violationBuildStudentMap();

            if (data.full_name) {
                this.form.full_name = data.full_name;
            }
            if (data.student_id) {
                this.form.student_id = data.student_id;
                if (!data.citizen_id) {
                    this.form.identifier = data.student_id;
                }
            }
            if (data.citizen_id) {
                this.form.identifier = data.citizen_id;
                if (!data.student_id) {
                    this.form.student_id = '';
                }
            }
            if (data.class) {
                this.form['class'] = data.class;
                this.violationEnsureSelectOption('class', data.class);
            }
            if (data.department) {
                this.form.department = data.department;
                this.violationEnsureSelectOption('department', data.department);
            }

            const lookupCode = String(data.student_id || data.citizen_id || '').trim().toLowerCase();
            const student = lookupCode ? this.violationStudentMap?.[lookupCode] : null;

            if (student) {
                this.violationApplyStudent(student);
                this.violationSearchStatus = 'success';
                this.violationStudentInSystem = true;
                this.violationStudentNotInSystem = false;
                this.showToast?.(`Đã trích xuất thông tin · Tìm thấy: ${student.name} (${student.id})`);
            } else if (lookupCode) {
                this.violationSearchStatus = 'error';
                this.violationStudentInSystem = false;
                this.violationStudentNotInSystem = true;
                this.showToast?.(data.message || 'Đã trích xuất thông tin. Sinh viên chưa có trong hệ thống.', 'error');
            } else {
                this.showToast?.(data.message || 'Đã trích xuất thông tin từ ảnh.');
            }

            this.violationResolveStudentState();
            setTimeout(() => { this.violationSearchStatus = 'idle'; }, closeScanner ? 2000 : 3000);

            if (closeScanner) {
                const rt = violationRuntime(this);
                rt.scanHandled = true;
                this.violationScannerSuccess = true;
                this.violationStopTurboScan();
                this.violationStopScanner({ keepOpen: false });
                this.violationScannerOpen = false;
            }
        },

        async violationExtractInfoFromImage(dataUrl, { closeScanner = false } = {}) {
            if (!this.violationRoutes?.extractCard) {
                this.showToast?.('Chưa cấu hình API trích xuất thông tin.', 'error');
                return;
            }

            this.violationScannerExtracting = true;
            this.violationScannerError = null;

            try {
                const compressed = await compressImageDataUrl(dataUrl);
                this.form.document_photo = compressed;
                this.violationResetCompare();

                const res = await fetch(this.violationRoutes.extractCard, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ document_photo: compressed }),
                });
                const text = await res.text();
                let payload;
                try {
                    payload = text ? JSON.parse(text) : {};
                } catch {
                    throw new Error('Máy chủ trả về phản hồi không hợp lệ. Vui lòng tải lại trang và thử lại.');
                }
                if (!res.ok) {
                    throw new Error(payload.message || 'Trích xuất thông tin thất bại.');
                }
                if (!payload.success) {
                    throw new Error(payload.message || 'Không trích xuất được thông tin từ ảnh.');
                }

                this.violationApplyExtractedCard(payload, { closeScanner });
            } catch (err) {
                const message = String(err?.message || 'Không trích xuất được thông tin từ ảnh.');
                this.violationScannerError = message;
                this.showToast?.(message, 'error');
            } finally {
                this.violationScannerExtracting = false;
            }
        },

        async violationCaptureAndScanFromCamera() {
            const video = this.violationGetScannerVideo();
            if (!video?.videoWidth) {
                this.showToast?.('Camera chưa sẵn sàng. Vui lòng đợi hoặc đổi camera.', 'error');
                return;
            }

            try {
                const blob = await violationVideoFrameToBlob(video);
                await this.violationDecodeScannerImage(blob);
            } catch (err) {
                const message = String(err?.message || 'Không chụp được ảnh từ camera.');
                this.showToast?.(message, 'error');
            }
        },

        async violationCaptureAndExtractFromCamera() {
            const video = this.violationGetScannerVideo();
            if (!video?.videoWidth) {
                this.showToast?.('Camera chưa sẵn sàng. Vui lòng đợi hoặc đổi camera.', 'error');
                return;
            }

            try {
                const blob = await violationVideoFrameToBlob(video);
                const dataUrl = await readFileAsDataUrl(blob);
                await this.violationExtractInfoFromImage(dataUrl, { closeScanner: true });
            } catch (err) {
                const message = String(err?.message || 'Không chụp được ảnh từ camera.');
                this.showToast?.(message, 'error');
            }
        },

        async violationCleanupScanner() {
            this.violationStopTurboScan();
            const rt = violationRuntime(this);
            const scanner = rt.scanner;
            rt.scanner = null;

            if (scanner) {
                try {
                    const state = scanner.getState?.();
                    if (state === 2 || scanner.isScanning) {
                        await scanner.stop();
                    }
                    await scanner.clear();
                } catch {
                    // ignore cleanup errors
                }
            }

            const readerEl = document.getElementById(VIOLATION_QR_READER_ID);
            if (readerEl) {
                readerEl.innerHTML = '';
            }
        },

        async violationStopScanner({ keepOpen = false } = {}) {
            const rt = violationRuntime(this);
            rt.initToken += 1;
            await this.violationCleanupScanner();

            if (!keepOpen) {
                this.violationScannerOpen = false;
                this.violationScannerLoading = false;
                this.violationScannerDecoding = false;
                this.violationScannerExtracting = false;
                this.violationScannerSuccess = false;
                this.violationScannerError = null;
                this.violationScannerFocusActive = false;
                this.violationScannerFocusBox = null;
            }
        },

        async violationCloseScanner() {
            await this.violationStopScanner();
        },

        async violationSetScannerMode(mode) {
            if (this.violationScannerMode === mode) {
                return;
            }

            this.violationScannerMode = mode;
            if (!this.violationScannerOpen) {
                return;
            }

            this.violationScannerSuccess = false;
            await this.violationInitScanner();
        },

        async violationToggleScannerCamera() {
            if (this.violationScannerLoading || this.violationScannerDecoding || this.violationScannerExtracting) {
                return;
            }

            const rt = violationRuntime(this);
            rt.initToken += 1;
            this.violationSyncActiveCameraFromTrack();

            const currentTrackId = this.violationGetScannerVideo()?.srcObject?.getVideoTracks?.()?.[0]?.getSettings?.()?.deviceId
                || rt.activeCameraId;

            try {
                const module = await loadViolationHtml5Qrcode();
                const { Html5Qrcode } = module;

                rt.cameras = await Html5Qrcode.getCameras();

                if ((rt.cameras || []).length < 2) {
                    const devices = await navigator.mediaDevices?.enumerateDevices?.() || [];
                    const videoInputs = devices.filter((device) => device.kind === 'videoinput' && device.deviceId);
                    if (videoInputs.length >= 2) {
                        rt.cameras = videoInputs.map((device) => ({
                            id: device.deviceId,
                            label: device.label || `Camera ${device.deviceId.slice(0, 6)}`,
                        }));
                    }
                }

                let nextCameraId = null;
                let nextFacingMode = null;
                const cameras = rt.cameras || [];

                if (cameras.length >= 2) {
                    const currentIndex = cameras.findIndex((camera) => camera.id === currentTrackId);
                    if (currentIndex >= 0) {
                        nextCameraId = cameras[(currentIndex + 1) % cameras.length].id;
                    } else {
                        const alternate = cameras.find((camera) => camera.id !== currentTrackId);
                        nextCameraId = alternate?.id || cameras[0].id;
                    }

                    if (nextCameraId === currentTrackId) {
                        const alternate = cameras.find((camera) => camera.id !== currentTrackId);
                        if (alternate) {
                            nextCameraId = alternate.id;
                        }
                    }
                } else {
                    nextFacingMode = (rt.preferredFacingMode || 'environment') === 'user' ? 'environment' : 'user';
                }

                this.violationScannerSuccess = false;
                this.violationScannerFocusActive = false;
                this.violationScannerFocusBox = null;
                this.violationScannerError = null;

                await this.violationCleanupScanner();
                await this.violationInitScanner({
                    cameraId: nextCameraId || undefined,
                    facingMode: nextFacingMode || undefined,
                });

                if (!this.violationScannerError) {
                    this.showToast?.(`Đã chuyển sang: ${this.violationActiveCameraLabel()}`);
                }
            } catch (err) {
                const message = String(err?.message || 'Không thể đổi camera.');
                this.violationScannerError = message;
                this.showToast?.(message, 'error');
            }
        },

        async violationScanFromFile(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) {
                return;
            }

            await this.violationDecodeScannerImage(file);
        },

        async violationPickPhoto(field, event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                this.showToast?.('Ảnh quá lớn. Vui lòng chọn ảnh dưới 2MB.', 'error');
                return;
            }
            try {
                const dataUrl = await readFileAsDataUrl(file);
                const compressed = await compressImageDataUrl(dataUrl);
                this.form[field] = compressed;
                this.violationResetCompare();
                if (field === 'document_photo') {
                    await this.violationExtractFromDocumentPhoto();
                }
            } catch (_) {
                this.showToast?.('Không đọc được ảnh.', 'error');
            }
        },

        violationClearPhoto(field) {
            this.form[field] = '';
            this.violationResetCompare();
        },

        violationOpenPhotoCamera(field, label) {
            this.violationPhotoCameraField = field;
            this.violationPhotoCameraLabel = label;
            const isPortrait = label.toLowerCase().includes('chân dung');
            this.violationPhotoFrameW = isPortrait ? 0.35 : 0.6;
            this.violationPhotoFrameH = isPortrait ? 0.75 : 0.45;
            this.violationPhotoFrameX = 0.5;
            this.violationPhotoFrameY = 0.5;
            this.violationPhotoCameraOpen = true;
            this.$nextTick(() => this.violationStartPhotoCamera());
        },

        async violationStartPhotoCamera() {
            this.violationStopPhotoCamera();
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                });
                this.violationPhotoStream = stream;
                const video = this.$refs.violationPhotoVideo;
                if (video) video.srcObject = stream;
            } catch (_) {
                this.showToast?.('Không thể truy cập camera.', 'error');
            }
        },

        violationStopPhotoCamera() {
            if (this.violationPhotoStream) {
                this.violationPhotoStream.getTracks().forEach((t) => t.stop());
                this.violationPhotoStream = null;
            }
            const video = this.$refs.violationPhotoVideo;
            if (video) video.srcObject = null;
        },

        violationClosePhotoCamera() {
            this.violationStopPhotoCamera();
            this.violationPhotoCameraOpen = false;
        },

        async violationCapturePhoto() {
            const video = this.$refs.violationPhotoVideo;
            if (!video || !this.violationPhotoCameraField) return;
            const canvas = document.createElement('canvas');
            const vW = video.videoWidth;
            const vH = video.videoHeight;
            const cropW = vW * this.violationPhotoFrameW;
            const cropH = vH * this.violationPhotoFrameH;
            const startX = vW * this.violationPhotoFrameX - cropW / 2;
            const startY = vH * this.violationPhotoFrameY - cropH / 2;
            canvas.width = cropW;
            canvas.height = cropH;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            ctx.drawImage(video, startX, startY, cropW, cropH, 0, 0, cropW, cropH);
            const field = this.violationPhotoCameraField;
            this.form[field] = canvas.toDataURL('image/jpeg', 0.85);
            this.violationResetCompare();
            this.violationClosePhotoCamera();
            if (field === 'document_photo') {
                await this.violationExtractFromDocumentPhoto();
            }
        },

        violationResetCompare() {
            this.violationCompareStatus = 'idle';
            this.violationCompareConfidence = null;
        },

        async violationCompareFaces() {
            if (!this.form.portrait_photo || !this.form.document_photo) {
                this.showToast?.('Vui lòng chụp cả ảnh chân dung và giấy tờ trước khi đối soát.', 'error');
                return;
            }
            if (!this.violationRoutes?.compareFaces) {
                this.showToast?.('Chưa cấu hình API đối soát.', 'error');
                return;
            }

            this.violationCompareStatus = 'loading';
            try {
                const res = await fetch(this.violationRoutes.compareFaces, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        portrait_photo: this.form.portrait_photo,
                        document_photo: this.form.document_photo,
                    }),
                });
                const text = await res.text();
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch {
                    throw new Error('Máy chủ trả về phản hồi không hợp lệ. Vui lòng tải lại trang và thử lại.');
                }
                if (!res.ok) throw new Error(data.message || 'Đối soát thất bại');

                this.violationCompareStatus = data.is_match ? 'success' : 'error';
                this.violationCompareConfidence = data.confidence ?? 0;
                this.showToast?.(data.message || (data.is_match ? 'Đối soát thành công' : 'Cảnh báo đối soát'), data.is_match ? 'success' : 'error');
            } catch (err) {
                this.violationCompareStatus = 'error';
                this.violationCompareConfidence = 0;
                this.showToast?.(err.message || 'Không thể kết nối dịch vụ AI.', 'error');
            }
        },

        violationOpenSignature() {
            if (this.isViewMode) return;
            this.violationSignatureOpen = true;
            this.$nextTick(() => this.violationInitSignatureCanvas());
        },

        violationInitSignatureCanvas() {
            const canvas = this.$refs.violationSignatureCanvas;
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
            ctx.beginPath();

            if (this.form.signature_base64) {
                const img = new Image();
                img.onload = () => {
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                };
                img.src = this.form.signature_base64;
            }
        },

        violationSignaturePos(event, canvas) {
            const rect = canvas.getBoundingClientRect();
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const clientY = event.touches ? event.touches[0].clientY : event.clientY;
            return {
                x: (clientX - rect.left) * (canvas.width / rect.width),
                y: (clientY - rect.top) * (canvas.height / rect.height),
            };
        },

        violationStartSignature(event) {
            if (this.isViewMode) return;
            this.violationSignatureDrawing = true;
            const canvas = this.$refs.violationSignatureCanvas;
            const ctx = canvas?.getContext('2d');
            if (!ctx || !canvas) return;
            const { x, y } = this.violationSignaturePos(event, canvas);
            ctx.beginPath();
            ctx.moveTo(x, y);
        },

        violationDrawSignature(event) {
            if (!this.violationSignatureDrawing || this.isViewMode) return;
            const canvas = this.$refs.violationSignatureCanvas;
            const ctx = canvas?.getContext('2d');
            if (!ctx || !canvas) return;
            const { x, y } = this.violationSignaturePos(event, canvas);
            ctx.lineTo(x, y);
            ctx.stroke();
        },

        violationStopSignature() {
            this.violationSignatureDrawing = false;
        },

        violationClearSignatureCanvas() {
            const canvas = this.$refs.violationSignatureCanvas;
            const ctx = canvas?.getContext('2d');
            if (!ctx || !canvas) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.beginPath();
        },

        violationConfirmSignature() {
            const canvas = this.$refs.violationSignatureCanvas;
            if (!canvas) return;
            const dataUrl = canvas.toDataURL();
            this.form.signature_base64 = dataUrl;
            this.form.signed = dataUrl && dataUrl.length > 100 ? 'Đã ký' : 'Chưa ký';
            this.violationSignatureOpen = false;
        },

        violationClearSignature() {
            this.form.signature_base64 = '';
            this.form.signed = 'Chưa ký';
        },

        violationOnModalOpen() {
            this.violationBuildStudentMap();
            this.violationSearchStatus = 'idle';
            this.violationStudentNotInSystem = false;
            this.violationResetCompare();
            this.violationResolveStudentState();
            if (this.form.class) {
                this.violationEnsureSelectOption('class', this.form.class);
            }
            if (this.form.department) {
                this.violationEnsureSelectOption('department', this.form.department);
            }
        },

        violationOnModalClose() {
            this.violationCloseScanner();
            this.violationClosePhotoCamera();
            this.violationSignatureOpen = false;
        },

        violationPrepareSave() {
            this.violationBuildStudentMap();
            this.violationResolveStudentState();

            const code = this.violationStudentCode();
            if (!String(this.form.full_name || '').trim()) {
                this.showToast?.('Vui lòng nhập họ tên sinh viên.', 'error');
                return false;
            }

            if (this.violationStudentNotInSystem && !code) {
                this.showToast?.('Vui lòng nhập MSSV hoặc CCCD để thêm sinh viên mới.', 'error');
                return false;
            }

            return true;
        },

        violationShouldSyncStudent() {
            return this.violationStudentNotInSystem === true;
        },

        violationRegisterSyncedStudent(student) {
            if (!student?.id) {
                return;
            }

            const list = this.violationStudents || [];
            const exists = list.some((item) => String(item.id) === String(student.id));
            if (!exists) {
                this.violationStudents = [...list, student];
            }
            this.violationBuildStudentMap();
            this.violationStudentInSystem = true;
            this.violationStudentNotInSystem = false;
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => {
                if (this.toast?.message === message) this.toast = null;
            }, 3500);
        },
    };
}
