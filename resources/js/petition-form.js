export function petitionFormState() {
    return {
        ptSections: { citizen: true, content: true },
    };
}

export function petitionFormMethods() {
    return {
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
