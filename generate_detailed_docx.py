import os
import docx
from docx.shared import Pt, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH

doc = docx.Document()

def add_heading(text, level, align=WD_ALIGN_PARAGRAPH.LEFT):
    h = doc.add_heading(text, level)
    h.alignment = align
    return h

def add_step(text):
    doc.add_paragraph(text, style='List Number')

def add_bullet(text):
    doc.add_paragraph(text, style='List Bullet')

def insert_img(filename, width=Inches(6)):
    path = os.path.join(r"D:\ktnb-app\screenshots", filename)
    if os.path.exists(path):
        doc.add_picture(path, width=width)
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER

add_heading('SỔ TAY HƯỚNG DẪN SỬ DỤNG HỆ THỐNG KTNB', 0, WD_ALIGN_PARAGRAPH.CENTER)
doc.add_paragraph('Tài liệu hướng dẫn sử dụng chi tiết từng thao tác của mọi chức năng trên hệ thống phần mềm Kiểm Tra Nội Bộ (KTNB).').alignment = WD_ALIGN_PARAGRAPH.CENTER
doc.add_page_break()

# -------------------------------------
# PHẦN 1: THAO TÁC CƠ BẢN
# -------------------------------------
add_heading('PHẦN 1: CÁC THAO TÁC CƠ BẢN DÙNG CHUNG', 1)
doc.add_paragraph('Hệ thống được thiết kế đồng bộ. Ở hầu hết các màn hình danh sách (như Danh sách Sinh viên, Danh sách Đơn vị...), người dùng đều có thể thực hiện các thao tác chung sau đây:')

add_heading('1. Thêm mới dữ liệu', 2)
add_step('Nhấn vào nút "Thêm mới" (màu xanh dương) ở góc trên bên phải màn hình.')
add_step('Hệ thống sẽ hiển thị một cửa sổ (Popup) hoặc chuyển sang trang Form nhập liệu.')
add_step('Điền đầy đủ thông tin vào các trường (các ô có dấu * màu đỏ là bắt buộc nhập).')
add_step('Nhấn "Lưu lại" (hoặc "Hoàn tất") để lưu thông tin vào hệ thống.')

add_heading('2. Chỉnh sửa và Xóa dữ liệu', 2)
add_step('Tại bảng dữ liệu, tìm đến dòng thông tin cần thao tác.')
add_step('Cột cuối cùng (Hành động) sẽ có 2 nút: Nút hình cây bút (Chỉnh sửa) và Nút hình thùng rác (Xóa).')
add_step('Nhấn nút Chỉnh sửa để sửa lại thông tin, sau đó nhấn Lưu.')
add_step('Nhấn nút Xóa để xóa thông tin. Hệ thống sẽ hỏi xác nhận, nhấn OK để đồng ý xóa.')

add_heading('3. Tìm kiếm và Lọc dữ liệu', 2)
add_step('Sử dụng ô Tìm kiếm ở góc trên bên phải để gõ từ khóa (VD: tên, mã số). Hệ thống sẽ tự động lọc ngay lập tức.')
add_step('Để lọc chi tiết hơn, nhấn vào nút "Bộ lọc" (hình chiếc phễu). Chọn các tiêu chí mong muốn và nhấn "Áp dụng".')

add_heading('4. Nhập/Xuất Excel (Import/Export)', 2)
add_step('Nhấn nút "Tải lên" (Import) để nạp dữ liệu hàng loạt từ file Excel (chỉ hỗ trợ file theo mẫu).')
add_step('Nhấn nút "Tải xuống" (Export) để xuất bảng dữ liệu hiện tại ra file Excel để báo cáo.')
doc.add_page_break()

# -------------------------------------
# PHẦN 2: CHI TIẾT CÁC CHỨC NĂNG
# -------------------------------------
add_heading('PHẦN 2: HƯỚNG DẪN CHI TIẾT TỪNG CHỨC NĂNG (THEO MENU)', 1)

def add_module(cat, name, filename, desc, steps):
    add_heading(f'{cat} > {name}', 2)
    doc.add_paragraph(desc)
    insert_img(filename)
    if steps:
        doc.add_paragraph('📌 Hướng dẫn thao tác:')
        for s in steps:
            add_step(s)

add_module('Tổng quan', 'Dashboard', 'dashboard.png', 
    'Màn hình hiển thị ngay khi đăng nhập. Cung cấp cái nhìn toàn cảnh về dữ liệu trong ngày.',
    [
        'Xem thống kê tổng số lớp học Online, Lớp học trực tiếp và Lịch thi đang diễn ra.',
        'Cuộn xuống để xem tiến độ của từng ca/tiết học theo thanh thời gian thực.',
        'Nhấn vào các bảng thông báo để đọc thông báo nội bộ mới nhất.'
    ])

# BỘ DANH MỤC
cat = 'Bộ danh mục'
danh_muc = [
    ('Chức vụ', 'positions.png', 'Quản lý danh mục chức vụ.', ['Nhấn "Thêm mới" -> Nhập Tên chức vụ và Mã chức vụ -> Lưu.']),
    ('Dãy nhà', 'building-blocks.png', 'Quản lý dãy nhà, cơ sở vật chất.', ['Nhập Tên dãy nhà và Ghi chú để tạo dãy nhà mới phục vụ việc gán phòng học.']),
    ('Đơn vị', 'departments.png', 'Quản lý Khoa/Trung tâm/Phòng ban.', ['Quản lý danh sách các khoa. Có thể đính kèm thông tin Trưởng đơn vị nếu cần.']),
    ('Phòng học', 'classrooms.png', 'Quản lý chi tiết từng phòng.', ['Nhấn Thêm mới -> Chọn Dãy nhà trực thuộc -> Nhập Tên phòng (VD: A.101) -> Lưu.']),
    ('Giảng viên', 'lecturers.png', 'Hồ sơ Giảng viên.', ['Sử dụng chức năng "Nhập từ Excel" để cập nhật hàng trăm Giảng viên cùng lúc thay vì nhập tay.']),
    ('Nhân viên', 'employees.png', 'Hồ sơ Nhân viên.', ['Quản lý nhân sự khối hành chính. Thao tác tương tự chức năng Giảng viên.']),
    ('Sinh viên', 'students.png', 'Hồ sơ Sinh viên.', ['Đây là dữ liệu dùng để tự động nhận diện sinh viên khi vi phạm. Có thể tìm kiếm sinh viên theo MSSV.']),
    ('Quà tặng', 'gifts.png', 'Danh sách quà tặng.', ['Nhập Tên phần quà và Số lượng kho để quản lý số quà đã trao cho sinh viên có Việc tốt.']),
    ('Vai trò', 'roles.png', 'Quản lý chức danh hệ thống.', ['Tạo vai trò (Admin, User, Inspector) để dùng cho việc phân quyền hệ thống sau này.']),
    ('Việc ghi nhận (Lỗi vi phạm)', 'recognitions.png', 'Các lỗi vi phạm.', ['Thêm các lỗi (VD: Đi trễ, Đeo tai nghe) và quy định số điểm trừ/hình thức phạt tương ứng.']),
    ('Việc phát sinh', 'incident-categories.png', 'Sự cố phát sinh.', ['Phân loại các sự cố ngoài ý muốn (Hỏng máy chiếu, Mất điện) để báo cáo.']),
    ('Loại văn bản', 'document-types.png', 'Các loại công văn.', ['Thiết lập các loại văn bản (Biên bản, Tờ trình) cho chức năng Quản lý hồ sơ.']),
]
for name, img, desc, steps in danh_muc:
    add_module(cat, name, img, desc, steps)

# CÔNG CỤ KIỂM TRA
cat = 'Công cụ kiểm tra'
add_module(cat, 'Cố vấn học tập', 'homeroom.png', 'Theo dõi điểm danh sinh hoạt lớp.', [
    'Chọn Ngày và Ca học để lọc lịch sinh hoạt cố vấn học tập.',
    'Nhấn vào nút "Điểm danh" tại một lớp cụ thể để ghi nhận số lượng sinh viên vắng mặt.',
    'Ghi chú lại đánh giá buổi sinh hoạt (Tốt/Khá/Tệ).'
])
add_module(cat, 'Lớp học online', 'online.png', 'Kiểm tra tình trạng mở lớp online.', [
    'Truy cập trang, hệ thống sẽ hiện danh sách các lớp học Teams/Meet đang diễn ra.',
    'Nhấn "Kiểm tra" và điền trạng thái (Giảng viên có mặt đúng giờ không, link có hoạt động không).'
])
add_module(cat, 'Lớp học trực tiếp', 'in-person.png', 'Đi tuần tra lớp học thực tế.', [
    'Thanh tra viên cầm thiết bị di động/tablet, chọn Cơ sở và Dãy nhà đang đứng.',
    'Hệ thống hiện danh sách phòng học. Đi ngang qua phòng nào, nhấn nút "Kiểm tra phòng đó".',
    'Chọn trạng thái (Bình thường, Giảng viên vắng, Giảng viên đi trễ) và nhấn Lưu.'
])
add_module(cat, 'Thi kết thúc môn', 'exams.png', 'Giám sát lịch thi.', [
    'Xem danh sách phòng thi trong ngày.',
    'Nhập số lượng sinh viên dự thi thực tế và số sinh viên vi phạm quy chế (nếu có).'
])
add_module(cat, 'Thực hành ngoài', 'external-practice.png', 'Giám sát thực tập/thực hành ngoài.', [
    'Theo dõi các đoàn sinh viên đi thực tế tại bệnh viện, nhà máy.',
    'Cập nhật tình trạng di chuyển, quân số thực tế của đoàn.'
])
add_module(cat, 'Sinh viên vi phạm', 'student-violations.png', 'Chức năng QUAN TRỌNG NHẤT: Ghi nhận vi phạm.', [
    'Cách 1 (Nhập tay): Nhấn "Thêm vi phạm", gõ MSSV (hệ thống tự hiện tên). Chọn Lỗi vi phạm (Đi trễ, v.v.).',
    'Cách 2 (Quét AI): Bấm nút "Quét Camera", giơ thẻ sinh viên hoặc CCCD của sinh viên ra trước camera. Hệ thống tự nhận diện và điền form hoàn toàn tự động!',
    'Có thể đính kèm hình ảnh minh chứng (chụp ảnh sinh viên vi phạm) bằng cách nhấn "Chọn file".',
    'Nhấn "Hoàn tất" để lưu biên bản.'
])
add_module(cat, 'Nhận - Trả tài sản', 'asset-check.png', 'Giảng viên mượn thiết bị.', [
    'Khi Giảng viên đến mượn Micro/Remote: Nhấn "Cho mượn" -> Điền tên GV và mã phòng.',
    'Khi GV trả: Tìm lại giao dịch đó, nhấn nút "Xác nhận đã trả".'
])
add_module(cat, 'Tiếp nhận yêu cầu & Đơn thư', 'requests.png', 'Quản lý khiếu nại.', [
    'Bấm nút "Tạo yêu cầu mới", điền Nội dung phản ánh của sinh viên/GV.',
    'Chuyển Trạng thái từ "Chờ xử lý" sang "Đang xử lý" hoặc "Đã hoàn thành".'
])
add_module(cat, 'Quản lý hồ sơ', 'document-records.png', 'Lưu trữ số hóa.', [
    'Bấm "Upload hồ sơ mới". Chọn Loại văn bản, điền số hiệu, chụp ảnh hoặc tải file PDF của văn bản lên để lưu trữ vĩnh viễn.'
])

# BÁO CÁO THỐNG KÊ
cat = 'Báo cáo thống kê'
add_module(cat, 'Báo cáo cuối ngày', 'reports-daily.png', 'Chốt số liệu cuối ngày.', [
    'Chọn Ngày muốn báo cáo.',
    'Bảng dữ liệu sẽ tổng hợp tự động tất cả các vi phạm, sự cố trong ngày hôm đó.',
    'Bấm nút "Đồng bộ Google Sheets" (nếu đã cài đặt API) để tự động đẩy báo cáo lên Cloud cho Ban giám hiệu xem.'
])
add_module(cat, 'Thống kê việc Không Phù Hợp', 'reports-comprehensive.png', 'Báo cáo KPH tháng.', [
    'Chọn khoảng thời gian (Từ ngày - Đến ngày) và Cơ sở.',
    'Hệ thống lọc ra danh sách giảng viên vi phạm nhiều nhất.',
    'Bấm nút "Xuất báo cáo Word" để tải Biên Bản Không Phù Hợp chuẩn form mẫu của trường.'
])
add_module(cat, 'Các báo cáo khác', 'reports-student-violations.png', 'Báo cáo SV vi phạm, Yêu cầu...', [
    'Thao tác hoàn toàn tương tự: Chọn mốc thời gian -> Bấm Tìm kiếm -> Bấm Xuất Excel để tải báo cáo.'
])

# THIẾT LẬP HỆ THỐNG
cat = 'Thiết lập hệ thống'
add_module(cat, 'Lịch học theo ngày', 'schedule.png', 'Nạp dữ liệu đầu vào.', [
    'Đây là bước bắt buộc mỗi ngày. Admin phải bấm "Import Excel" và tải lên file Lịch học lấy từ hệ thống Đào tạo.',
    'Hệ thống sẽ lấy dữ liệu này để đổ vào các chức năng Lớp học trực tiếp, Lớp học online cho Thanh tra viên đi kiểm tra.'
])
add_module(cat, 'Phân quyền truy cập', 'permissions.png', 'Cấp quyền an toàn.', [
    'Bấm chọn một Vai trò (VD: Thanh tra viên).',
    'Tick chọn vào các ô quyền tương ứng (Xem, Thêm, Sửa, Xóa) cho từng màn hình một.',
    'Bấm Lưu cấu hình.'
])
add_module(cat, 'Nhật ký truy cập', 'access-log.png', 'Theo dõi Logs.', [
    'Chỉ Admin xem được chức năng này. Dùng để tra cứu xem tài khoản nào đã lỡ tay Xóa hay Sửa dữ liệu sai vào thời gian nào.'
])

doc.save(r'd:\ktnb-app\HUONG_DAN_CHI_TIET_THAO_TAC.docx')
print("Detailed user guide with steps generated successfully.")
