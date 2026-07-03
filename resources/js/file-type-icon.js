const PAGE_BODY = `
  <path fill="#fff" stroke="#cbd5e1" stroke-width=".75" d="M7 3h12.5l5.5 5.5V27a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/>
  <path fill="#e2e8f0" d="M19.5 3v6h6"/>
`;

function svgWrap(body, className = 'h-7 w-7') {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="${className}" aria-hidden="true">${body}</svg>`;
}

function labeledDocIcon(label, color, className) {
    return svgWrap(`
        ${PAGE_BODY}
        <rect fill="${color}" x="5" y="17" width="22" height="9" rx="1"/>
        <text fill="#fff" font-family="Arial,Helvetica,sans-serif" font-size="5.5" font-weight="700" x="16" y="23.5" text-anchor="middle">${label}</text>
    `, className);
}

const ICONS = {
    pdf: (className) => labeledDocIcon('PDF', '#E53935', className),
    word: (className) => labeledDocIcon('DOC', '#2B579A', className),
    excel: (className) => labeledDocIcon('XLS', '#217346', className),
    ppt: (className) => labeledDocIcon('PPT', '#D24726', className),
    txt: (className) => labeledDocIcon('TXT', '#64748B', className),
    csv: (className) => labeledDocIcon('CSV', '#15803D', className),
    image: (className) => svgWrap(`
        ${PAGE_BODY}
        <rect fill="#F59E0B" x="5" y="17" width="22" height="9" rx="1"/>
        <circle fill="#fff" cx="12" cy="20" r="1.4"/>
        <path fill="#fff" d="M8 24l4-3 3 2 4-4 5 5H8z"/>
    `, className),
    archive: (className) => labeledDocIcon('ZIP', '#7C3AED', className),
    link: (className) => svgWrap(`
        <rect fill="#ECFEFF" stroke="#06B6D4" stroke-width="1" x="4" y="6" width="24" height="20" rx="3"/>
        <path fill="none" stroke="#0891B2" stroke-width="1.75" stroke-linecap="round" d="M13 16a3.5 3.5 0 0 0 4.9 0l2.1-2.1a3.5 3.5 0 1 0-5-5l-1 1"/>
        <path fill="none" stroke="#0891B2" stroke-width="1.75" stroke-linecap="round" d="M19 16a3.5 3.5 0 0 0-4.9 0l-2.1 2.1a3.5 3.5 0 1 0 5 5l1-1"/>
    `, className),
    file: (className) => svgWrap(`
        ${PAGE_BODY}
        <rect fill="#94A3B8" x="5" y="17" width="22" height="9" rx="1"/>
        <path fill="#fff" d="M10 21h12v1H10zm0 2.5h8v1h-8z"/>
    `, className),
};

export function resolveFileIconType(ext, isDirectLink = false) {
    if (ext === 'pdf') return 'pdf';
    if (['doc', 'docx'].includes(ext)) return 'word';
    if (['xls', 'xlsx'].includes(ext)) return 'excel';
    if (['csv'].includes(ext)) return 'csv';
    if (['ppt', 'pptx'].includes(ext)) return 'ppt';
    if (['txt', 'rtf', 'md'].includes(ext)) return 'txt';
    if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) return 'image';
    if (['zip', 'rar', '7z'].includes(ext)) return 'archive';
    if (isDirectLink) return 'link';
    return 'file';
}

export function renderFileTypeIcon(type, className = 'h-7 w-7') {
    const render = ICONS[type] || ICONS.file;
    return render(className);
}
