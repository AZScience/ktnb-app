/**
 * NTTU Table Multi-Sort & Natural Period Comparator
 * Supports up to 2 sort conditions, smart period sorting, and automatic secondary fallback.
 */

export function parsePeriodRange(p) {
    if (p == null || p === '') return [9999, 9999];
    const nums = String(p).match(/\d+/g);
    if (!nums || nums.length === 0) return [9999, 9999];
    const start = parseInt(nums[0], 10);
    const end = nums[1] ? parseInt(nums[1], 10) : start;
    return [start, end];
}

export function comparePeriods(p1, p2) {
    const [s1, e1] = parsePeriodRange(p1);
    const [s2, e2] = parsePeriodRange(p2);
    if (s1 !== s2) return s1 - s2;
    if (e1 !== e2) return e1 - e2;
    return String(p1 || '').localeCompare(String(p2 || ''), 'vi', { numeric: true });
}

export function parseDateToTime(val) {
    if (!val) return 0;
    if (typeof val === 'number') return val;
    const str = String(val).trim();
    // Check format DD/MM/YYYY
    const dmy = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
    if (dmy) {
        return new Date(`${dmy[3]}-${dmy[2].padStart(2, '0')}-${dmy[1].padStart(2, '0')}`).getTime() || 0;
    }
    // Check format YYYY-MM-DD
    const ymd = str.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/);
    if (ymd) {
        return new Date(`${ymd[1]}-${ymd[2].padStart(2, '0')}-${ymd[3].padStart(2, '0')}`).getTime() || 0;
    }
    const parsed = Date.parse(str);
    return Number.isNaN(parsed) ? 0 : parsed;
}

export function compareValues(a, b, key) {
    if (key === 'period' || key === 'shift') {
        return comparePeriods(a?.[key], b?.[key]);
    }

    const av = a?.[key];
    const bv = b?.[key];

    if (av == null && bv == null) return 0;
    if (av == null || av === '') return 1;
    if (bv == null || bv === '') return -1;

    // Date keys
    if (['date', 'violation_date', 'created_at', 'updated_at', 'modified_at', 'start_date', 'end_date'].includes(key)) {
        const t1 = parseDateToTime(av);
        const t2 = parseDateToTime(bv);
        if (t1 !== t2) return t1 - t2;
    }

    if (typeof av === 'number' && typeof bv === 'number') {
        return av - bv;
    }

    return String(av).localeCompare(String(bv), 'vi', { numeric: true });
}

export function normalizeSortRules(config) {
    if (!config) return [];
    if (Array.isArray(config)) {
        return config
            .filter((r) => r && r.key && r.direction)
            .map((r) => ({
                key: String(r.key),
                direction: r.direction === 'descending' || r.direction === 'desc' ? 'desc' : 'asc',
            }))
            .slice(0, 2);
    }
    if (typeof config === 'object' && config.key && config.direction) {
        return [{
            key: String(config.key),
            direction: config.direction === 'descending' || config.direction === 'desc' ? 'desc' : 'asc',
        }];
    }
    return [];
}

export function multiSortRows(items, sortRules, fallbacks = ['period', 'room', 'id']) {
    if (!Array.isArray(items) || items.length === 0) return [];
    const rules = normalizeSortRules(sortRules);

    return [...items].sort((a, b) => {
        // 1. Explicit sort rules
        for (const rule of rules) {
            const dir = rule.direction === 'desc' ? -1 : 1;
            const cmp = compareValues(a, b, rule.key);
            if (cmp !== 0) return cmp * dir;
        }

        // 2. Smart auto-fallbacks when explicit rules are equal
        for (const fb of fallbacks) {
            if (rules.some((r) => r.key === fb)) continue;
            if (a?.[fb] === undefined && b?.[fb] === undefined) continue;
            const cmp = compareValues(a, b, fb);
            if (cmp !== 0) return cmp;
        }

        return 0;
    });
}
