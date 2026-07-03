import * as XLSX from 'xlsx';

/**
 * @param {Array<{key: string, label: string}>} columns
 * @param {string} filename
 * @param {Record<string, unknown>|Record<string, string>|null} options
 */
export function downloadImportTemplate(columns, filename, options = null) {
    if (!columns?.length) {
        return;
    }

    const opts = options && typeof options === 'object' && !Array.isArray(options)
        ? options
        : { sampleRow: options };

    const {
        sampleRow = null,
        startColumn = 0,
        indexColumnLabel = '',
        includeSampleRow = true,
        sheetName = 'Mau_Import',
    } = opts;

    const leading = [];
    for (let i = 0; i < startColumn; i += 1) {
        if (i === 0 && indexColumnLabel) {
            leading.push(indexColumnLabel);
        } else {
            leading.push('');
        }
    }

    const headers = [...leading, ...columns.map((col) => col.label)];
    const sampleCells = sampleRow
        ? columns.map((col) => sampleRow[col.key] ?? '')
        : columns.map(() => '');

    const sampleLeading = [];
    for (let i = 0; i < startColumn; i += 1) {
        if (i === 0 && indexColumnLabel) {
            sampleLeading.push('1');
        } else {
            sampleLeading.push('');
        }
    }

    const sample = [...sampleLeading, ...sampleCells];
    const rows = [headers];
    if (includeSampleRow && sampleCells.some((cell) => String(cell).trim() !== '')) {
        rows.push(sample);
    }

    const worksheet = XLSX.utils.aoa_to_sheet(rows);
    worksheet['!cols'] = headers.map((header, index) => ({
        wch: Math.min(40, Math.max(10, String(header || sample[index] || '').length + 2)),
    }));

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);

    XLSX.writeFile(workbook, filename.endsWith('.xlsx') ? filename : `${filename}.xlsx`);
}
