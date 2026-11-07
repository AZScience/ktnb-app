import os
import time
from playwright.sync_api import sync_playwright
import docx
from docx.shared import Pt, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH

SCREENSHOT_DIR = r"D:\ktnb-app\screenshots"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)
BASE_URL = "http://localhost:8000"

MISSING_ROUTES = [
    ("/personnel/positions", "positions.png"),
    ("/personnel/building-blocks", "building-blocks.png"),
    ("/monitoring/in-person", "in-person.png"),
    ("/monitoring/exams", "exams.png"),
    ("/monitoring/external-practice", "external-practice.png"),
    ("/settings/permissions", "permissions.png"),
]

print("Starting Playwright to capture missing screenshots and UI elements...")
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1440, 'height': 900})
    page = context.new_page()
    
    # Auto login
    page.goto(f"{BASE_URL}/auto-login")
    time.sleep(2)
    
    # Capture missing full pages with "load" instead of "networkidle"
    for route, filename in MISSING_ROUTES:
        try:
            page.goto(f"{BASE_URL}{route}")
            page.wait_for_load_state("load", timeout=10000)
            time.sleep(1)
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, filename))
        except Exception as e:
            pass

    # Capture specific UI elements from a typical page (e.g., departments)
    try:
        page.goto(f"{BASE_URL}/personnel/departments")
        page.wait_for_load_state("load", timeout=10000)
        time.sleep(1)
        
        # 1. Thêm mới button (Usually a button with flex, text-white, bg-blue-600)
        # Using a generic locator for the primary button on top right
        add_btn = page.locator('button.bg-blue-600, a.bg-blue-600').first
        if add_btn.is_visible():
            add_btn.screenshot(path=os.path.join(SCREENSHOT_DIR, "btn_add.png"))
            
        # 2. Search bar
        search_input = page.locator('input[type="text"][placeholder*="Tìm kiếm"]').first
        if search_input.is_visible():
            search_input.screenshot(path=os.path.join(SCREENSHOT_DIR, "input_search.png"))
            
        # 3. Action buttons (Edit/Delete) in the table
        action_td = page.locator('table tbody tr:first-child td:last-child').first
        if action_td.is_visible():
            action_td.screenshot(path=os.path.join(SCREENSHOT_DIR, "btn_actions.png"))
            
        # 4. Pagination
        pagination = page.locator('.nttu-pagination-wrapper, nav[aria-label="Pagination"]').first
        if pagination.is_visible():
            pagination.screenshot(path=os.path.join(SCREENSHOT_DIR, "pagination.png"))
    except Exception as e:
        pass

    browser.close()

print("Building highly visual Word document...")

doc = docx.Document()

def add_heading(text, level, align=WD_ALIGN_PARAGRAPH.LEFT):
    h = doc.add_heading(text, level)
    h.alignment = align
    return h

def insert_img(filename, width=Inches(6)):
    path = os.path.join(SCREENSHOT_DIR, filename)
    if os.path.exists(path):
        doc.add_picture(path, width=width)
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
        
def insert_inline_img(filename, paragraph):
    path = os.path.join(SCREENSHOT_DIR, filename)
    if os.path.exists(path):
        run = paragraph.add_run()
        run.add_picture(path, height=Inches(0.4))

add_heading('SỔ TAY HƯỚNG DẪN SỬ DỤNG HỆ THỐNG KTNB', 0, WD_ALIGN_PARAGRAPH.CENTER)
doc.add_paragraph('Tài liệu hướng dẫn sử dụng SIÊU TRỰC QUAN. Mọi thao tác đều có hình ảnh nút bấm thực tế minh họa.').alignment = WD_ALIGN_PARAGRAPH.CENTER
doc.add_page_break()

# -------------------------------------
# PHẦN 1: THAO TÁC CƠ BẢN TRỰC QUAN
# -------------------------------------
add_heading('PHẦN 1: CÁC THAO TÁC CƠ BẢN DÙNG CHUNG', 1)
doc.add_paragraph('Hệ thống thiết kế đồng bộ. Khi gặp một bảng danh sách bất kỳ, bạn sẽ sử dụng các thao tác sau:')

# Thêm mới
add_heading('1. Thêm mới dữ liệu', 2)
p1 = doc.add_paragraph('Để thêm dữ liệu, hãy tìm nút Thêm mới (màu xanh) ở góc trên bên phải màn hình: ')
insert_inline_img('btn_add.png', p1)
doc.add_paragraph('Click vào nút này, một cửa sổ sẽ hiện ra. Bạn nhập đầy đủ thông tin vào các ô và nhấn Lưu.')

# Tìm kiếm
add_heading('2. Tìm kiếm nhanh', 2)
p2 = doc.add_paragraph('Để tìm kiếm, hãy gõ từ khóa vào ô tìm kiếm ở phía trên bảng dữ liệu: ')
insert_inline_img('input_search.png', p2)
doc.add_paragraph('Dữ liệu sẽ tự động lọc ngay lập tức mà không cần nhấn Enter.')

# Sửa / Xóa
add_heading('3. Chỉnh sửa và Xóa', 2)
p3 = doc.add_paragraph('Ở cột cuối cùng (cột Hành động) của mỗi dòng dữ liệu sẽ có các nút thao tác: ')
insert_inline_img('btn_actions.png', p3)
doc.add_paragraph('- Nút màu Vàng/Xanh lá (hình cây bút): Dùng để Chỉnh sửa thông tin.')
doc.add_paragraph('- Nút màu Đỏ (hình thùng rác): Dùng để Xóa dòng dữ liệu đó.')

# Phân trang
add_heading('4. Chuyển trang và Đổi số dòng', 2)
p4 = doc.add_paragraph('Ở dưới cùng của bảng là khu vực phân trang: ')
insert_inline_img('pagination.png', p4)
doc.add_paragraph('Bạn có thể nhấn mũi tên để sang trang, hoặc nhấp vào con số để tự gõ số dòng muốn hiển thị (VD: 50, 100, 500).')

doc.add_page_break()

# -------------------------------------
# PHẦN 2: CHI TIẾT CÁC CHỨC NĂNG
# -------------------------------------
add_heading('PHẦN 2: HƯỚNG DẪN CHI TIẾT TỪNG MÀN HÌNH', 1)

def add_module(cat, name, filename, desc):
    add_heading(f'{cat} > {name}', 2)
    doc.add_paragraph(desc)
    insert_img(filename)
    
danh_muc = [
    ('Chức vụ', 'positions.png', 'Dùng nút Thêm mới để khai báo chức vụ.'),
    ('Dãy nhà', 'building-blocks.png', 'Dùng nút Thêm mới để tạo dãy nhà.'),
    ('Đơn vị', 'departments.png', 'Quản lý Khoa/Phòng ban. Có thể Sửa/Xóa bên cột Hành động.'),
    ('Phòng học', 'classrooms.png', 'Liệt kê các phòng học thuộc các dãy nhà.'),
    ('Giảng viên', 'lecturers.png', 'Sử dụng nút Import để nhập hàng loạt giảng viên từ file Excel.'),
    ('Nhân viên', 'employees.png', 'Quản lý nhân viên hành chính.'),
    ('Sinh viên', 'students.png', 'Quản lý hồ sơ sinh viên, dùng cho việc trừ điểm sau này.'),
    ('Vai trò', 'roles.png', 'Tạo vai trò và phân quyền.'),
    ('Việc ghi nhận', 'recognitions.png', 'Khai báo các lỗi vi phạm và điểm trừ.'),
    ('Việc phát sinh', 'incident-categories.png', 'Phân loại sự cố (hỏng máy chiếu...).'),
    ('Loại văn bản', 'document-types.png', 'Phân loại công văn.'),
]
for name, img, desc in danh_muc:
    add_module('Bộ danh mục', name, img, desc)

kiem_tra = [
    ('Lớp học trực tiếp', 'in-person.png', 'Chọn cơ sở và dãy nhà. Danh sách phòng học sẽ hiện ra. Bấm nút "Kiểm tra" ở dòng tương ứng để ghi nhận vi phạm.'),
    ('Thi kết thúc môn', 'exams.png', 'Giám sát phòng thi. Tương tự như kiểm tra lớp trực tiếp.'),
    ('Thực hành ngoài', 'external-practice.png', 'Giám sát sinh viên thực hành ngoài viện/doanh nghiệp.'),
    ('Sinh viên vi phạm', 'student-violations.png', '👉 TÍNH NĂNG ĐẶC BIỆT: Bấm nút "Quét Camera" để AI tự nhận diện thẻ sinh viên. Hoặc bấm Thêm mới để nhập tay MSSV.'),
    ('Nhận - Trả tài sản', 'asset-check.png', 'Bấm nút "Cho mượn" khi GV lấy thiết bị. Bấm "Đã trả" khi GV hoàn trả.'),
    ('Tiếp nhận yêu cầu', 'requests.png', 'Bấm Thêm mới để ghi nhận yêu cầu. Cập nhật trạng thái thành Đang xử lý/Hoàn thành.'),
    ('Tiếp nhận đơn thư', 'petitions.png', 'Quy trình tương tự chức năng Tiếp nhận yêu cầu.'),
    ('Quản lý hồ sơ', 'document-records.png', 'Bấm Upload để đăng tải file PDF công văn lên hệ thống lưu trữ.'),
]
for name, img, desc in kiem_tra:
    add_module('Công cụ kiểm tra', name, img, desc)

bao_cao = [
    ('Báo cáo cuối ngày', 'reports-daily.png', 'Chọn Ngày để xem. Bấm nút "Đồng bộ Google Sheets" để nạp dữ liệu lên Cloud.'),
    ('Thống kê việc KPH', 'reports-comprehensive.png', 'Chọn Khoảng thời gian -> Bấm nút "Xuất báo cáo Word" để tải biên bản vi phạm KPH về máy.'),
    ('Báo cáo sinh viên vi phạm', 'reports-student-violations.png', 'Bộ lọc tìm kiếm sinh viên vi phạm theo khoảng ngày.')
]
for name, img, desc in bao_cao:
    add_module('Báo cáo thống kê', name, img, desc)
    
thiet_lap = [
    ('Lịch học theo ngày', 'schedule.png', '👉 BẮT BUỘC: Hàng ngày, bấm nút Import Excel để nạp lịch học vào hệ thống để bắt đầu đi tuần tra.'),
    ('Phân quyền truy cập', 'permissions.png', 'Chọn một Vai trò và tick vào các ô Xem/Thêm/Sửa/Xóa.'),
    ('Nhật ký truy cập', 'access-log.png', 'Theo dõi toàn bộ lịch sử các thao tác Thêm/Sửa/Xóa của mọi tài khoản.'),
]
for name, img, desc in thiet_lap:
    add_module('Thiết lập hệ thống', name, img, desc)

doc.save(r'd:\ktnb-app\HUONG_DAN_TRUC_QUAN_SIEU_CHI_TIET.docx')
print("Visual manual built successfully.")
