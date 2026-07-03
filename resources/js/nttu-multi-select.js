export function normalizeMultiOption(opt) {
    if (opt && typeof opt === 'object' && opt.value != null) {
        return {
            value: String(opt.value),
            label: String(opt.label ?? opt.value),
        };
    }

    const value = String(opt ?? '');
    return { value, label: value };
}

export function createNttuMultiSelectMixin(config = {}) {
    const {
        valuesKey = 'advancedFilters',
        resolveOptions,
        getSelectedValues,
        setSelectedValues,
        onChange,
    } = config;

    return {
        multiSelectOpenField: null,
        multiSelectSearch: {},
        multiSelectCustomOptions: {},

        nttuMultiValues(field) {
            if (typeof getSelectedValues === 'function') {
                return getSelectedValues.call(this, field) || [];
            }

            const store = this[valuesKey];
            return store?.[field] || [];
        },

        _nttuMultiSetValues(field, values) {
            if (typeof setSelectedValues === 'function') {
                setSelectedValues.call(this, field, values);
            } else if (valuesKey === 'advanced') {
                this.advanced = { ...this.advanced, [field]: values };
            } else {
                this.advancedFilters = { ...this.advancedFilters, [field]: values };
            }

            if (typeof onChange === 'function') {
                onChange.call(this, field);
            }
        },

        nttuMultiIsOpen(field) {
            return this.multiSelectOpenField === field;
        },

        nttuMultiToggleOpen(field) {
            this.multiSelectOpenField = this.multiSelectOpenField === field ? null : field;
        },

        nttuMultiClose(field) {
            if (this.multiSelectOpenField === field) {
                this.multiSelectOpenField = null;
            }
        },

        nttuMultiCloseAll() {
            this.multiSelectOpenField = null;
        },

        nttuMultiAllOptions(field) {
            const base = (resolveOptions?.call(this, field) || []).map(normalizeMultiOption);
            const custom = (this.multiSelectCustomOptions[field] || []).map(normalizeMultiOption);
            const seen = new Set();
            const merged = [];

            [...base, ...custom].forEach((option) => {
                if (!option.value || seen.has(option.value)) {
                    return;
                }
                seen.add(option.value);
                merged.push(option);
            });

            return merged;
        },

        nttuMultiFilteredOptions(field) {
            const query = String(this.multiSelectSearch[field] || '').toLowerCase().trim();
            const options = this.nttuMultiAllOptions(field);
            if (!query) {
                return options;
            }

            return options.filter(
                (option) =>
                    option.label.toLowerCase().includes(query) ||
                    option.value.toLowerCase().includes(query)
            );
        },

        nttuMultiSearchText(field) {
            return String(this.multiSelectSearch[field] || '').trim();
        },

        nttuMultiCanAdd(field) {
            const query = this.nttuMultiSearchText(field);
            if (!query) {
                return false;
            }

            const lower = query.toLowerCase();
            return !this.nttuMultiAllOptions(field).some(
                (option) =>
                    option.label.toLowerCase() === lower || option.value.toLowerCase() === lower
            );
        },

        nttuMultiAddCustom(field) {
            const query = this.nttuMultiSearchText(field);
            if (!query || !this.nttuMultiCanAdd(field)) {
                return;
            }

            const customs = this.multiSelectCustomOptions[field] || [];
            this.multiSelectCustomOptions = {
                ...this.multiSelectCustomOptions,
                [field]: [...customs, { value: query, label: query }],
            };
            this.nttuMultiToggle(field, query);
            this.multiSelectSearch = { ...this.multiSelectSearch, [field]: '' };
        },

        nttuMultiOnSearchKeydown(field, event) {
            if (event.key === 'Enter' && this.nttuMultiCanAdd(field)) {
                event.preventDefault();
                this.nttuMultiAddCustom(field);
            }
        },

        nttuMultiIsSelected(field, value) {
            return this.nttuMultiValues(field).includes(value);
        },

        nttuMultiToggle(field, value) {
            const current = this.nttuMultiValues(field);
            const next = current.includes(value)
                ? current.filter((item) => item !== value)
                : [...current, value];
            this._nttuMultiSetValues(field, next);
        },

        nttuMultiRemove(field, value, event) {
            event?.stopPropagation();
            this.nttuMultiToggle(field, value);
        },

        nttuMultiClear(field) {
            this._nttuMultiSetValues(field, []);
        },

        nttuMultiLabel(field, value) {
            return this.nttuMultiAllOptions(field).find((option) => option.value === value)?.label || value;
        },
    };
}

const MS_GAP = 6;
const MS_PAD = 8;

function msTrigger(field) {
    return document.querySelector(`[data-ms-trigger="${CSS.escape(field)}"]`);
}

function msMeasurePanel(panel) {
    const previous = {
        position: panel.style.position,
        visibility: panel.style.visibility,
        display: panel.style.display,
        top: panel.style.top,
        left: panel.style.left,
    };

    panel.style.position = 'fixed';
    panel.style.visibility = 'hidden';
    panel.style.display = 'block';
    panel.style.top = '0';
    panel.style.left = '0';

    const size = { width: panel.offsetWidth, height: panel.offsetHeight };

    panel.style.position = previous.position;
    panel.style.visibility = previous.visibility;
    panel.style.display = previous.display;
    panel.style.top = previous.top;
    panel.style.left = previous.left;

    return size;
}

function msPositionPanel(panel, trigger) {
    const triggerRect = trigger.getBoundingClientRect();
    const { width: panelWidth, height: panelHeight } = msMeasurePanel(panel);

    let top = triggerRect.bottom + MS_GAP;
    let left = triggerRect.left;

    if (top + panelHeight > window.innerHeight - MS_PAD) {
        const above = triggerRect.top - panelHeight - MS_GAP;
        if (above >= MS_PAD || above > top) {
            top = above;
        }
    }

    if (left + panelWidth > window.innerWidth - MS_PAD) {
        left = window.innerWidth - panelWidth - MS_PAD;
    }
    if (left < MS_PAD) {
        left = MS_PAD;
    }

    top = Math.max(MS_PAD, Math.min(top, window.innerHeight - panelHeight - MS_PAD));

    const minWidth = Number.parseInt(panel.style.minWidth, 10) || 280;

    panel.style.position = 'fixed';
    panel.style.zIndex = '9999';
    panel.style.top = `${Math.round(top)}px`;
    panel.style.left = `${Math.round(left)}px`;
    panel.style.width = `${Math.max(Math.round(triggerRect.width), minWidth)}px`;
    panel.style.margin = '0';
}

export function registerNttuMultiSelectPanel(Alpine) {
    Alpine.directive('nttu-multi-panel', (el, { expression }, { effect, evaluate, cleanup }) => {
        const field = String(expression ?? '').replace(/^['"]|['"]$/g, '');
        let scrollHandler = null;
        let resizeHandler = null;
        let dismissHandler = null;
        let dismissTimer = null;
        let rafIds = [];

        const isOpen = () => {
            try {
                return evaluate(`multiSelectOpenField === ${JSON.stringify(field)}`);
            } catch {
                return false;
            }
        };

        const clearRafs = () => {
            rafIds.forEach((id) => window.cancelAnimationFrame(id));
            rafIds = [];
        };

        const reposition = () => {
            if (!isOpen()) {
                return;
            }

            const trigger = msTrigger(field);
            if (!trigger) {
                return;
            }

            msPositionPanel(el, trigger);
        };

        const scheduleReposition = () => {
            clearRafs();
            reposition();
            rafIds.push(requestAnimationFrame(() => {
                reposition();
                rafIds.push(requestAnimationFrame(reposition));
            }));
        };

        const unbindScroll = () => {
            if (scrollHandler) {
                window.removeEventListener('scroll', scrollHandler, true);
                scrollHandler = null;
            }
            if (resizeHandler) {
                window.removeEventListener('resize', resizeHandler);
                resizeHandler = null;
            }
        };

        const bindScroll = () => {
            if (scrollHandler) {
                return;
            }

            scrollHandler = () => reposition();
            resizeHandler = () => reposition();
            window.addEventListener('scroll', scrollHandler, true);
            window.addEventListener('resize', resizeHandler);
        };

        const unbindDismiss = () => {
            if (dismissTimer) {
                window.clearTimeout(dismissTimer);
                dismissTimer = null;
            }
            if (dismissHandler) {
                document.removeEventListener('pointerdown', dismissHandler, true);
                dismissHandler = null;
            }
        };

        const bindDismiss = () => {
            unbindDismiss();
            dismissTimer = window.setTimeout(() => {
                dismissTimer = null;
                if (!isOpen()) {
                    return;
                }

                dismissHandler = (event) => {
                    const trigger = msTrigger(field);
                    if (el.contains(event.target) || trigger?.contains(event.target)) {
                        return;
                    }

                    if (trigger) {
                        Alpine.evaluate(trigger, `nttuMultiClose(${JSON.stringify(field)})`);
                    }
                };

                document.addEventListener('pointerdown', dismissHandler, true);
            }, 0);
        };

        const unbindAll = () => {
            clearRafs();
            unbindScroll();
            unbindDismiss();
        };

        effect(() => {
            if (isOpen()) {
                bindScroll();
                scheduleReposition();
                bindDismiss();
            } else {
                unbindAll();
            }
        });

        cleanup(() => {
            unbindAll();
        });
    });
}
