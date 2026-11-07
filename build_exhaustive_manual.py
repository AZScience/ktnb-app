import os
import time
from playwright.sync_api import sync_playwright
import docx
from docx.shared import Pt, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH

BASE_URL = "http://localhost:8000"
SCREENSHOT_DIR = r"D:\ktnb-app\screenshots\exhaustive"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

def inject_pointer(page, selector, number):
    page.evaluate(f"""
        try {{
            const el = {selector};
            if (el) {{
                el.style.outline = '4px solid red';
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

def clear_labels(page):
    page.evaluate("""
        document.querySelectorAll('div').forEach(el => {
            if(el.style.background === 'red' && el.style.borderRadius === '50%') el.remove();
        });
        document.querySelectorAll('*').forEach(el => {
            if(el.style.outline === 'red solid 4px') el.style.outline = '';
        });
    """)

print("Capturing 5 interaction states per function...")
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1440, 'height': 900})
    page = context.new_page()
    page.goto(f"{BASE_URL}/auto-login")
    time.sleep(2)

    modules = [
        ("Đại diện Danh mục", "departments", "/personnel/departments"),
        ("Sinh viên vi phạm", "violations", "/monitoring/student-violations"),
        ("Lịch học theo ngày", "schedule", "/settings/schedule"),
        ("Nhận trả tài sản", "asset", "/monitoring/asset-check")
    ]

    for title, name, route in modules:
        try:
            page.goto(f"{BASE_URL}{route}")
            page.wait_for_load_state("load")
            time.sleep(1)

            # 1. LIST SCREEN
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_1_list.png"))

            # 2. COLUMNS (Cài đặt hiển thị)
            page.evaluate("""
                const btn = document.querySelector('button[title="Cài đặt hiển thị"]');
                if(btn) btn.click();
            """)
            time.sleep(1)
            inject_pointer(page, "document.querySelector('.nttu-report-settings-panel')", "1")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_2_columns.png"))
            clear_labels(page)
            page.evaluate("document.body.click();") # close popover
            time.sleep(0.5)

            # 3. FILTER (Bộ lọc)
            page.evaluate("""
                const btn = document.querySelector('button[title="Bộ lọc nâng cao"]');
                if(btn) btn.click();
            """)
            time.sleep(1)
            inject_pointer(page, "document.querySelector('.relative.z-10.w-full')", "1")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_3_filter.png"))
            clear_labels(page)
            page.keyboard.press("Escape") # close modal
            time.sleep(0.5)

            # 4. ADD MODAL
            page.evaluate("""
                const btn = Array.from(document.querySelectorAll('button, a')).find(el => el.textContent.includes('Thêm') || el.textContent.includes('Tải lên') || el.textContent.includes('Import'));
                if(btn) btn.click();
            """)
            time.sleep(1)
            inject_pointer(page, "document.querySelector('[x-dialog] .bg-white, .filament-modal .bg-white, div[role=\"dialog\"] .bg-white')", "1")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_4_add.png"))
            clear_labels(page)
            page.keyboard.press("Escape")
            time.sleep(0.5)

            # 5. EDIT MODAL (click row action -> Edit)
            page.evaluate("""
                const rowBtn = document.querySelector('.nttu-row-action-btn');
                if(rowBtn) {
                    rowBtn.click();
                    setTimeout(() => {
                        const editBtn = Array.from(document.querySelectorAll('.nttu-row-action-menu button')).find(b => b.textContent.includes('Sửa') || b.textContent.includes('Chỉnh'));
                        if(editBtn) editBtn.click();
                    }, 300);
                }
            """)
            time.sleep(1.5)
            inject_pointer(page, "document.querySelector('[x-dialog] .bg-white, .filament-modal .bg-white, div[role=\"dialog\"] .bg-white')", "1")
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, f"{name}_5_edit.png"))
            clear_labels(page)
            page.keyboard.press("Escape")
            time.sleep(0.5)

            # 6. DELETE MODAL (click row action -> Delete)
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
            # Sweetalert is usually .swal2-container
            inject_pointer(page, "document.querySelector('.swal2-popup, [x-dialog] .bg-white')", "1")
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
        doc.add_picture(path, width=Inches(6))
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
    else:
        doc.add_paragraph(f"(Không có dữ liệu minh họa)").alignment = WD_ALIGN_PARAGRAPH.CENTER

doc.add_heading('SỔ TAY HƯỚNG DẪN: 100% GIAO DIỆN TƯƠNG TÁC', 0).alignment = WD_ALIGN_PARAGRAPH.CENTER

for title, name, route in modules:
    add_heading(f"Chức năng: {title}", 1)
    
    doc.add_paragraph("1. MÀN HÌNH CHÍNH (DANH SÁCH)")
    insert_img(f"{name}_1_list.png")
    
    doc.add_paragraph("2. ẨN/HIỆN CỘT DỮ LIỆU (Bấm nút Cài đặt hiển thị ở góc phải bảng)")
    insert_img(f"{name}_2_columns.png")
    
    doc.add_paragraph("3. BỘ LỌC NÂNG CAO (Bấm nút hình cái phễu)")
    insert_img(f"{name}_3_filter.png")
    
    doc.add_paragraph("4. FORM THÊM MỚI (Bấm nút Thêm mới/Tải lên)")
    insert_img(f"{name}_4_add.png")
    
    doc.add_paragraph("5. FORM CHỈNH SỬA (Bấm nút Hành động -> Chọn Sửa)")
    insert_img(f"{name}_5_edit.png")
    
    doc.add_paragraph("6. CẢNH BÁO XÓA (Bấm nút Hành động -> Chọn Xóa)")
    insert_img(f"{name}_6_delete.png")
    
    doc.add_page_break()

doc.save(r'd:\ktnb-app\HUONG_DAN_DU_FULL_5_THAO_TAC.docx')
print("Exhaustive manual built successfully.")
