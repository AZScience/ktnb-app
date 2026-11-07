import os
import time
from playwright.sync_api import sync_playwright
import docx
from docx.shared import Pt, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH

ROUTES = [
    # Dashboard
    ("Tổng quan", "/dashboard", "dashboard.png", "Màn hình đầu tiên sau khi đăng nhập. Cung cấp cái nhìn toàn cảnh về tình hình lớp học, số liệu vi phạm và các thông báo quan trọng trong ngày."),
    
    # Bộ danh mục
    ("Bộ danh mục > Chức vụ", "/personnel/positions", "positions.png", "Quản lý danh sách các chức vụ của cán bộ, giảng viên trong trường."),
    ("Bộ danh mục > Dãy nhà", "/personnel/building-blocks", "building-blocks.png", "Quản lý hệ thống dãy nhà, tòa nhà để phục vụ cho việc xếp phòng học."),
    ("Bộ danh mục > Đơn vị", "/personnel/departments", "departments.png", "Quản lý các Khoa, Phòng ban, Trung tâm trong trường."),
    ("Bộ danh mục > Phòng học", "/personnel/classrooms", "classrooms.png", "Quản lý chi tiết các phòng học, phòng thực hành và tình trạng của chúng."),
    ("Bộ danh mục > Giảng viên", "/personnel/lecturers", "lecturers.png", "Hồ sơ của tất cả giảng viên. Hỗ trợ tính năng Nhập/Xuất bằng file Excel để cập nhật hàng loạt."),
    ("Bộ danh mục > Nhân viên", "/personnel/employees", "employees.png", "Hồ sơ nhân viên, chuyên viên của trường."),
    ("Bộ danh mục > Sinh viên", "/personnel/students", "students.png", "Hồ sơ toàn bộ sinh viên trong trường. Hỗ trợ tìm kiếm nhanh theo MSSV."),
    ("Bộ danh mục > Quà tặng", "/personnel/gifts", "gifts.png", "Quản lý các loại quà tặng, phần thưởng dùng cho việc tuyên dương người tốt việc tốt."),
    ("Bộ danh mục > Vai trò", "/personnel/roles", "roles.png", "Thiết lập các vai trò (VD: Admin, Thanh tra) để cấp quyền truy cập hệ thống."),
    ("Bộ danh mục > Việc ghi nhận", "/personnel/recognitions", "recognitions.png", "Danh sách các loại lỗi vi phạm (VD: Vắng thi, Đi trễ) và hình thức xử lý."),
    ("Bộ danh mục > Việc phát sinh", "/personnel/incident-categories", "incident-categories.png", "Danh sách phân loại các sự cố, phát sinh ngoài ý muốn."),
    ("Bộ danh mục > Loại văn bản", "/personnel/document-types", "document-types.png", "Phân loại các hồ sơ, biểu mẫu, văn bản."),

    # Công cụ kiểm tra
    ("Công cụ kiểm tra > Cố vấn học tập", "/monitoring/homeroom", "homeroom.png", "Theo dõi lịch sinh hoạt lớp, điểm danh và đánh giá buổi sinh hoạt CVHT."),
    ("Công cụ kiểm tra > Lớp học online", "/monitoring/online", "online.png", "Giám sát các ca học trực tuyến qua Teams/Google Meet."),
    ("Công cụ kiểm tra > Lớp học trực tiếp", "/monitoring/in-person", "in-person.png", "Tra cứu lịch học trực tiếp tại các phòng, tòa nhà để tiến hành đi tuần tra."),
    ("Công cụ kiểm tra > Thi kết thúc môn", "/monitoring/exams", "exams.png", "Giám sát lịch thi, phòng thi và tình hình vắng/vi phạm quy chế thi."),
    ("Công cụ kiểm tra > Thực hành ngoài", "/monitoring/external-practice", "external-practice.png", "Theo dõi các nhóm sinh viên thực hành ngoài cơ sở, tại bệnh viện/doanh nghiệp."),
    ("Công cụ kiểm tra > Sinh viên vi phạm", "/monitoring/student-violations", "student-violations.png", "Nghiệp vụ cốt lõi: Ghi nhận vi phạm. Hỗ trợ [Camera AI] tự động đọc thẻ sinh viên/CCCD để điền form nhanh chóng."),
    ("Công cụ kiểm tra > Nhận - Trả tài sản", "/monitoring/asset-check", "asset-check.png", "Quản lý việc mượn/trả trang thiết bị (Micro, Remote, Chìa khóa) của giảng viên."),
    ("Công cụ kiểm tra > Tiếp nhận yêu cầu", "/monitoring/requests", "requests.png", "Ghi nhận và xử lý các yêu cầu hỗ trợ từ sinh viên/giảng viên."),
    ("Công cụ kiểm tra > Tiếp nhận đơn thư", "/monitoring/petitions", "petitions.png", "Quản lý các khiếu nại, phản ánh."),
    ("Công cụ kiểm tra > Quản lý hồ sơ", "/monitoring/document-records", "document-records.png", "Nơi lưu trữ và số hóa các biên bản giấy, công văn."),

    # Báo cáo thống kê
    ("Báo cáo > Báo cáo cuối ngày", "/reports/daily", "reports-daily.png", "Bảng thống kê toàn bộ vi phạm trong ngày. Có nút đồng bộ tự động lên hệ thống Google Sheets."),
    ("Báo cáo > Minh chứng ca trực", "/feedback", "feedback.png", "Tổng hợp các minh chứng ảnh/video của các ca trực ban."),
    ("Báo cáo > Thống kê việc KPH", "/reports/comprehensive", "reports-comprehensive.png", "Xuất báo cáo tổng hợp (Biên bản Word) định kỳ hàng tuần/tháng. Hỗ trợ lọc theo khoảng ngày và Cơ sở."),
    ("Báo cáo > Sinh viên vi phạm", "/reports/student-violations", "reports-student-violations.png", "Báo cáo chi tiết danh sách sinh viên bị ghi nhận lỗi."),
    ("Báo cáo > Người tốt việc tốt", "/reports/good-deeds", "reports-good-deeds.png", "Thống kê danh sách tuyên dương."),
    ("Báo cáo > Tiếp nhận yêu cầu", "/reports/request-reports", "reports-requests.png", "Báo cáo tiến độ xử lý yêu cầu."),
    ("Báo cáo > Tiếp nhận đơn thư", "/reports/incident-reports", "reports-incidents.png", "Thống kê tình hình xử lý khiếu nại."),

    # Thiết lập hệ thống
    ("Thiết lập > Lịch học theo ngày", "/settings/schedule", "schedule.png", "Import danh sách lớp học/lịch thi (từ file Excel) để hệ thống bắt đầu giám sát."),
    ("Thiết lập > Tham số hệ thống", "/settings/parameters", "parameters.png", "Nơi Admin điền các API Key (Google, AI, Email) và cấu hình vận hành cốt lõi."),
    ("Thiết lập > Phân quyền truy cập", "/settings/permissions", "permissions.png", "Kiểm soát rủi ro: Cấp hoặc rút quyền (Xem/Thêm/Sửa/Xóa) của bất kỳ nhóm tài khoản nào."),
    ("Thiết lập > Nhật ký truy cập", "/settings/access-log", "access-log.png", "Lưu vết mọi hành động (Log) của người dùng để truy cứu khi cần thiết."),
    ("Thiết lập > Sao lưu & Phục hồi", "/settings/backup", "backup.png", "Bảo vệ dữ liệu: Tạo bản sao lưu Database và khôi phục khi xảy ra sự cố."),
    ("Thiết lập > Quản lý file mã nguồn", "/settings/project-files", "project-files.png", "Truy cập trực tiếp và chỉnh sửa các file cấu hình hệ thống trên server."),

    # Công cụ hỗ trợ
    ("Hỗ trợ > Giám sát thực hành", "/monitoring/external-checkins", "external-checkins.png", "Kiểm tra check-in các ca thực hành đặc biệt."),
    ("Hỗ trợ > Giám sát Online", "/monitoring/online-classes", "online-classes.png", "Công cụ hỗ trợ rà soát link lớp học."),
    ("Hỗ trợ > Kho minh chứng", "/monitoring/evidence", "evidence.png", "Quản lý tập trung toàn bộ hình ảnh, video tải lên hệ thống."),
    ("Hỗ trợ > Tra cứu thông tin", "/ai/assistant", "ai.png", "Công cụ AI hỗ trợ tìm kiếm nhanh."),
    ("Hỗ trợ > Tra cứu văn bản", "/monitoring/document-lookup", "document-lookup.png", "Tìm kiếm nhanh các quy định, văn bản nội bộ."),
    ("Hỗ trợ > Bảng thảo luận", "/discussion", "discussion.png", "Kênh trao đổi nội bộ cho tổ thanh tra."),
    ("Hỗ trợ > Hộp thư nội bộ", "/messaging", "messaging.png", "Gửi nhận tin nhắn, thông báo giữa các nhân sự."),
]

BASE_URL = "http://localhost:8000"
SCREENSHOT_DIR = r"D:\ktnb-app\screenshots"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

print("Starting Playwright to capture screenshots...")
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1440, 'height': 900})
    page = context.new_page()
    
    # Auto login
    print(f"Logging in...")
    page.goto(f"{BASE_URL}/auto-login")
    time.sleep(2)  # Wait for dashboard to load
    
    for title, route, filename, _ in ROUTES:
        try:
            page.goto(f"{BASE_URL}{route}")
            page.wait_for_load_state("networkidle", timeout=5000)
            time.sleep(1) # Extra buffer for animations
            page.screenshot(path=os.path.join(SCREENSHOT_DIR, filename))
        except Exception as e:
            pass
            
    browser.close()

print("All screenshots captured. Building Word document...")

doc = docx.Document()

def add_heading_doc(text, level, align=WD_ALIGN_PARAGRAPH.LEFT):
    h = doc.add_heading(text, level)
    h.alignment = align
    return h

def insert_image_doc(path, width=Inches(6)):
    if os.path.exists(path):
        doc.add_picture(path, width=width)
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER

add_heading_doc('HƯỚNG DẪN SỬ DỤNG HỆ THỐNG KTNB (CHI TIẾT VÀ CỤ THỂ)', 0, WD_ALIGN_PARAGRAPH.CENTER)
doc.add_paragraph('Tài liệu hướng dẫn sử dụng chi tiết mọi chức năng theo đúng cấu trúc menu, đính kèm hình ảnh giao diện thực tế của hệ thống.').alignment = WD_ALIGN_PARAGRAPH.CENTER
doc.add_page_break()

# Hướng dẫn thao tác chung
add_heading_doc('CÁC THAO TÁC CƠ BẢN TRÊN BẢNG DỮ LIỆU', 1)
doc.add_paragraph('Ở hầu hết các tính năng danh sách, hệ thống sử dụng chung một giao diện chuẩn bao gồm:')
doc.add_paragraph('- Tìm kiếm: Góc trên bên phải bảng dữ liệu.', style='List Bullet')
doc.add_paragraph('- Bộ lọc nâng cao: Biểu tượng phễu lọc để hiển thị các tiêu chí tùy chỉnh.', style='List Bullet')
doc.add_paragraph('- Phân trang: Nhập trực tiếp hoặc chọn số dòng muốn xem ở góc dưới bảng dữ liệu.', style='List Bullet')
doc.add_paragraph('- Import/Export: Các nút tải lên từ Excel hoặc xuất ra Excel được đặt trên thanh công cụ.', style='List Bullet')

current_category = ""
for title, _, filename, desc in ROUTES:
    parts = title.split(' > ')
    if len(parts) == 1:
        # Top level (Dashboard)
        cat = parts[0]
        name = parts[0]
    else:
        cat = parts[0]
        name = parts[1]
        
    if cat != current_category:
        current_category = cat
        doc.add_page_break()
        add_heading_doc(current_category.upper(), 1)
        
    add_heading_doc(name, 2)
    doc.add_paragraph(desc)
    
    img_path = os.path.join(SCREENSHOT_DIR, filename)
    insert_image_doc(img_path)

doc.save(r'd:\ktnb-app\Huong_Dan_Su_Dung_Kem_Anh_That.docx')
print("Complete document generated successfully: Huong_Dan_Su_Dung_Kem_Anh_That.docx")
