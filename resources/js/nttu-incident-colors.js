// Bảng màu dùng chung cho cột "Việc phát sinh": mỗi loại việc một màu tươi, nổi bật.
// Các chuỗi class được viết đầy đủ (literal) để Tailwind quét và sinh ra khi build.

const BADGE_BASE = 'font-semibold shadow-sm';

const PALETTE = [
    `${BADGE_BASE} bg-rose-600 text-white ring-1 ring-rose-400`,
    `${BADGE_BASE} bg-orange-600 text-white ring-1 ring-orange-400`,
    `${BADGE_BASE} bg-amber-500 text-white ring-1 ring-amber-300`,
    `${BADGE_BASE} bg-yellow-500 text-white ring-1 ring-yellow-300`,
    `${BADGE_BASE} bg-lime-600 text-white ring-1 ring-lime-400`,
    `${BADGE_BASE} bg-green-600 text-white ring-1 ring-green-400`,
    `${BADGE_BASE} bg-emerald-600 text-white ring-1 ring-emerald-400`,
    `${BADGE_BASE} bg-teal-600 text-white ring-1 ring-teal-400`,
    `${BADGE_BASE} bg-cyan-600 text-white ring-1 ring-cyan-400`,
    `${BADGE_BASE} bg-sky-600 text-white ring-1 ring-sky-400`,
    `${BADGE_BASE} bg-blue-600 text-white ring-1 ring-blue-400`,
    `${BADGE_BASE} bg-indigo-600 text-white ring-1 ring-indigo-400`,
    `${BADGE_BASE} bg-violet-600 text-white ring-1 ring-violet-400`,
    `${BADGE_BASE} bg-purple-600 text-white ring-1 ring-purple-400`,
    `${BADGE_BASE} bg-fuchsia-600 text-white ring-1 ring-fuchsia-400`,
    `${BADGE_BASE} bg-pink-600 text-white ring-1 ring-pink-400`,
];

const NEUTRAL = `${BADGE_BASE} bg-slate-500 text-white ring-1 ring-slate-400`;

// Màu cố định cho các việc phổ biến.
const EXPLICIT = [
    ['báo nghỉ', `${BADGE_BASE} bg-red-600 text-white ring-1 ring-red-400`],
    ['nghỉ dạy', `${BADGE_BASE} bg-red-600 text-white ring-1 ring-red-400`],
    ['hủy lịch', `${BADGE_BASE} bg-red-700 text-white ring-1 ring-red-500`],
    ['chuyển phòng', `${BADGE_BASE} bg-orange-600 text-white ring-1 ring-orange-400`],
    ['dạy thay', `${BADGE_BASE} bg-violet-600 text-white ring-1 ring-violet-400`],
    ['coi thi thay', `${BADGE_BASE} bg-purple-600 text-white ring-1 ring-purple-400`],
    ['ngoài lịch', `${BADGE_BASE} bg-amber-500 text-white ring-1 ring-amber-300`],
    ['ngoài tkb', `${BADGE_BASE} bg-amber-500 text-white ring-1 ring-amber-300`],
    ['đi trễ', `${BADGE_BASE} bg-yellow-500 text-white ring-1 ring-yellow-300`],
    ['về sớm', `${BADGE_BASE} bg-cyan-600 text-white ring-1 ring-cyan-400`],
    ['có sh', `${BADGE_BASE} bg-green-600 text-white ring-1 ring-green-400`],
    ['có thực hiện', `${BADGE_BASE} bg-green-600 text-white ring-1 ring-green-400`],
    ['không online', `${BADGE_BASE} bg-rose-600 text-white ring-1 ring-rose-400`],
    ['không có online', `${BADGE_BASE} bg-rose-600 text-white ring-1 ring-rose-400`],
    ['lcms', `${BADGE_BASE} bg-blue-600 text-white ring-1 ring-blue-400`],
    ['ghi nhận khác', `${BADGE_BASE} bg-slate-600 text-white ring-1 ring-slate-400`],
];

function normalize(value) {
    return String(value ?? '').trim().toLowerCase();
}

function hashIndex(text) {
    let hash = 0;
    for (let i = 0; i < text.length; i++) {
        hash = (hash * 31 + text.charCodeAt(i)) >>> 0;
    }
    return hash % PALETTE.length;
}

const NORMAL_PHRASES = new Set([
    '',
    '---',
    '--- không có ---',
    'không có',
    'khong co',
    'bình thường',
    'binh thuong',
    'học bình thường',
    'hoc binh thuong',
    'none',
]);

export function isNotableIncidentValue(value) {
    return !NORMAL_PHRASES.has(normalize(value));
}

/** Trả về chuỗi class Tailwind cho badge của một việc phát sinh. */
export function incidentBadgeClass(value) {
    const key = normalize(value);
    if (!isNotableIncidentValue(value)) {
        return NEUTRAL;
    }

    for (const [needle, cls] of EXPLICIT) {
        if (key.includes(needle)) {
            return cls;
        }
    }

    return PALETTE[hashIndex(key)];
}
