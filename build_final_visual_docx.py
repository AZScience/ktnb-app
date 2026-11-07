import os
import traceback
from PIL import Image
import docx
from docx.shared import Pt, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH

SCREENSHOT_DIR = r"D:\ktnb-app\screenshots"

# Create crops from departments.png
deps_img_path = os.path.join(SCREENSHOT_DIR, "departments.png")
crops_dir = os.path.join(SCREENSHOT_DIR, "crops")
os.makedirs(crops_dir, exist_ok=True)

btn_add = os.path.join(crops_dir, "btn_add.png")
input_search = os.path.join(crops_dir, "input_search.png")
btn_actions = os.path.join(crops_dir, "btn_actions.png")
pagination = os.path.join(crops_dir, "pagination.png")

if os.path.exists(deps_img_path):
    img = Image.open(deps_img_path)
    w, h = img.size
    
    # Estimate coordinates based on 1440x900 viewport
    # Top right for Add Button & Search
    img.crop((w - 400, 50, w - 20, 120)).save(btn_add)
    img.crop((w - 400, 100, w - 20, 150)).save(input_search)
    
    # Right side of a table row for actions
    img.crop((w - 200, 200, w - 50, 250)).save(btn_actions)
    
    # Bottom for pagination
    img.crop((250, h - 100, w - 50, h - 20)).save(pagination)

doc = docx.Document()

def add_heading(text, level, align=WD_ALIGN_PARAGRAPH.LEFT):
    h = doc.add_heading(text, level)
    h.alignment = align
    return h

def insert_img(path, width=Inches(6)):
    if os.path.exists(path):
        doc.add_picture(path, width=width)
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
    else:
        doc.add_paragraph(f"[LỖI: KHÔNG TÌM THẤY ẢNH TẠI {path}]").alignment = WD_ALIGN_PARAGRAPH.CENTER

add_heading('SỔ TAY HƯỚNG DẪN SỬ DỤNG HỆ THỐNG KTNB', 0, WD_ALIGN_PARAGRAPH.CENTER)
doc.add_paragraph('Tài liệu hướng dẫn sử dụng SIÊU TRỰC QUAN.').alignment = WD_ALIGN_PARAGRAPH.CENTER
doc.add_page_break()

# -------------------------------------
# PHẦN 1: THAO TÁC CƠ BẢN TRỰC QUAN
# -------------------------------------
add_heading('PHẦN 1: CÁC THAO TÁC CƠ BẢN DÙNG CHUNG', 1)
doc.add_paragraph('Hệ thống thiết kế đồng bộ. Khi gặp một bảng danh sách bất kỳ, bạn sẽ sử dụng các thao tác sau:')

add_heading('1. Thêm mới dữ liệu', 2)
doc.add_paragraph('Để thêm dữ liệu, hãy tìm nút Thêm mới ở góc trên bên phải màn hình: ')
insert_img(btn_add, Inches(4))

add_heading('2. Tìm kiếm nhanh', 2)
doc.add_paragraph('Để tìm kiếm, hãy gõ từ khóa vào ô tìm kiếm ở phía trên bảng dữ liệu: ')
insert_img(input_search, Inches(4))

add_heading('3. Chỉnh sửa và Xóa', 2)
doc.add_paragraph('Ở cột cuối cùng (cột Hành động) của mỗi dòng dữ liệu sẽ có các nút thao tác: ')
insert_img(btn_actions, Inches(2))

add_heading('4. Chuyển trang và Đổi số dòng', 2)
doc.add_paragraph('Ở dưới cùng của bảng là khu vực phân trang: ')
insert_img(pagination, Inches(5))

doc.add_page_break()

# -------------------------------------
# PHẦN 2: CHI TIẾT CÁC CHỨC NĂNG
# -------------------------------------
add_heading('PHẦN 2: HƯỚNG DẪN CHI TIẾT TỪNG MÀN HÌNH', 1)

def add_module(cat, name, filename, desc):
    add_heading(f'{cat} > {name}', 2)
    doc.add_paragraph(desc)
    path = os.path.join(SCREENSHOT_DIR, filename)
    insert_img(path)
    
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

try:
    doc.save(r'd:\ktnb-app\HUONG_DAN_CHOT.docx')
    print("Success")
except Exception as e:
    print(f"Error: {e}")
    traceback.print_exc()
