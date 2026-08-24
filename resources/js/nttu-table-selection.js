export function createTableSelectionState({ load, save, storageKey = 'selected' }) {
    const persist = (ids) => save(storageKey, ids);

    return {
        selectedIds: load(storageKey, []),

        isSelected(id) {
            return this.selectedIds.includes(id);
        },

        toggleRow(id) {
            const set = new Set(this.selectedIds);
            if (set.has(id)) {
                set.delete(id);
            } else {
                set.add(id);
            }
            this.selectedIds = [...set];
            persist(this.selectedIds);
        },

        forgetSelectedId(id) {
            this.selectedIds = this.selectedIds.filter((selectedId) => selectedId !== id);
            persist(this.selectedIds);
        },

        replaceSelectedId(oldId, newId) {
            if (oldId === newId) {
                return;
            }

            this.selectedIds = this.selectedIds.map((selectedId) => (selectedId === oldId ? newId : selectedId));
            persist(this.selectedIds);
        },
    };
}
