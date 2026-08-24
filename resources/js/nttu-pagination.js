export const ROWS_PER_PAGE_OPTIONS = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50];

export function normalizeRowsPerPage(value, fallback = 10) {
    const parsed = parseInt(String(value ?? ''), 10);
    if (!Number.isFinite(parsed) || parsed < 1) {
        return parseInt(String(fallback ?? 10), 10) || 10;
    }

    // Limit to maximum 1000 rows to prevent browser freeze
    return Math.min(parsed, 1000);
}

export function readStoredRowsPerPage(key, fallback = 10) {
    try {
        const raw = localStorage.getItem(key);
        if (raw === null || raw === '') {
            return normalizeRowsPerPage(fallback, fallback);
        }

        try {
            return normalizeRowsPerPage(JSON.parse(raw), fallback);
        } catch {
            return normalizeRowsPerPage(raw, fallback);
        }
    } catch {
        return normalizeRowsPerPage(fallback, fallback);
    }
}

export function writeStoredRowsPerPage(key, value) {
    localStorage.setItem(key, String(normalizeRowsPerPage(value, 10)));
}

export function normalizeCurrentPage(value, totalPages = 1) {
    const total = Math.max(1, parseInt(String(totalPages ?? ''), 10) || 1);
    const parsed = parseInt(String(value ?? ''), 10);
    if (!Number.isFinite(parsed) || parsed < 1) {
        return 1;
    }

    return Math.min(parsed, total);
}

export function paginateSlice(items, page, perPage) {
    const rowsPerPage = normalizeRowsPerPage(perPage);
    const totalPages = Math.max(1, Math.ceil(items.length / rowsPerPage));
    const safePage = normalizeCurrentPage(page, totalPages);
    const startIndex = (safePage - 1) * rowsPerPage;

    return {
        items: items.slice(startIndex, startIndex + rowsPerPage),
        startIndex,
        totalPages,
        safePage,
        rowsPerPage,
    };
}
