import * as XLSX from 'xlsx';

function normalizeKey(value) {
    return String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]/gi, '')
        .trim();
}

function formatImportDate(rawDate) {
    if (rawDate instanceof Date && !Number.isNaN(rawDate.getTime())) {
        const day = String(rawDate.getDate()).padStart(2, '0');
        const month = String(rawDate.getMonth() + 1).padStart(2, '0');
        const year = rawDate.getFullYear();

        return `${day}/${month}/${year}`;
    }

    return String(rawDate || '').trim();
}

function isValidImportDate(rawDate) {
    const formatted = formatImportDate(rawDate);
    if (!formatted) {
        return false;
    }

    return /^\d{1,2}\/\d{1,2}\/\d{4}$/.test(formatted);
}

function rowText(row) {
    return Object.values(row || {})
        .map((value) => String(value ?? '').trim())
        .filter(Boolean)
        .join(' ')
        .trim();
}

function isFooterOrSignatureRow(row) {
    const text = rowText(row);
    if (!text) {
        return true;
    }

    if (/Hồ Chí Minh,\s*ngày\s+\d{1,2}\s+tháng\s+\d{1,2}\s+năm\s+\d{4}/i.test(text)) {
        return true;
    }

    if (/Người lập biểu/i.test(text)) {
        return true;
    }

    if (/^Nguyễn\s+Vĩnh\s+Phúc$/i.test(text)) {
        return true;
    }

    return false;
}

function isScheduleDataRow(row) {
    if (isFooterOrSignatureRow(row)) {
        return false;
    }

    if (!isValidImportDate(row['Ngày'])) {
        return false;
    }

    const className = String(row['Lớp'] || '').trim();
    const content = String(row['Nội dung'] || '').trim();
    const room = String(row['Phòng'] || '').trim();

    return !!(className || content || room || String(row['Tiết'] || '').trim());
}

function rawRowToData(row) {
    const status = String(row['Trạng thái'] || 'Phòng học');
    let className = String(row['Lớp'] || '');
    let lecturer = String(row['Giảng viên'] || '');

    if (status === 'Phòng tự do' && !className && !lecturer) {
        className = 'Tự do';
        lecturer = 'Tự do';
    }

    return {
        date: formatImportDate(row['Ngày']),
        building: String(row['Dãy nhà'] || ''),
        room: String(row['Phòng'] || ''),
        period: String(row['Tiết'] || ''),
        type: String(row['LT/TH'] || row['Loại'] || ''),
        studentCount: Number(row['Sĩ số'] || 0),
        department: String(row['Khoa sử dụng'] || row['Khoa'] || ''),
        class: className,
        lecturer,
        proctor1: String(row['CBCT 01'] || ''),
        proctor2: String(row['CBCT 02'] || ''),
        proctor3: String(row['CBCT 03'] || ''),
        content: String(row['Nội dung'] || ''),
        status,
        note: String(row['Ghi chú'] || ''),
    };
}

/** @param {ArrayBuffer} buffer */
export function parseScheduleExcelFile(buffer) {
    const workbook = XLSX.read(buffer, { type: 'array', cellDates: true });
    const rows = [];

    for (const sheetName of workbook.SheetNames) {
        const sheet = workbook.Sheets[sheetName];
        if (!sheet) {
            continue;
        }

        const sheetRows = XLSX.utils.sheet_to_json(sheet, { range: 7, defval: '' });
        if (Array.isArray(sheetRows) && sheetRows.length) {
            rows.push(...sheetRows.filter(isScheduleDataRow));
        }
    }

    return rows;
}

export function buildImportPreviewRows(rawRows) {
    const previewMap = new Map();

    for (const row of rawRows) {
        const rowData = rawRowToData(row);
        const status = rowData.status;
        const lecturer = String(row['Giảng viên'] || '');

        if (status === 'Phòng thi') {
            rowData.lecturer = '';
            if (!rowData.proctor1 && lecturer) {
                rowData.proctor1 = lecturer;
            }
        }

        const baseKey = normalizeKey([
            rowData.date,
            rowData.building,
            rowData.room,
            rowData.period,
            rowData.type,
            rowData.department,
            rowData.class,
            rowData.studentCount,
            rowData.content,
        ].join('-'));

        const key = status === 'Phòng thi' ? baseKey : normalizeKey(`${baseKey}-${lecturer}`);

        if (previewMap.has(key)) {
            const existing = previewMap.get(key);
            if (status === 'Phòng thi' && lecturer) {
                if (!existing.proctor1) {
                    existing.proctor1 = lecturer;
                } else if (!existing.proctor2) {
                    existing.proctor2 = lecturer;
                } else if (!existing.proctor3) {
                    existing.proctor3 = lecturer;
                }
            }
        } else {
            previewMap.set(key, { ...rowData });
        }
    }

    return Array.from(previewMap.values());
}

export function mergeImportRows(rawRows) {
    const globalMergedMap = new Map();

    for (const row of rawRows) {
        const rowData = rawRowToData(row);
        const status = rowData.status;
        const lecturer = rowData.lecturer;

        const baseKey = normalizeKey([
            rowData.date,
            rowData.building,
            rowData.room,
            rowData.period,
            rowData.type,
            rowData.department,
            rowData.class,
            rowData.studentCount,
            rowData.content,
        ].join(''));

        const key = status === 'Phòng thi' ? baseKey : `${baseKey}${normalizeKey(lecturer)}`;

        if (globalMergedMap.has(key)) {
            const existing = globalMergedMap.get(key);
            if (rowData.content && !existing.content.includes(rowData.content)) {
                existing.content = `${existing.content} / ${rowData.content}`.replace(/^ \/ /, '').trim();
            }

            if (status === 'Phòng thi') {
                const nextLec = lecturer;
                if (nextLec && ![existing.proctor1, existing.proctor2, existing.proctor3].includes(nextLec)) {
                    if (!existing.proctor1) {
                        existing.proctor1 = nextLec;
                    } else if (!existing.proctor2) {
                        existing.proctor2 = nextLec;
                    } else if (!existing.proctor3) {
                        existing.proctor3 = nextLec;
                    }
                }
                existing.lecturer = '';
            } else if (lecturer && !existing.lecturer.includes(lecturer)) {
                existing.lecturer = `${existing.lecturer}, ${lecturer}`.replace(/^, /, '').trim();
            }
        } else if (status === 'Phòng thi') {
            const firstLec = lecturer;
            globalMergedMap.set(key, {
                ...rowData,
                proctor1: firstLec || rowData.proctor1,
                proctor2: rowData.proctor2 || '',
                proctor3: rowData.proctor3 || '',
                lecturer: '',
            });
        } else {
            globalMergedMap.set(key, { ...rowData });
        }
    }

    return Array.from(globalMergedMap.values()).filter((row) => isValidImportDate(row.date));
}

export function filterNewImportItems(mergedRows, existingItems) {
    const existingKeys = new Set(
        (existingItems || []).map((item) => normalizeKey([
            item.date,
            item.building,
            item.room,
            item.period,
            item.department,
            item.content,
            item.class,
            item.lecturer,
        ].join(''))),
    );

    return mergedRows.filter((row) => {
        if (!isValidImportDate(row.date)) {
            return false;
        }

        const rowKey = normalizeKey([
            row.date,
            row.building,
            row.room,
            row.period,
            row.department,
            row.content,
            row.class,
            row.lecturer,
        ].join(''));

        return !existingKeys.has(rowKey);
    });
}

export function toImportPayload(row) {
    if (!isValidImportDate(row.date)) {
        return null;
    }

    const count = row.studentCount ?? row.student_count;

    return {
        date: row.date,
        building: row.building || null,
        room: row.room || null,
        period: row.period || null,
        type: row.type || null,
        student_count: count === '' || count === undefined || count === null ? null : Number(count),
        department: row.department || null,
        class: row.class || null,
        lecturer: row.lecturer || null,
        proctor1: row.proctor1 || null,
        proctor2: row.proctor2 || null,
        proctor3: row.proctor3 || null,
        content: row.content || null,
        status: row.status || 'Phòng học',
        note: row.note || null,
    };
}
