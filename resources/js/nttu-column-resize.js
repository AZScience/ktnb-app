const STORAGE_PREFIX = 'nttu_col_widths_';
const EDGE_HIT_PX = 12;
const MIN_COL_WIDTH = 72;
const MAX_COL_WIDTH = 640;
const FIXED_INDEX_WIDTH = 48;
const FIXED_SETTINGS_WIDTH = 64;
const HEADER_MEASURE_PAD = 32;

/** @type {Record<string, number>} */
const DEFAULT_WIDTHS = {
    period: 88,
    avatar_url: 96,
    id: 128,
    employee_id: 104,
    department_id: 120,
    code: 112,
    name: 168,
    gender: 104,
    birth_date: 112,
    birth_place: 112,
    class: 88,
    phone: 120,
    citizen_id: 148,
    department: 104,
    department_name: 128,
    position_name: 120,
    role_name: 112,
    nickname: 112,
    email: 112,
    address: 128,
    permanent_address: 128,
    temporary_address: 112,
    contact_address: 112,
    note: 128,
    head: 144,
    deputy_head: 132,
    secretary: 112,
    spokesperson: 144,
    recognition_name: 152,
    recognition_id: 152,
    hometown: 128,
    ethnicity: 104,
    religion: 112,
    major: 96,
    region: 96,
    father_name: 112,
    father_occupation: 156,
    mother_name: 112,
    mother_occupation: 156,
    parent_phone: 132,
    position: 112,
    title: 128,
    building_block_name: 132,
    room_type: 120,
    seating_capacity: 132,
    table_count: 104,
    exam_capacity: 120,
    subject_nature: 128,
    has_projector: 128,
    is_inactive: 128,
    officer: 148,
    building: 96,
    cup_tiet: 88,
    di_hoc_tre: 96,
    dong_phuc: 200,
    khong_the: 120,
    mat_trat_tu: 96,
    hut_thuoc: 220,
    lam_rieng: 160,
    vo_le: 128,
    hung_khi: 160,
    gay_go: 120,
    dua_nguoi: 200,
    danh_bai: 88,
    khac: 120,
};

function isCrosstabTable(table) {
    return table.classList.contains('daily-report-crosstab');
}

function crosstabColKeysOrdered(table) {
    const keys = ['__index__'];

    table.querySelectorAll('thead tr:first-child th[data-col][rowspan]').forEach((th) => {
        if (th.dataset.col) {
            keys.push(th.dataset.col);
        }
    });
    table.querySelectorAll('thead tr:nth-child(2) th[data-col]').forEach((th) => {
        if (th.dataset.col) {
            keys.push(th.dataset.col);
        }
    });
    if (table.querySelector('thead th.catalog-th-settings') !== null) {
        keys.push('__settings__');
    }

    return keys;
}

function crosstabLogicalColumnCount(table) {
    return crosstabColKeysOrdered(table).length;
}

function crosstabColumnIndexForKey(table, colKey) {
    const idx = crosstabColKeysOrdered(table).indexOf(colKey);

    return idx >= 0 ? idx : -1;
}

function resolveHeaderThAtLogicalIndex(table, colIndex) {
    if (!isCrosstabTable(table)) {
        return headerCells(table)[colIndex] || null;
    }

    const keys = crosstabColKeysOrdered(table);
    const key = keys[colIndex];
    if (!key) {
        return null;
    }
    if (key === '__index__') {
        return table.querySelector('thead th.nttu-th-index, thead th.catalog-th-index');
    }
    if (key === '__settings__') {
        return table.querySelector('thead th.catalog-th-settings');
    }

    return table.querySelector(`thead th[data-col="${CSS.escape(key)}"]`);
}

function isIndexHeader(th) {
    return th.classList.contains('catalog-th-index') || th.classList.contains('nttu-th-index');
}

function isSettingsHeader(th) {
    return th.classList.contains('catalog-th-settings');
}

function isFixedHeader(th) {
    return isIndexHeader(th) || isSettingsHeader(th);
}

function fixedColumnIndex(table, kind) {
    if (isCrosstabTable(table)) {
        if (kind === 'index') {
            return 0;
        }
        if (kind === 'settings' && hasSettingsColumn(table)) {
            return crosstabLogicalColumnCount(table) - 1;
        }

        return -1;
    }

    return headerCells(table).findIndex((th) => (
        kind === 'index' ? isIndexHeader(th) : isSettingsHeader(th)
    ));
}

function hasSettingsColumn(table) {
    return table.querySelector('thead th.catalog-th-settings') !== null;
}

function isSpacerHeader(th) {
    return th?.classList.contains('catalog-th-spacer');
}

function removeSpacerColumns(table) {
    table.querySelectorAll('thead th.catalog-th-spacer').forEach((el) => el.remove());
    table.querySelectorAll('tbody td.catalog-td-spacer').forEach((el) => el.remove());
}

function lastDataColumnIndex(table) {
    if (isCrosstabTable(table)) {
        const violationThs = [...table.querySelectorAll('thead tr:nth-child(2) th[data-col]')];
        if (violationThs.length) {
            return crosstabColumnIndexForKey(table, violationThs[violationThs.length - 1].dataset.col);
        }

        const fixedThs = [...table.querySelectorAll('thead tr:first-child th[data-col][rowspan]')];
        if (fixedThs.length) {
            return crosstabColumnIndexForKey(table, fixedThs[fixedThs.length - 1].dataset.col);
        }

        return 0;
    }

    const headers = headerCells(table);
    const settingsIdx = fixedColumnIndex(table, 'settings');
    const lastIdx = settingsIdx >= 0 ? settingsIdx - 1 : headers.length - 1;

    for (let i = lastIdx; i >= 0; i -= 1) {
        const th = headers[i];
        if (!th || isFixedHeader(th) || isSpacerHeader(th)) {
            continue;
        }
        if (th.dataset.col || th.classList.contains('catalog-th-col')) {
            return i;
        }
    }

    return -1;
}

function columnWidthAtIndex(table, colIndex) {
    const colgroup = table.querySelector('colgroup[data-nttu-resize]');
    if (colgroup?.children[colIndex]) {
        const fromStyle = parseInt(colgroup.children[colIndex].style.width, 10);
        if (Number.isFinite(fromStyle) && fromStyle > 0) {
            return fromStyle;
        }
    }

    const th = resolveHeaderThAtLogicalIndex(table, colIndex);
    return th ? Math.ceil(th.getBoundingClientRect().width) : 0;
}

function applyColumnWidthAtIndex(table, colIndex, widthPx) {
    const width = Math.max(0, Math.round(widthPx));
    const colgroup = ensureColgroup(table);

    if (colgroup?.children[colIndex]) {
        const col = colgroup.children[colIndex];
        col.style.width = `${width}px`;
        col.style.minWidth = `${width}px`;
        col.style.maxWidth = `${width}px`;
    }

    const th = resolveHeaderThAtLogicalIndex(table, colIndex);
    if (th) {
        th.style.width = `${width}px`;
        th.style.minWidth = `${width}px`;
        th.style.maxWidth = `${width}px`;
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cell = row.children[colIndex];
        if (!cell || cell.tagName !== 'TD') {
            return;
        }
        cell.style.width = `${width}px`;
        cell.style.minWidth = `${width}px`;
        cell.style.maxWidth = `${width}px`;
    });
}

function fillRemainingWidthBeforeSettings(table) {
    if (!hasSettingsColumn(table)) {
        return;
    }

    removeSpacerColumns(table);
    ensureColgroup(table);
    applyFixedColumns(table);

    const available = containerWidthForTable(table);
    if (available <= 0) {
        return;
    }

    const total = sumColumnWidths(table);
    const remainder = available - total;
    if (remainder <= 4) {
        return;
    }

    const lastIdx = lastDataColumnIndex(table);
    if (lastIdx < 0) {
        return;
    }

    const th = headerCells(table)[lastIdx];
    const colKey = th.dataset.col || '';
    const next = columnWidthAtIndex(table, lastIdx) + remainder;

    if (colKey) {
        applyColumnWidth(table, colKey, next);
    } else {
        applyColumnWidthAtIndex(table, lastIdx, next);
    }

    applyFixedColumns(table);
}

function layoutSettingsPinnedTable(table) {
    removeSpacerColumns(table);
    ensureColgroup(table);

    columnKeys(table).forEach((colKey) => {
        const measured = measureHeaderWidthForColumn(table, colKey);
        if (measured > 0) {
            applyColumnWidth(table, colKey, measured);
        }
    });

    applyFixedColumns(table);
    fillRemainingWidthBeforeSettings(table);

    const total = sumColumnWidths(table);
    const available = containerWidthForTable(table);
    const target = Math.max(total, available > 0 ? available : total);

    table.style.width = `${target}px`;
    table.style.minWidth = `${target}px`;
}

function loadWidths(storageKey) {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_PREFIX + storageKey) || '{}');
        delete saved._index;
        delete saved._settings;
        return { ...DEFAULT_WIDTHS, ...saved };
    } catch {
        return { ...DEFAULT_WIDTHS };
    }
}

function saveWidths(storageKey, widths) {
    localStorage.setItem(STORAGE_PREFIX + storageKey, JSON.stringify(widths));
}

function tableStorageKey(table) {
    return (table.getAttribute('data-col-resize') || '').trim();
}

function getTableWidths(table, storageKey) {
    if (!table._nttuColWidths) {
        table._nttuColWidths = loadWidths(storageKey);
    }
    return table._nttuColWidths;
}

function getLabelMinWidths(table) {
    if (!table._nttuLabelMinWidths) {
        table._nttuLabelMinWidths = {};
    }
    return table._nttuLabelMinWidths;
}

function headerCells(table) {
    return [...table.querySelectorAll('thead tr:first-child th')];
}

function columnIndexForKey(table, colKey) {
    if (isCrosstabTable(table)) {
        return crosstabColumnIndexForKey(table, colKey);
    }

    return headerCells(table).findIndex((th) => th.dataset.col === colKey);
}

function columnKeys(table) {
    const keys = new Set();
    table.querySelectorAll('thead th[data-col]').forEach((th) => {
        const key = th.dataset.col;
        if (key) {
            keys.add(key);
        }
    });
    return [...keys];
}

function resolveHeaderLabelText(th) {
    const fromDom = th.querySelector('.nttu-th-label-text')?.textContent?.trim();
    if (fromDom) {
        return fromDom;
    }

    return th.dataset.colLabel?.trim() || '';
}

function measureHeaderWidthForColumn(table, colKey) {
    const headers = table.querySelectorAll(`thead th[data-col="${colKey}"]`);
    if (!headers.length) {
        return 0;
    }

    let max = 0;
    headers.forEach((th) => {
        const labelText = resolveHeaderLabelText(th);
        const probe = th.cloneNode(true);
        probe.style.cssText = [
            'position:absolute',
            'left:-10000px',
            'top:0',
            'visibility:hidden',
            'width:auto',
            'max-width:none',
            'min-width:max-content',
            'white-space:nowrap',
            'height:auto',
        ].join(';');
        probe.querySelectorAll('.nttu-report-header-popover, .nttu-floating-panel, [x-float], [x-float\\.start]').forEach((el) => {
            el.remove();
        });
        document.body.appendChild(probe);
        const labelEl = probe.querySelector('.nttu-th-label-text');
        if (labelEl && labelText && !labelEl.textContent?.trim()) {
            labelEl.textContent = labelText;
        }
        const btn = probe.querySelector('.nttu-th-sort-btn, .nttu-th-label');
        if (btn) {
            const sortIcons = btn.querySelectorAll('svg.ml-1, svg.ml-2, svg.shrink-0');
            if (sortIcons.length > 1) {
                [...sortIcons].slice(1).forEach((el) => el.remove());
            }
        }
        max = Math.max(max, Math.ceil(probe.getBoundingClientRect().width));
        probe.remove();
    });

    return max > 0 ? max + HEADER_MEASURE_PAD : 0;
}

function ensureColgroup(table) {
    let colgroup = table.querySelector('colgroup[data-nttu-resize]');
    const count = isCrosstabTable(table)
        ? crosstabLogicalColumnCount(table)
        : headerCells(table).length;

    if (!colgroup) {
        colgroup = document.createElement('colgroup');
        colgroup.dataset.nttuResize = '1';
        table.insertBefore(colgroup, table.firstChild);
    }

    while (colgroup.children.length < count) {
        colgroup.appendChild(document.createElement('col'));
    }
    while (colgroup.children.length > count) {
        colgroup.removeChild(colgroup.lastChild);
    }

    if (isCrosstabTable(table)) {
        crosstabColKeysOrdered(table).forEach((key, index) => {
            const col = colgroup.children[index];
            if (!col) {
                return;
            }
            col.dataset.col = (key === '__index__' || key === '__settings__') ? '' : key;
        });
    } else {
        headerCells(table).forEach((th, index) => {
            const col = colgroup.children[index];
            if (!col) {
                return;
            }
            col.dataset.col = th.dataset.col || '';
        });
    }

    return colgroup;
}

function applyColumnWidth(table, colKey, widthPx) {
    const colIndex = columnIndexForKey(table, colKey);
    const colgroup = ensureColgroup(table);

    if (colIndex >= 0 && colgroup?.children[colIndex]) {
        colgroup.children[colIndex].style.width = `${widthPx}px`;
    }

    table.querySelectorAll(`[data-col="${colKey}"]`).forEach((cell) => {
        cell.style.width = `${widthPx}px`;
        cell.style.minWidth = `${widthPx}px`;
        cell.style.maxWidth = `${widthPx}px`;
        cell.classList.add('nttu-col-custom-width');
    });
}

function applyFixedColumnWidthByIndex(table, colIndex, widthPx, kind) {
    const colgroup = ensureColgroup(table);
    const fixedClass = kind === 'index' ? 'nttu-col-fixed-index' : 'nttu-col-fixed-settings';

    if (colIndex >= 0 && colgroup?.children[colIndex]) {
        const col = colgroup.children[colIndex];
        col.dataset.nttuFixed = kind;
        col.style.width = `${widthPx}px`;
        col.style.minWidth = `${widthPx}px`;
        col.style.maxWidth = `${widthPx}px`;
    }

    const th = resolveHeaderThAtLogicalIndex(table, colIndex);
    if (th) {
        th.style.width = `${widthPx}px`;
        th.style.minWidth = `${widthPx}px`;
        th.style.maxWidth = `${widthPx}px`;
        th.classList.add('nttu-col-fixed', fixedClass);
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cell = row.children[colIndex];
        if (!cell || cell.tagName !== 'TD') {
            return;
        }
        cell.style.width = `${widthPx}px`;
        cell.style.minWidth = `${widthPx}px`;
        cell.style.maxWidth = `${widthPx}px`;
        cell.classList.add('nttu-col-fixed', fixedClass);
    });
}

function applyFixedColumns(table) {
    const indexCol = fixedColumnIndex(table, 'index');
    if (indexCol >= 0) {
        applyFixedColumnWidthByIndex(table, indexCol, FIXED_INDEX_WIDTH, 'index');
    }

    const settingsCol = fixedColumnIndex(table, 'settings');
    if (settingsCol >= 0) {
        applyFixedColumnWidthByIndex(table, settingsCol, FIXED_SETTINGS_WIDTH, 'settings');
    }
}

function sumColumnWidths(table) {
    const colgroup = table.querySelector('colgroup[data-nttu-resize]');
    if (!colgroup) {
        return 0;
    }

    let total = 0;
    [...colgroup.children].forEach((col) => {
        const fromStyle = parseInt(col.style.width, 10);
        if (Number.isFinite(fromStyle) && fromStyle > 0) {
            total += fromStyle;
            return;
        }

        const thIndex = [...colgroup.children].indexOf(col);
        const th = resolveHeaderThAtLogicalIndex(table, thIndex);
        if (th) {
            total += Math.ceil(th.getBoundingClientRect().width);
        }
    });

    return total;
}

function containerWidthForTable(table) {
    const wrapper = table.parentElement;
    if (!wrapper) {
        return 0;
    }

    return Math.floor(wrapper.clientWidth);
}

function sumFixedColumnWidths(table) {
    let sum = 0;
    if (fixedColumnIndex(table, 'index') >= 0) {
        sum += FIXED_INDEX_WIDTH;
    }
    if (fixedColumnIndex(table, 'settings') >= 0) {
        sum += FIXED_SETTINGS_WIDTH;
    }
    return sum;
}

function distributeColumnsEvenly(table, keys, available) {
    applyFixedColumns(table);

    const dataBudget = Math.max(0, available - sumFixedColumnWidths(table));
    const count = keys.length;
    if (!count || !dataBudget) {
        return;
    }

    const labelMins = getLabelMinWidths(table);
    let each = Math.floor(dataBudget / count);
    let remainder = dataBudget - each * count;

    keys.forEach((colKey) => {
        const floor = labelMins[colKey] || DEFAULT_WIDTHS[colKey] || MIN_COL_WIDTH;
        let width = each + (remainder > 0 ? 1 : 0);
        if (remainder > 0) {
            remainder -= 1;
        }
        applyColumnWidth(table, colKey, Math.max(width, floor));
    });

    applyFixedColumns(table);
}

function expandTableToFillContainer(table, { resetBase = true, evenly = true } = {}) {
    const storageKey = tableStorageKey(table);
    const keys = columnKeys(table);

    if (resetBase && storageKey && keys.length) {
        const widths = getTableWidths(table, storageKey);
        applyAllWidths(table, widths);
        applyFixedColumns(table);
    }

    let total = sumColumnWidths(table);
    const available = containerWidthForTable(table);

    if (hasSettingsColumn(table)) {
        fillRemainingWidthBeforeSettings(table);
        total = sumColumnWidths(table);
    } else if (keys.length && available > total + 4 && evenly) {
        distributeColumnsEvenly(table, keys, available);
        total = sumColumnWidths(table);
    }

    if (total <= 0 && available <= 0) {
        return;
    }

    const target = Math.max(total, available > 0 ? available : total);
    table.style.width = `${target}px`;
    table.style.minWidth = `${target}px`;
}

function updateTableMinWidth(table) {
    expandTableToFillContainer(table);
}

function applyAllWidths(table, widths) {
    Object.entries(widths).forEach(([colKey, widthPx]) => {
        if (Number.isFinite(widthPx) && widthPx > 0) {
            applyColumnWidth(table, colKey, widthPx);
        }
    });
}

function fitColumnsToHeaderLabels(table) {
    const storageKey = tableStorageKey(table);
    if (!storageKey) {
        return;
    }

    const labelMins = getLabelMinWidths(table);
    const widths = getTableWidths(table, storageKey);

    columnKeys(table).forEach((colKey) => {
        const measured = measureHeaderWidthForColumn(table, colKey);
        if (measured > 0) {
            labelMins[colKey] = measured;
        }

        const labelMin = labelMins[colKey] || DEFAULT_WIDTHS[colKey] || MIN_COL_WIDTH;
        const saved = widths[colKey] || 0;
        widths[colKey] = Math.max(labelMin, saved, DEFAULT_WIDTHS[colKey] || 0, MIN_COL_WIDTH);
    });

    applyAllWidths(table, widths);
    applyFixedColumns(table);
    updateTableMinWidth(table);
}

function syncTable(table) {
    if (!table.classList.contains('nttu-table')) {
        return;
    }

    const storageKey = tableStorageKey(table);
    if (!storageKey) {
        if (hasSettingsColumn(table)) {
            layoutSettingsPinnedTable(table);
        }
        return;
    }

    if (table._nttuSyncing) {
        return;
    }

    table._nttuSyncing = true;
    try {
        removeSpacerColumns(table);
        ensureColgroup(table);
        fitColumnsToHeaderLabels(table);
    } finally {
        queueMicrotask(() => {
            table._nttuSyncing = false;
        });
    }
}

function syncAllTables(root = document) {
    root.querySelectorAll('table.nttu-table').forEach((table) => {
        if (tableStorageKey(table) || hasSettingsColumn(table)) {
            syncTable(table);
        }
    });
}

function isNearColumnRightEdge(th, clientX) {
    const rect = th.getBoundingClientRect();
    return clientX >= rect.right - EDGE_HIT_PX && clientX <= rect.right + 2;
}

function startColumnResize(table, th, colKey, clientX) {
    const storageKey = tableStorageKey(table);
    if (!storageKey || !colKey) {
        return;
    }

    const widths = getTableWidths(table, storageKey);
    const labelMins = getLabelMinWidths(table);
    const floor = labelMins[colKey] || DEFAULT_WIDTHS[colKey] || MIN_COL_WIDTH;
    const startX = clientX;
    const startWidth = widths[colKey] ?? th.getBoundingClientRect().width;

    const onMove = (moveEvent) => {
        const next = Math.max(
            floor,
            Math.min(MAX_COL_WIDTH, Math.round(startWidth + (moveEvent.clientX - startX))),
        );
        widths[colKey] = next;
        applyColumnWidth(table, colKey, next);
        applyFixedColumns(table);
    };

    const onUp = () => {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        saveWidths(storageKey, widths);
        expandTableToFillContainer(table, { evenly: false });
    };

    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
}

function onDocumentMouseDown(event) {
    if (event.button !== 0) {
        return;
    }

    const th = event.target.closest('thead th[data-col]');
    if (!th || isFixedHeader(th)) {
        return;
    }

    const table = th.closest('table.nttu-table[data-col-resize]');
    if (!table || !isNearColumnRightEdge(th, event.clientX)) {
        return;
    }

    const colKey = th.dataset.col;
    if (!colKey) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    syncTable(table);
    startColumnResize(table, th, colKey, event.clientX);
}

function mutationShouldSyncTable(mutation) {
    if (mutation.type === 'attributes') {
        return mutation.attributeName === 'data-col-resize';
    }

    if (mutation.type !== 'childList') {
        return false;
    }

    const addedTables = [...mutation.addedNodes].filter(
        (node) => node.nodeType === 1 && node.matches?.('table.nttu-table[data-col-resize]'),
    );
    if (addedTables.length) {
        return true;
    }

    const target = mutation.target;
    if (!(target instanceof Element)) {
        return false;
    }

    const table = target.closest('table.nttu-table');
    if (!table || table._nttuSyncing) {
        return false;
    }

    if (target.tagName === 'TBODY' || target.closest('tbody') !== null) {
        return hasSettingsColumn(table);
    }

    // Chỉ đồng bộ khi cấu trúc thead đổi (ẩn/hiện cột), không phải khi tbody render lại từng dòng.
    return target.tagName === 'THEAD' || target.closest('thead') !== null;
}

function watchTables() {
    let frame = 0;
    const observer = new MutationObserver((mutations) => {
        if (!mutations.some(mutationShouldSyncTable)) {
            return;
        }

        if (frame) {
            cancelAnimationFrame(frame);
        }
        frame = requestAnimationFrame(() => {
            frame = 0;
            syncAllTables();
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['data-col-resize'],
    });
}

export function bindNttuColumnResizeTable(table) {
    syncTable(table);
}

export function scheduleBindNttuColumnResizeTable(table, delays = [0, 100, 400, 1000]) {
    if (!table) {
        return;
    }
    delays.forEach((delay) => {
        window.setTimeout(() => syncTable(table), delay);
    });
}

export function initNttuColumnResize(root = document) {
    syncAllTables(root);
}

export function registerNttuColumnResize() {
    window.nttuInitColumnResize = initNttuColumnResize;
    window.nttuBindColumnResizeTable = bindNttuColumnResizeTable;
    window.nttuScheduleColumnResize = scheduleBindNttuColumnResizeTable;

    document.addEventListener('mousedown', onDocumentMouseDown, true);

    const boot = () => {
        syncAllTables();
        requestAnimationFrame(() => syncAllTables());
        window.setTimeout(() => syncAllTables(), 250);
        window.setTimeout(() => syncAllTables(), 1000);
    };

    document.addEventListener('alpine:initialized', boot);
    if (document.readyState !== 'loading') {
        boot();
    } else {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    }

    window.addEventListener('nttu-language-changed', () => {
        document.querySelectorAll('table.nttu-table').forEach((table) => {
            if (!tableStorageKey(table) && !hasSettingsColumn(table)) {
                return;
            }
            table._nttuLabelMinWidths = {};
            syncTable(table);
        });
    });

    let resizeTimer = 0;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => syncAllTables(), 150);
    });

    watchTables();
}
