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
    };
}

export function serviceRequestFormMethods() {
    return {
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
