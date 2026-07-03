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
    };
}

export function assetReceptionFormMethods() {
    return {
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
