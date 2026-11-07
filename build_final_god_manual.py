import os
import time
from playwright.sync_api import sync_playwright
import docx
from docx.shared import Pt, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH

BASE_URL = "http://localhost:8000"
SCREENSHOT_DIR = r"D:\ktnb-app\screenshots\god"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

JS_NUMBER_LIST = """
try {
    const btnColumns = document.querySelector('button[title="Cài đặt hiển thị"]');
    const btnFilter = document.querySelector('button[title="Bộ lọc nâng cao"]');
    const inputSearch = document.querySelector('input[placeholder*="Tìm kiếm"]');
    const btnAdd = Array.from(document.querySelectorAll('button, a')).find(el => el.textContent.includes('Thêm') || el.textContent.includes('Tải lên') || el.textContent.includes('Import') || el.textContent.includes('Xuất'));
    const btnAction = document.querySelector('.nttu-row-action-btn');
    
    const targets = [btnAdd, inputSearch, btnFilter, btnColumns, btnAction];
    let count = 1;
    targets.forEach(el => {
        if(el && el.getBoundingClientRect().width > 0) {
            el.style.outline = '4px solid red';
            const rect = el.getBoundingClientRect();
            const label = document.createElement('div');
            label.className = 'my-red-badge';
            label.textContent = count++;
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
        }
    });
} catch(e) {}
"""

JS_NUMBER_CONTAINER = """
(containerSelector) => {
    try {
        const container = document.querySelector(containerSelector);
        if(!container) return;
        const elements = container.querySelectorAll('input:not([type="hidden"]), select, textarea, button, [type="checkbox"], .nttu-row-action-menu button');
        let count = 1;
        elements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if(rect.width > 5 && rect.height > 5 && window.getComputedStyle(el).display !== 'none') {
                el.style.outline = '3px solid red';
                const label = document.createElement('div');
                label.className = 'my-red-badge';
                label.textContent = count++;
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
            }
        });
    } catch(e) {}
}
"""

def clear_labels(page):
    page.evaluate("""
        document.querySelectorAll('.my-red-badge').forEach(el => el.remove());
        document.querySelectorAll('*').forEach(el => {
            if(el.style.outline.includes('red')) el.style.outline = '';
        });
    """)

print("Capturing 5 interaction states per function for ALL 15 functions...")
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

            # 1. LIST SCREEN
            page.evaluate(JS_NUMBER_LIST)
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_1_list.png"))
            clear_labels(page)

            # 2. COLUMNS (Cài đặt hiển thị)
            page.evaluate("""
                const btn = document.querySelector('button[title="Cài đặt hiển thị"]');
                if(btn) btn.click();
            """)
            time.sleep(1)
            page.evaluate(JS_NUMBER_CONTAINER, ".nttu-report-settings-panel")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_2_columns.png"))
            clear_labels(page)
            page.evaluate("document.body.click();")
            time.sleep(0.5)

            # 3. FILTER (Bộ lọc)
            page.evaluate("""
                const btn = document.querySelector('button[title="Bộ lọc nâng cao"]');
                if(btn) btn.click();
            """)
            time.sleep(1)
            page.evaluate(JS_NUMBER_CONTAINER, ".relative.z-10.w-full.max-w-2xl")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_3_filter.png"))
            clear_labels(page)
            page.keyboard.press("Escape")
            time.sleep(0.5)

            if has_form:
                # 4. ADD MODAL
                page.evaluate("""
                    const btn = Array.from(document.querySelectorAll('button, a')).find(el => el.textContent.includes('Thêm') || el.textContent.includes('Tải lên') || el.textContent.includes('Import'));
                    if(btn) btn.click();
                """)
                time.sleep(1.5)
                page.evaluate(JS_NUMBER_CONTAINER, "[x-dialog] .bg-white, .filament-modal .bg-white, div[role=\"dialog\"] .bg-white")
                page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_4_add.png"))
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
                page.evaluate(JS_NUMBER_CONTAINER, "[x-dialog] .bg-white, .filament-modal .bg-white, div[role=\"dialog\"] .bg-white")
                page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_5_edit.png"))
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
                page.evaluate(JS_NUMBER_CONTAINER, ".swal2-popup")
                page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_6_delete.png"))
                clear_labels(page)
                page.evaluate("""
                    const cancelBtn = document.querySelector('.swal2-cancel');
                    if(cancelBtn) cancelBtn.click();
                """)
                time.sleep(0.5)

        except Exception as e:
            print(f"Error on {name}: {e}")

    browser.close()

# COMPILE DOCX
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

doc.add_heading('SỔ TAY HƯỚNG DẪN SỬ DỤNG: TƯƠNG TÁC ĐẾN TỪNG Ô NHẬP LIỆU (ĐẦY ĐỦ 15 CHỨC NĂNG)', 0).alignment = WD_ALIGN_PARAGRAPH.CENTER
doc.add_paragraph('Ở mỗi bức ảnh, hệ thống đã tự động khoanh đỏ và đánh số [1], [2], [3]... cho tất cả các đối tượng tương tác (ô chữ, menu xổ xuống, nút bấm, ô check). Bạn chỉ cần click theo thứ tự đánh số là xong.').alignment = WD_ALIGN_PARAGRAPH.CENTER

for title, name, route, has_form in modules:
    add_heading(f"Chức năng: {title}", 1)
    
    doc.add_paragraph("1. MÀN HÌNH CHÍNH (DANH SÁCH)")
    insert_img(f"{name}_1_list.png")
    
    doc.add_paragraph("2. ẨN/HIỆN CỘT DỮ LIỆU (Chọn các ô Checkbox để hiển thị hoặc ẩn bớt các cột)")
    insert_img(f"{name}_2_columns.png")
    
    doc.add_paragraph("3. BỘ LỌC NÂNG CAO (Điền các ô điều kiện để lọc danh sách)")
    insert_img(f"{name}_3_filter.png")
    
    if has_form:
        doc.add_paragraph("4. FORM THÊM MỚI (Nhập đầy đủ thông tin vào các ô được đánh số từ trên xuống dưới, sau đó bấm nút Lưu ở cuối)")
        insert_img(f"{name}_4_add.png")
        
        doc.add_paragraph("5. FORM CHỈNH SỬA / KIỂM TRA (Sửa lại dữ liệu cũ)")
        insert_img(f"{name}_5_edit.png")
        
        doc.add_paragraph("6. CẢNH BÁO XÓA (Xác nhận việc xóa dòng dữ liệu)")
        insert_img(f"{name}_6_delete.png")
    
    doc.add_page_break()

doc.save(r'd:\ktnb-app\HUONG_DAN_TUYET_DOI_CHI_TIET.docx')
print("Exhaustive god manual built successfully.")
