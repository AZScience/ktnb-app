import os
import time
from playwright.sync_api import sync_playwright
import docx
from docx.shared import Pt, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH

BASE_URL = "http://127.0.0.1:8000"
SCREENSHOT_DIR = r"D:\ktnb-app\screenshots\annotated"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

JS_NUMBER_LIST = """
() => {
    try {
        let targets = [];
        
        // 1. All action buttons in the top right
        const actionGroup = document.querySelector('.nttu-card .border-b .flex.items-center.gap-2');
        if (actionGroup) {
            actionGroup.querySelectorAll('button, label').forEach(btn => targets.push(btn));
        }
        
        // 2. Column settings button
        const btnColumns = document.querySelector('th.catalog-th-settings button, button.nttu-catalog-cog-btn');
        if (btnColumns) targets.push(btnColumns);
        
        // 3. Row action button
        const btnAction = document.querySelector('.nttu-row-action-btn');
        if (btnAction) targets.push(btnAction);
        
        // 4. Search input
        const inputSearch = document.querySelector('input[placeholder*="Tìm kiếm"]');
        if (inputSearch) targets.push(inputSearch);

        let count = 1;
        let annotations = [];
        
        targets.forEach(el => {
            if(el && el.getBoundingClientRect().width > 0) {
                el.style.outline = '4px solid red';
                const rect = el.getBoundingClientRect();
                const label = document.createElement('div');
                label.className = 'my-red-badge';
                label.textContent = count;
                label.style.background = 'red';
                label.style.color = 'white';
                label.style.padding = '2px 8px';
                label.style.fontSize = '16px';
                label.style.fontWeight = 'bold';
                label.style.borderRadius = '50%';
                label.style.position = 'fixed';
                label.style.top = (rect.top - 10) + 'px';
                label.style.left = (rect.left - 10) + 'px';
                label.style.zIndex = '999999';
                document.body.appendChild(label);
                
                let desc = el.title || el.innerText || "";
                if (!desc && el.tagName.toLowerCase() === 'input') desc = "Tìm kiếm dữ liệu";
                if (!desc && el.querySelector('input[type="file"]')) desc = "Nhập file Excel (Upload)";
                if (!desc) {
                    const html = el.innerHTML || "";
                    if (html.includes('M12 10v6m0 0l-3-3')) desc = "Tải mẫu file import";
                    else if (html.includes('M4 16v1a3 3 0 003 3h10') && !el.querySelector('input')) desc = "Xuất file Excel";
                    else if (html.includes('M12 9v3m0 0v3')) desc = "Thêm mới dữ liệu";
                    else if (html.includes('M3 4h18M6 8h12')) desc = "Bộ lọc nâng cao";
                    else if (html.includes('M4 16v1a3 3 0 003 3h10') && el.querySelector('input')) desc = "Nhập file Excel (Upload)";
                    else if (html.includes('M10.325 4.317')) desc = "Cài đặt hiển thị cột";
                    else if (html.includes('M5 12h.01M12 12h.01')) desc = "Menu Hành động (Sửa/Xóa/Xem)";
                    else desc = "Nút thao tác (" + el.tagName.toLowerCase() + ")";
                }
                
                if (desc) {
                    annotations.push(`[` + count + `] ` + desc);
                    count++;
                }
            }
        });
        return annotations;
    } catch(e) { return []; }
}
"""

JS_NUMBER_CONTAINER = """
(containerSelector) => {
    try {
        const container = document.querySelector(containerSelector);
        if(!container) return [];
        // Tìm các input, select, textarea, button, checkbox.
        // Hạn chế các input hidden hoặc readonly không cần thiết, trừ khi hiển thị.
        const elements = container.querySelectorAll('input:not([type="hidden"]), select, textarea, button, .nttu-row-action-menu button');
        let count = 1;
        let annotations = [];
        
        elements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if(rect.width > 5 && rect.height > 5 && window.getComputedStyle(el).display !== 'none' && window.getComputedStyle(el).visibility !== 'hidden') {
                el.style.outline = '3px solid red';
                const label = document.createElement('div');
                label.className = 'my-red-badge';
                label.textContent = count;
                label.style.background = 'red';
                label.style.color = 'white';
                label.style.padding = '2px 6px';
                label.style.fontSize = '12px';
                label.style.fontWeight = 'bold';
                label.style.borderRadius = '50%';
                label.style.position = 'fixed';
                label.style.top = (rect.top - 5) + 'px';
                label.style.left = (rect.left - 5) + 'px';
                label.style.zIndex = '999999';
                document.body.appendChild(label);
                
                // Xác định Tên của đối tượng tương tác
                let fieldName = "";
                
                // 1. Thử lấy label cho input
                if (el.id) {
                    const lbl = document.querySelector(`label[for="${el.id}"]`);
                    if (lbl) fieldName = lbl.innerText.trim();
                }
                
                // 2. Thử lấy từ label bọc nó
                if (!fieldName && el.closest('label')) {
                    fieldName = el.closest('label').innerText.trim();
                }
                
                // 3. Nút bấm
                if (!fieldName && el.tagName.toLowerCase() === 'button') {
                    fieldName = el.innerText.trim();
                    if(!fieldName) fieldName = el.title;
                    if(fieldName) fieldName = "Nút: " + fieldName;
                }
                
                // 4. Placeholder
                if (!fieldName && el.placeholder) {
                    fieldName = el.placeholder;
                }
                
                // 5. Thử tìm text ngay kề trước (prev element)
                if (!fieldName && el.previousElementSibling && el.previousElementSibling.tagName.toLowerCase() === 'label') {
                    fieldName = el.previousElementSibling.innerText.trim();
                }
                
                // Lọc bỏ ký tự * bắt buộc
                if(fieldName) fieldName = fieldName.replace(/\*$/, '').trim();
                if(!fieldName) fieldName = "Thao tác / Nhập liệu";
                
                // Gắn thêm tiền tố để ngta biết là loại gì
                let typePrefix = "Ô nhập: ";
                if(el.tagName.toLowerCase() === 'select') typePrefix = "Menu chọn: ";
                else if(el.type === 'checkbox' || el.type === 'radio') typePrefix = "Lựa chọn: ";
                else if(el.tagName.toLowerCase() === 'button') typePrefix = "";
                
                annotations.push(`[` + count + `] ` + typePrefix + fieldName);
                count++;
            }
        });
        return annotations;
    } catch(e) { return []; }
}
"""

def clear_labels(page):
    page.evaluate("""
        document.querySelectorAll('.my-red-badge').forEach(el => el.remove());
        document.querySelectorAll('*').forEach(el => {
            if(el.style.outline.includes('red')) el.style.outline = '';
        });
    """)

print("Capturing interaction states with annotations...")
doc = docx.Document()
def add_heading(text, level):
    h = doc.add_heading(text, level)
    for run in h.runs: run.font.color.rgb = RGBColor(0, 80, 160)
def insert_img(filename):
    path = os.path.join(SCREENSHOT_DIR, filename)
    if os.path.exists(path):
        doc.add_picture(path, width=Inches(6.5))
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
    else:
        doc.add_paragraph(f"(Không có dữ liệu cho thao tác này)").alignment = WD_ALIGN_PARAGRAPH.CENTER

def add_annotations(annotations):
    if not annotations:
        doc.add_paragraph("(Không có đối tượng tương tác nào được nhận diện)")
    else:
        for ann in annotations:
            p = doc.add_paragraph(ann)
            p.paragraph_format.left_indent = Inches(0.5)

doc.add_heading('SỔ TAY HƯỚNG DẪN SỬ DỤNG CHI TIẾT ĐẾN TỪNG Ô NHẬP LIỆU', 0).alignment = WD_ALIGN_PARAGRAPH.CENTER

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1440, 'height': 900})
    page = context.new_page()
    page.goto(f"{BASE_URL}/auto-login")
    time.sleep(2)

    modules = [
        ("Danh mục Đơn vị", "departments", "/personnel/departments", True),
        ("Lớp học trực tiếp", "in_person", "/monitoring/in-person", True),
        ("Thi kết thúc môn", "exams", "/monitoring/exams", True),
        ("Thực hành ngoài", "external", "/monitoring/external-practice", True),
        ("Sinh viên vi phạm", "violations", "/monitoring/student-violations", True),
        ("Nhận trả tài sản", "asset", "/monitoring/asset-check", True),
        ("Tiếp nhận yêu cầu", "requests", "/monitoring/requests", True),
        ("Tiếp nhận đơn thư", "petitions", "/monitoring/petitions", True),
        ("Quản lý hồ sơ", "documents", "/monitoring/document-records", True),
        ("Báo cáo cuối ngày", "report_daily", "/reports/daily", False),
        ("Thống kê KPH", "report_kph", "/reports/comprehensive", False),
        ("BC Sinh viên vi phạm", "report_sv", "/reports/student-violations", False),
        ("Lịch học theo ngày", "schedule", "/settings/schedule", True),
        ("Phân quyền truy cập", "permissions", "/settings/permissions", True),
        ("Nhật ký truy cập", "accesslog", "/settings/access-log", False),
    ]

    for title, name, route, has_form in modules:
        try:
            print(f"Processing {name}...")
            page.goto(f"{BASE_URL}{route}")
            page.wait_for_load_state("load")
            time.sleep(1)
            
            add_heading(f"Chức năng: {title}", 1)

            # 1. LIST SCREEN
            annotations = page.evaluate(JS_NUMBER_LIST)
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_1_list.png"))
            doc.add_paragraph("1. MÀN HÌNH CHÍNH (DANH SÁCH)")
            insert_img(f"{name}_1_list.png")
            doc.add_paragraph("Mô tả các thao tác trên màn hình chính:")
            add_annotations(annotations)
            clear_labels(page)

            # 2. COLUMNS (Cài đặt hiển thị)
            page.evaluate("""
                const btn = document.querySelector('button[title="Cài đặt hiển thị"]');
                if(btn) btn.click();
            """)
            time.sleep(1)
            annotations = page.evaluate(JS_NUMBER_CONTAINER, ".nttu-report-settings-panel")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_2_columns.png"))
            doc.add_paragraph("2. ẨN/HIỆN CỘT DỮ LIỆU")
            insert_img(f"{name}_2_columns.png")
            doc.add_paragraph("Ý nghĩa các ô check:")
            add_annotations(annotations)
            clear_labels(page)
            page.evaluate("document.body.click();")
            time.sleep(0.5)

            # 3. FILTER (Bộ lọc)
            page.evaluate("""
                const btn = document.querySelector('button[title="Bộ lọc nâng cao"]');
                if(btn) btn.click();
            """)
            time.sleep(1)
            annotations = page.evaluate(JS_NUMBER_CONTAINER, ".relative.z-10.w-full")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_3_filter.png"))
            doc.add_paragraph("3. BỘ LỌC NÂNG CAO")
            insert_img(f"{name}_3_filter.png")
            doc.add_paragraph("Ý nghĩa các mục lọc:")
            add_annotations(annotations)
            clear_labels(page)
            page.keyboard.press("Escape")
            time.sleep(0.5)

            if has_form:
                # 4. ADD MODAL
                page.evaluate("""
                    const btn = Array.from(document.querySelectorAll('button, a')).find(el => 
                        el.textContent.includes('Thêm') || el.textContent.includes('Tải lên') || el.textContent.includes('Import')) || Array.from(document.querySelectorAll('button, a')).find(el => el.innerHTML.includes('M12 9v3m0 0v3'));
                    if(btn) btn.click();
                """)
                time.sleep(1.5)
                annotations = page.evaluate(JS_NUMBER_CONTAINER, "[x-dialog] .bg-white, .filament-modal .bg-white, div[role=\"dialog\"] .bg-white")
                page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_4_add.png"))
                doc.add_paragraph("4. FORM THÊM MỚI")
                insert_img(f"{name}_4_add.png")
                doc.add_paragraph("Mô tả các trường thông tin cần nhập:")
                add_annotations(annotations)
                clear_labels(page)
                page.keyboard.press("Escape")
                time.sleep(0.5)

                # 5. EDIT MODAL
                page.evaluate("""
                    const rowBtn = document.querySelector('.nttu-row-action-btn');
                    if(rowBtn) {
                        rowBtn.click();
                        setTimeout(() => {
                            const editBtn = Array.from(document.querySelectorAll('.nttu-row-action-menu button')).find(b => b.textContent.includes('Sửa') || b.textContent.includes('Chỉnh') || b.textContent.includes('Kiểm tra'));
                            if(editBtn) editBtn.click();
                        }, 300);
                    }
                """)
                time.sleep(1.5)
                annotations = page.evaluate(JS_NUMBER_CONTAINER, "[x-dialog] .bg-white, .filament-modal .bg-white, div[role=\"dialog\"] .bg-white")
                page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_5_edit.png"))
                doc.add_paragraph("5. FORM CHỈNH SỬA")
                insert_img(f"{name}_5_edit.png")
                doc.add_paragraph("Mô tả các trường thông tin:")
                add_annotations(annotations)
                clear_labels(page)
                page.keyboard.press("Escape")
                time.sleep(0.5)

                # 6. DELETE MODAL
                page.evaluate("""
                    const rowBtn = document.querySelector('.nttu-row-action-btn');
                    if(rowBtn) {
                        rowBtn.click();
                        setTimeout(() => {
                            const delBtn = Array.from(document.querySelectorAll('.nttu-row-action-menu button')).find(b => b.textContent.includes('Xóa'));
                            if(delBtn) delBtn.click();
                        }, 300);
                    }
                """)
                time.sleep(1.5)
                annotations = page.evaluate(JS_NUMBER_CONTAINER, ".swal2-popup")
                page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_6_delete.png"))
                doc.add_paragraph("6. CẢNH BÁO XÓA")
                insert_img(f"{name}_6_delete.png")
                doc.add_paragraph("Các nút xác nhận:")
                add_annotations(annotations)
                clear_labels(page)
                page.evaluate("""
                    const cancelBtn = document.querySelector('.swal2-cancel');
                    if(cancelBtn) cancelBtn.click();
                """)
                time.sleep(0.5)

            doc.add_page_break()
        except Exception as e:
            print(f"Error on {name}: {e}")

    browser.close()

doc.save(r'd:\ktnb-app\HUONG_DAN_CO_CHU_THICH.docx')
print("Annotated manual built successfully.")
