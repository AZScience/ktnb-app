/**
 * Global Table Row Selection Filter Enhancer for KTNB
 * Automatically injects & manages the Selection Filter (All / Selected / Unselected)
 * in the '#' index column of EVERY data table across the entire application.
 */

function getTableContext(th) {
    if (!window.Alpine) return null;
    let data = null;
    try {
        data = window.Alpine.$data(th);
    } catch (e) {
        return null;
    }
    if (!data) return null;

    // Daily Report Viewer (Tab-based with stateFor)
    if (typeof data.stateFor === 'function' && data.tab) {
        const tabKey = data.tab;
        const state = data.stateFor(tabKey);
        return {
            type: 'daily-report',
            component: data,
            getSelected: () => state.selectedIds || [],
            setSelected: (ids) => {
                state.selectedIds = ids;
                data.persistState?.(tabKey);
            },
            getSelectionFilter: () => state.selectionFilter || 'all',
            setSelectionFilter: (mode) => {
                if (typeof data.setSelectionFilter === 'function') {
                    data.setSelectionFilter(tabKey, mode);
                } else {
                    state.selectionFilter = mode;
                    state.currentPage = 1;
                    data.persistState?.(tabKey);
                }
            },
            toggleCurrentPage: () => {
                if (typeof data.toggleSelectAllCurrentPage === 'function') {
                    data.toggleSelectAllCurrentPage(tabKey);
                } else {
                    const pageIds = (data.currentItems?.(tabKey) || []).map((i) => i.id);
                    const sel = state.selectedIds || [];
                    const allSel = pageIds.length > 0 && pageIds.every((id) => sel.includes(id));
                    state.selectedIds = allSel
                        ? sel.filter((id) => !pageIds.includes(id))
                        : Array.from(new Set([...sel, ...pageIds]));
                    data.persistState?.(tabKey);
                }
            },
            clearAll: () => {
                if (typeof data.clearSelection === 'function') {
                    data.clearSelection(tabKey);
                } else {
                    state.selectedIds = [];
                    state.selectionFilter = 'all';
                    data.persistState?.(tabKey);
                }
            },
        };
    }

    // Interactive Report
    if (data.activeTab !== undefined && (data.selectedRowIds !== undefined || typeof data.setSelectionFilter === 'function')) {
        return {
            type: 'interactive-report',
            component: data,
            getSelected: () => data.selectedRowIds || [],
            setSelected: (ids) => {
                data.selectedRowIds = ids;
            },
            getSelectionFilter: () => data.selectionFilter || 'all',
            setSelectionFilter: (mode) => {
                if (typeof data.setSelectionFilter === 'function') {
                    data.setSelectionFilter(mode);
                } else {
                    data.selectionFilter = mode;
                    data.currentPage = 1;
                }
            },
            toggleCurrentPage: () => {
                if (typeof data.toggleSelectAllCurrentPage === 'function') {
                    data.toggleSelectAllCurrentPage();
                } else {
                    const pageIds = (data.pagedRows || []).map((i) => i.id);
                    const sel = data.selectedRowIds || [];
                    const allSel = pageIds.length > 0 && pageIds.every((id) => sel.includes(id));
                    data.selectedRowIds = allSel
                        ? sel.filter((id) => !pageIds.includes(id))
                        : Array.from(new Set([...sel, ...pageIds]));
                }
            },
            clearAll: () => {
                data.selectedRowIds = [];
                if (typeof data.setSelectionFilter === 'function') {
                    data.setSelectionFilter('all');
                } else {
                    data.selectionFilter = 'all';
                }
            },
        };
    }

    // Catalog Table Page (selectedIds)
    if (data.selectedIds !== undefined) {
        return {
            type: 'catalog',
            component: data,
            getSelected: () => data.selectedIds || [],
            setSelected: (ids) => {
                data.selectedIds = ids;
                try {
                    const key = `${data.storageKey || 'catalog'}_selected`;
                    localStorage.setItem(key, JSON.stringify(ids));
                } catch (e) {}
            },
            getSelectionFilter: () => data.selectionFilter || 'all',
            setSelectionFilter: (mode) => {
                if (typeof data.setSelectionFilter === 'function') {
                    data.setSelectionFilter(mode);
                } else {
                    data.selectionFilter = mode;
                    if (data.safePage !== undefined) data.safePage = 1;
                    if (data.currentPage !== undefined) data.currentPage = 1;
                }
            },
            toggleCurrentPage: () => {
                if (typeof data.toggleSelectAllCurrentPage === 'function') {
                    data.toggleSelectAllCurrentPage();
                } else {
                    const pageIds = (data.currentItems || data.items || []).map((i) => i.id);
                    const sel = data.selectedIds || [];
                    const allSel = pageIds.length > 0 && pageIds.every((id) => sel.includes(id));
                    data.selectedIds = allSel
                        ? sel.filter((id) => !pageIds.includes(id))
                        : Array.from(new Set([...sel, ...pageIds]));
                }
            },
            clearAll: () => {
                data.selectedIds = [];
                if (typeof data.setSelectionFilter === 'function') {
                    data.setSelectionFilter('all');
                } else {
                    data.selectionFilter = 'all';
                }
            },
        };
    }

    // Monitoring Schedules, Daily Schedule Settings, etc. (selectedRowIds)
    if (data.selectedRowIds !== undefined) {
        return {
            type: 'standard',
            component: data,
            getSelected: () => data.selectedRowIds || [],
            setSelected: (ids) => {
                data.selectedRowIds = ids;
            },
            getSelectionFilter: () => data.selectionFilter || 'all',
            setSelectionFilter: (mode) => {
                if (typeof data.setSelectionFilter === 'function') {
                    data.setSelectionFilter(mode);
                } else {
                    data.selectionFilter = mode;
                    if (data.currentPage !== undefined) data.currentPage = 1;
                }
            },
            toggleCurrentPage: () => {
                if (typeof data.toggleSelectAllCurrentPage === 'function') {
                    data.toggleSelectAllCurrentPage();
                } else {
                    const pageIds = (data.paginatedItems || data.pagedItems || data.currentItems || []).map((i) => i.id);
                    const sel = data.selectedRowIds || [];
                    const allSel = pageIds.length > 0 && pageIds.every((id) => sel.includes(id));
                    data.selectedRowIds = allSel
                        ? sel.filter((id) => !pageIds.includes(id))
                        : Array.from(new Set([...sel, ...pageIds]));
                }
            },
            clearAll: () => {
                data.selectedRowIds = [];
                if (typeof data.setSelectionFilter === 'function') {
                    data.setSelectionFilter('all');
                } else {
                    data.selectionFilter = 'all';
                }
            },
        };
    }

    return null;
}

function getHeaderSafeTop() {
    const headerEl = document.querySelector('header.sticky, .nttu-header, header');
    if (headerEl) {
        const rect = headerEl.getBoundingClientRect();
        return Math.max(64, Math.round(rect.bottom));
    }
    return 64;
}

function positionFixedPopover(popover, trigger) {
    const PAD = 8;
    const GAP = 4;
    const safeTop = getHeaderSafeTop() + PAD;
    const triggerRect = trigger.getBoundingClientRect();

    if (popover.parentElement !== document.body) {
        popover._origParent = popover.parentElement;
        popover._origNextSibling = popover.nextSibling;
        document.body.appendChild(popover);
    }

    popover.style.position = 'fixed';
    popover.style.zIndex = '10060';
    popover.style.margin = '0';
    popover.style.right = 'auto';
    popover.style.bottom = 'auto';
    popover.style.visibility = 'hidden';
    popover.classList.remove('hidden');

    const popoverWidth = popover.offsetWidth || 224;
    const popoverHeight = popover.offsetHeight || 230;

    let left = triggerRect.left;
    if (left + popoverWidth > window.innerWidth - PAD) {
        left = window.innerWidth - popoverWidth - PAD;
    }
    if (left < PAD) left = PAD;

    const availableBelow = window.innerHeight - (triggerRect.bottom + GAP) - PAD;
    const availableAbove = triggerRect.top - GAP - safeTop;

    let top;
    let maxHeight;

    if (availableBelow >= popoverHeight || (availableBelow >= availableAbove && availableBelow >= 120)) {
        top = triggerRect.bottom + GAP;
        maxHeight = Math.max(120, availableBelow);
    } else {
        maxHeight = Math.max(120, availableAbove);
        const actualH = Math.min(popoverHeight, maxHeight);
        top = Math.max(safeTop, triggerRect.top - GAP - actualH);
    }

    popover.style.top = `${Math.round(top)}px`;
    popover.style.left = `${Math.round(left)}px`;
    popover.style.maxHeight = `${Math.floor(maxHeight)}px`;
    popover.style.overflowY = 'auto';
    popover.style.visibility = 'visible';
}

function hideFixedPopover(popover) {
    if (!popover) return;
    popover.classList.add('hidden');
    popover.style.position = '';
    popover.style.top = '';
    popover.style.left = '';
    popover.style.maxHeight = '';
    popover.style.overflowY = '';
    popover.style.visibility = '';
}

export function enhanceTableHeader(th) {
    if (!th || th.querySelector('.btn-selection-filter')) return;

    const ctx = getTableContext(th);
    if (!ctx) return;

    th.classList.add('relative', 'p-0');
    th.innerHTML = `
        <div class="relative flex items-center justify-center h-full selection-filter-container">
            <button type="button" class="btn-selection-filter flex items-center justify-center gap-1 w-full h-full py-2 px-1 hover:bg-black/10 transition-colors cursor-pointer select-none" title="Lọc theo trạng thái chọn">
                <span class="font-bold">#</span>
                <svg class="h-3 w-3 shrink-0 opacity-70 filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span class="selection-filter-badge hidden text-[10px] font-black px-1.5 py-0.5 rounded-full bg-white text-red-600 shadow-sm"></span>
            </button>
            <div class="selection-filter-popover hidden absolute top-full left-0 z-50 w-56 rounded-md border bg-white p-1 text-gray-800 shadow-xl text-left font-normal text-xs">
                <div class="px-2 py-1.5 font-bold text-gray-700 border-b flex items-center justify-between">
                    <span>Lọc dòng:</span>
                    <span class="text-[10px] font-normal text-gray-500 sel-count"></span>
                </div>
                <div class="py-1 space-y-0.5">
                    <button type="button" class="btn-sel-all flex w-full items-center justify-between px-2 py-1.5 rounded hover:bg-gray-100">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            Tất cả dòng
                        </span>
                    </button>
                    <button type="button" class="btn-sel-selected flex w-full items-center justify-between px-2 py-1.5 rounded hover:bg-gray-100">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Chỉ dòng đã chọn
                        </span>
                        <span class="sel-num text-xs text-gray-500"></span>
                    </button>
                    <button type="button" class="btn-sel-unselected flex w-full items-center justify-between px-2 py-1.5 rounded hover:bg-gray-100">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Chỉ dòng chưa chọn
                        </span>
                    </button>
                </div>
                <div class="border-t pt-1 mt-1 space-y-0.5">
                    <button type="button" class="btn-toggle-page flex w-full items-center gap-2 px-2 py-1 rounded text-gray-600 hover:bg-gray-100">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Chọn/Bỏ chọn tất cả</span>
                    </button>
                    <button type="button" class="btn-clear-sel flex w-full items-center gap-2 px-2 py-1 rounded text-red-600 hover:bg-red-50">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Bỏ chọn tất cả</span>
                    </button>
                </div>
            </div>
        </div>
    `;

    const btn = th.querySelector('.btn-selection-filter');
    const popover = th.querySelector('.selection-filter-popover');
    const badge = th.querySelector('.selection-filter-badge');

    function syncBadge() {
        const liveCtx = getTableContext(th);
        if (!liveCtx) return;
        const filter = liveCtx.getSelectionFilter();
        const selected = liveCtx.getSelected();

        if (filter === 'selected') {
            badge.textContent = `(${selected.length})`;
            badge.classList.remove('hidden');
            th.classList.add('bg-blue-600', 'text-white');
        } else if (filter === 'unselected') {
            badge.textContent = '(Chưa)';
            badge.classList.remove('hidden');
            th.classList.add('bg-blue-600', 'text-white');
        } else {
            badge.classList.add('hidden');
            th.classList.remove('bg-blue-600', 'text-white');
        }
    }

    if (btn && popover) {
        btn.onclick = (e) => {
            e.stopPropagation();
            const liveCtx = getTableContext(th);
            if (!liveCtx) return;

            const isHidden = popover.classList.contains('hidden');
            document.querySelectorAll('.selection-filter-popover').forEach(hideFixedPopover);

            if (isHidden) {
                const selected = liveCtx.getSelected();
                popover.querySelector('.sel-count').textContent = `Đã chọn: ${selected.length}`;
                popover.querySelector('.sel-num').textContent = `(${selected.length})`;
                positionFixedPopover(popover, btn);
            }
        };

        popover.querySelector('.btn-sel-all').onclick = (e) => {
            e.stopPropagation();
            const liveCtx = getTableContext(th);
            liveCtx?.setSelectionFilter('all');
            syncBadge();
            hideFixedPopover(popover);
        };

        popover.querySelector('.btn-sel-selected').onclick = (e) => {
            e.stopPropagation();
            const liveCtx = getTableContext(th);
            liveCtx?.setSelectionFilter('selected');
            syncBadge();
            hideFixedPopover(popover);
        };

        popover.querySelector('.btn-sel-unselected').onclick = (e) => {
            e.stopPropagation();
            const liveCtx = getTableContext(th);
            liveCtx?.setSelectionFilter('unselected');
            syncBadge();
            hideFixedPopover(popover);
        };

        popover.querySelector('.btn-toggle-page').onclick = (e) => {
            e.stopPropagation();
            const liveCtx = getTableContext(th);
            liveCtx?.toggleCurrentPage();
            syncBadge();
            hideFixedPopover(popover);
        };

        popover.querySelector('.btn-clear-sel').onclick = (e) => {
            e.stopPropagation();
            const liveCtx = getTableContext(th);
            liveCtx?.clearAll();
            syncBadge();
            hideFixedPopover(popover);
        };

        const handleOutside = (e) => {
            if (!th.contains(e.target) && !popover.contains(e.target)) {
                hideFixedPopover(popover);
            }
        };

        document.addEventListener('click', handleOutside, true);
        document.addEventListener('pointerdown', handleOutside, true);

        const handleScrollOrResize = () => {
            if (!popover.classList.contains('hidden')) {
                hideFixedPopover(popover);
            }
        };

        window.addEventListener('scroll', handleScrollOrResize, true);
        window.addEventListener('resize', handleScrollOrResize);
        window.addEventListener('orientationchange', handleScrollOrResize);
    }

    syncBadge();
}

export function scanAndEnhanceAllTables() {
    const selector = 'th.catalog-th-index, th.nttu-th-index, th.daily-report-th.nttu-th-index';
    const ths = document.querySelectorAll(selector);
    ths.forEach((th) => {
        try {
            enhanceTableHeader(th);
        } catch (e) {
            console.warn('[ktnb] Error enhancing table header:', e);
        }
    });

    // Check in-person and online schedule page checkboxes
    const rootMonitoring = document.querySelector('[x-data*="monitoringSchedulesPage"]');
    if (rootMonitoring && window.Alpine) {
        try {
            const data = window.Alpine.$data(rootMonitoring);
            if (data && (data.module === 'in-person' || data.module === 'online') && typeof data.ensureIncludeFilterCheckboxes === 'function') {
                data.ensureIncludeFilterCheckboxes();
            }
        } catch (e) {}
    }

    // Purge any redundant camera flip buttons in select box (leaving floating overlay intact)
    document.querySelectorAll('.btn-toggle-cam').forEach((el) => el.remove());

    // Ensure camera selection UI is present whenever evidence camera panel is rendered
    document.querySelectorAll('.aspect-video').forEach((aspectVideo) => {
        try {
            const panel = aspectVideo.closest('[x-data]');
            if (panel && window.Alpine) {
                const pData = window.Alpine.$data(panel);
                if (pData && typeof pData.ensureCameraSelectorUI === 'function') {
                    pData.ensureCameraSelectorUI();
                }
            }
        } catch (e) {}
    });
}

export function registerGlobalTableSelection(Alpine) {
    if (typeof window === 'undefined') return;

    // Run on initial DOM readiness
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(scanAndEnhanceAllTables, 150);
            setTimeout(scanAndEnhanceAllTables, 600);
        });
    } else {
        setTimeout(scanAndEnhanceAllTables, 150);
        setTimeout(scanAndEnhanceAllTables, 600);
    }

    // Observe DOM mutations so tables loaded via AJAX, tab switching, or Alpine dynamic rendering are enhanced immediately
    const observer = new MutationObserver(() => {
        scanAndEnhanceAllTables();
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    // Also scan periodically as a fallback
    setInterval(scanAndEnhanceAllTables, 1500);
}
