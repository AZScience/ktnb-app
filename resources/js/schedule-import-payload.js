function isValidImportDate(rawDate) {
    const formatted = String(rawDate || '').trim();
    if (!formatted) {
        return false;
    }

    return /^\d{1,2}\/\d{1,2}\/\d{4}$/.test(formatted);
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
