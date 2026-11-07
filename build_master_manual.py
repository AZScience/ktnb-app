import os
import time
from playwright.sync_api import sync_playwright
import docx
from docx.shared import Pt, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH

BASE_URL = "http://localhost:8000"
SCREENSHOT_DIR = r"D:\ktnb-app\screenshots\master"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

def inject_number(page, selector, number):
    page.evaluate(f"""
        try {{
            const el = {selector};
            if (el) {{
                el.style.outline = '4px solid red';
                el.style.outlineOffset = '2px';
                
                const label = document.createElement('div');
                label.textContent = '{number}';
                label.style.background = 'red';
                label.style.color = 'white';
                label.style.padding = '4px 10px';
                label.style.fontSize = '20px';
                label.style.fontWeight = 'bold';
                label.style.borderRadius = '50%';
                label.style.zIndex = '999999';
                
                document.body.appendChild(label);
                const rect = el.getBoundingClientRect();
                label.style.position = 'fixed';
                label.style.top = (rect.top - 10) + 'px';
                label.style.left = (rect.left - 10) + 'px';
            }}
        }} catch(e) {{}}
    """)

def auto_label_modal(page):
    # Finds all inputs in the active modal and numbers them 1,2,3...
    page.evaluate("""
        try {
            // Find active modal
            const modals = Array.from(document.querySelectorAll('[x-dialog], .filament-modal, div[role="dialog"]')).filter(el => el.getBoundingClientRect().width > 0);
            if (modals.length > 0) {
                const modal = modals[0];
                const fields = modal.querySelectorAll('input:not([type="hidden"]), select, textarea, button[type="submit"]');
                let count = 1;
                fields.forEach(el => {
                    // Filter out invisible ones
                    if (el.getBoundingClientRect().width > 0) {
                        el.style.outline = '3px solid red';
                        const label = document.createElement('div');
                        label.textContent = count++;
                        label.style.background = 'red';
                        label.style.color = 'white';
                        label.style.padding = '2px 8px';
                        label.style.fontSize = '16px';
                        label.style.fontWeight = 'bold';
                        label.style.borderRadius = '50%';
                        label.style.zIndex = '999999';
                        document.body.appendChild(label);
                        const rect = el.getBoundingClientRect();
                        label.style.position = 'fixed';
                        label.style.top = (rect.top - 10) + 'px';
                        label.style.left = (rect.left - 10) + 'px';
                    }
                });
            }
        } catch(e) {}
    """)

def clear_labels(page):
    page.evaluate("""
        document.querySelectorAll('div').forEach(el => {
            if(el.style.background === 'red' && el.style.borderRadius === '50%') el.remove();
        });
        document.querySelectorAll('*').forEach(el => {
            if(el.style.outline === 'red solid 4px' || el.style.outline === 'red solid 3px') el.style.outline = '';
        });
    """)

print("Starting deep UI capture...")
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1440, 'height': 900})
    page = context.new_page()
    page.goto(f"{BASE_URL}/auto-login")
    time.sleep(2)

    modules = [
        ("departments", "/personnel/departments", True),
        ("student-violations", "/monitoring/student-violations", True),
        ("in-person", "/monitoring/in-person", True),
        ("asset-check", "/monitoring/asset-check", True),
        ("requests", "/monitoring/requests", True),
        ("schedule", "/settings/schedule", True),
        ("comprehensive", "/reports/comprehensive", False),
    ]

    for name, route, has_modal in modules:
        try:
            page.goto(f"{BASE_URL}{route}")
            page.wait_for_load_state("load")
            time.sleep(1)
            
            # Label List View
            inject_number(page, "Array.from(document.querySelectorAll('button, a')).find(el => el.textContent.includes('Thêm') || el.textContent.includes('Tải lên') || el.textContent.includes('Import') || el.textContent.includes('Xuất'))", "1")
            inject_number(page, "document.querySelector('input[placeholder*=\"Tìm kiếm\"], input[type=\"date\"]')", "2")
            inject_number(page, "document.querySelector('table tbody tr td:last-child')", "3")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_list.png"))
            
            clear_labels(page)

            if has_modal:
                # Click Add button to open modal
                page.evaluate("""
                    const btn = Array.from(document.querySelectorAll('button, a')).find(el => el.textContent.includes('Thêm') || el.textContent.includes('Tải lên') || el.textContent.includes('Import'));
                    if (btn) btn.click();
                """)
                time.sleep(2) # wait for modal animation
                
                # Auto label fields in modal
                auto_label_modal(page)
                page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_modal.png"))
        except Exception as e:
            print(f"Failed {name}: {e}")

    browser.close()

# BUILD DOCX
doc = docx.Document()

def add_h1(text):
    h = doc.add_heading(text, 1)
    for run in h.runs: run.font.color.rgb = RGBColor(0, 80, 160)

def add_h2(text):
    h = doc.add_heading(text, 2)
    for run in h.runs: run.font.color.rgb = RGBColor(0, 100, 0)

def insert_img(filename):
    path = os.path.join(SCREENSHOT_DIR, filename)
    if os.path.exists(path):
        doc.add_picture(path, width=Inches(6.5))
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER

doc.add_heading('HƯỚNG DẪN SỬ DỤNG CHI TIẾT CẤP ĐỘ FORM (FIELD-LEVEL)', 0).alignment = WD_ALIGN_PARAGRAPH.CENTER
doc.add_paragraph('Tài liệu mổ xẻ chi tiết từng ô nhập liệu trên hệ thống.').alignment = WD_ALIGN_PARAGRAPH.CENTER

content_map = [
    (
        "1. QUẢN LÝ BỘ DANH MỤC (Đại diện: Đơn vị/Phòng ban)",
        "departments",
        "Màn hình ngoài: [1] Nút Thêm mới. [2] Ô Tìm kiếm. [3] Cột Thao tác (Sửa/Xóa).",
        "Bên trong Form Thêm mới: [1] Ô Tên Đơn vị (VD: Khoa CNTT). [2] Ô Ghi chú. [3] Nút Lưu lại (Submit). Khi bạn điền xong các ô từ [1] đến [2], hãy bấm [3] để hoàn tất."
    ),
    (
        "2. GHI NHẬN SINH VIÊN VI PHẠM",
        "student-violations",
        "Màn hình ngoài: [1] Nút Quét Camera/Thêm mới. [2] Khung tra cứu. [3] Cột sửa xóa biên bản.",
        "Bên trong Form Ghi nhận: Form này rất quan trọng. [1] Ô Mã số SV (gõ MSSV vào). [2] Danh sách xổ xuống chọn Lỗi vi phạm (VD: Đi trễ). [3] Nút chọn tệp để Upload ảnh bằng chứng. [4] Nút Lưu lại. Hãy nhập theo đúng số thứ tự để lưu biên bản."
    ),
    (
        "3. KIỂM TRA LỚP HỌC TRỰC TIẾP",
        "in-person",
        "Màn hình ngoài: [1] Nút Bộ lọc (Lọc theo cơ sở/dãy nhà). [2] Ô Tìm kiếm. [3] Cột Hành động để bấm Kiểm tra.",
        "Bên trong Form Kiểm tra: [1] Ô Đánh giá chung (Tốt/Khá/Tệ). [2] Ô Hiện trạng chi tiết (VD: Sinh viên ồn ào). [3] Nút Lưu Đánh Giá."
    ),
    (
        "4. QUẢN LÝ NHẬN - TRẢ TÀI SẢN",
        "asset-check",
        "Màn hình ngoài: [1] Nút Thêm giao dịch (Khi GV đến mượn). [2] Tìm kiếm giao dịch cũ. [3] Sửa (Khi GV trả).",
        "Bên trong Form: [1] Chọn Giảng viên. [2] Chọn Thiết bị. [3] Nút Lưu. Sau khi lưu, trạng thái sẽ là Đang mượn."
    ),
    (
        "5. TIẾP NHẬN YÊU CẦU",
        "requests",
        "Màn hình ngoài: [1] Thêm Yêu cầu. [2] Lọc theo trạng thái Đang xử lý. [3] Nút Hành động.",
        "Bên trong Form: [1] Tên người yêu cầu. [2] Nội dung chi tiết. [3] Trạng thái. [4] Nút Lưu."
    ),
    (
        "6. IMPORT LỊCH HỌC TỪ EXCEL",
        "schedule",
        "Màn hình ngoài: [1] Nút Import (Tải lên). [2] Lọc ngày xem lịch.",
        "Bên trong Cửa sổ Import: [1] Nút Chọn tệp (Browse file). [2] Nút Tiến hành Import. Bạn bấm số 1 chọn file, rồi bấm số 2 để chạy."
    )
]

for title, name, list_desc, modal_desc in content_map:
    add_h1(title)
    
    add_h2("A. Màn Hình Danh Sách")
    doc.add_paragraph(list_desc)
    insert_img(f"{name}_list.png")
    
    add_h2("B. Màn Hình Form Tương Tác")
    doc.add_paragraph(modal_desc)
    insert_img(f"{name}_modal.png")
    
    doc.add_page_break()

# 7. Thong ke KPH
add_h1("7. XUẤT BÁO CÁO THỐNG KÊ KPH")
add_h2("A. Màn Hình Thống Kê")
doc.add_paragraph("Màn hình báo cáo: [1] Nút Xuất Báo Cáo (Tải file Word). [2] Khung chọn Từ Ngày - Đến Ngày. Nhập xong số [2] thì bấm số [1] để lấy file về.")
insert_img("comprehensive_list.png")

try:
    doc.save(r'd:\ktnb-app\HUONG_DAN_TUTORIAL_FULL.docx')
    print("Master manual generated successfully!")
except Exception as e:
    print(f"Error saving DOCX: {e}")
