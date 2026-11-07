import os
import docx
from docx.shared import Pt, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH

doc = docx.Document()

def add_title(text):
    h = doc.add_heading(text, 0)
    h.alignment = WD_ALIGN_PARAGRAPH.CENTER

def add_h1(text):
    doc.add_heading(text, 1)

def insert_img(filename, width=Inches(6)):
    path = os.path.join(r"D:\ktnb-app\screenshots", filename)
    if os.path.exists(path):
        doc.add_picture(path, width=width)
        doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER

add_title('CẨM NANG HƯỚNG DẪN THAO TÁC TỪNG BƯỚC')
doc.add_paragraph('Tài liệu hướng dẫn thao tác chi tiết (cầm tay chỉ việc) cho từng chức năng cụ thể của ứng dụng KTNB. Đọc và làm theo từng bước là có thể sử dụng ngay lập tức mà không cần đào tạo.').alignment = WD_ALIGN_PARAGRAPH.CENTER
doc.add_page_break()

modules = [
    (
        '1. GHI NHẬN SINH VIÊN VI PHẠM (QUAN TRỌNG NHẤT)',
        'student-violations.png',
        [
            '📌 MỤC ĐÍCH: Dùng để ghi nhận lỗi vi phạm của sinh viên (VD: Đi trễ, Đeo tai nghe, Trang phục không đúng).',
            '',
            '👉 BƯỚC 1: Trên thanh menu bên trái, bấm vào phần "Công cụ kiểm tra", sau đó chọn mục "Sinh viên vi phạm".',
            '👉 BƯỚC 2: Màn hình danh sách vi phạm hiện ra. Hãy đưa chuột lên góc phải trên cùng màn hình và bấm vào nút "Thêm mới" (nút màu xanh dương).',
            '👉 BƯỚC 3: Màn hình điền thông tin hiện ra. Sẽ có 2 cách để nhập sinh viên:',
            '   + Cách 1 (Tự động hóa): Bấm nút "Quét Camera". Giơ thẻ sinh viên hoặc CCCD của sinh viên lên trước webcam laptop/điện thoại. Hệ thống tự nhận diện khuôn mặt và điền thẳng thông tin vào form.',
            '   + Cách 2 (Nhập tay thủ công): Click vào ô "Mã số sinh viên", gõ mã số vào. Hệ thống sẽ tự động tìm kiếm Tên và Lớp điền sẵn cho bạn, không cần gõ lại tên.',
            '👉 BƯỚC 4: Tại ô "Hình thức vi phạm", bấm vào biểu tượng mũi tên trỏ xuống và click chọn loại lỗi (Ví dụ: Đi trễ).',
            '👉 BƯỚC 5 (Tùy chọn): Tại khu vực "Hình ảnh minh chứng", bấm nút "Chọn file" để tải lên bức ảnh chụp sinh viên đang vi phạm (từ điện thoại hoặc máy tính).',
            '👉 BƯỚC 6: Bấm nút "Lưu lại" (hoặc "Hoàn tất") ở dưới cùng. Thông báo màu xanh báo "Lưu thành công" hiện ra ở góc phải là bạn đã hoàn tất.'
        ]
    ),
    (
        '2. LỊCH HỌC THEO NGÀY (NẠP DỮ LIỆU ĐẦU VÀO)',
        'schedule.png',
        [
            '📌 MỤC ĐÍCH: Đưa dữ liệu thời khóa biểu giảng dạy từ phòng Đào Tạo vào phần mềm KTNB để bắt đầu đi tuần tra.',
            '',
            '👉 BƯỚC 1: Trên menu bên trái, cuộn xuống dưới cùng bấm vào "Thiết lập hệ thống" -> "Lịch học theo ngày".',
            '👉 BƯỚC 2: Nhấn vào nút "Tải lên" (Import Excel) có biểu tượng mũi tên mây ở thanh công cụ.',
            '👉 BƯỚC 3: Một cửa sổ nhỏ bật lên. Bấm chữ "Chọn tệp", duyệt tìm đến file Excel chứa thời khóa biểu trên máy tính của bạn và bấm Open.',
            '👉 BƯỚC 4: Nhấn nút "Tiến hành Import". Bạn chờ cho thanh tiến trình chạy đủ 100% là dữ liệu sẽ tự động đổ vào bảng bên dưới.',
            '👉 BƯỚC 5: (Để kiểm tra lại) Bấm biểu tượng chiếc phễu (Bộ lọc) trên bảng, chọn một Cơ sở (Ví dụ Cơ sở Quận 12) để xem lịch của cơ sở đó đã vào đủ chưa.'
        ]
    ),
    (
        '3. KIỂM TRA LỚP HỌC TRỰC TIẾP (DÀNH CHO ĐI TUẦN TRA)',
        'in-person.png',
        [
            '📌 MỤC ĐÍCH: Dành cho Thanh tra viên cầm điện thoại/tablet đi quanh trường kiểm tra xem các lớp học có diễn ra bình thường không.',
            '',
            '👉 BƯỚC 1: Trên menu trái, bấm "Công cụ kiểm tra" -> "Lớp học trực tiếp".',
            '👉 BƯỚC 2: Màn hình hiện ra danh sách các phòng học có lịch hôm nay. Bạn dùng Bộ lọc (nút cái phễu) để lọc chọn Dãy nhà bạn đang đứng (VD: Dãy nhà A).',
            '👉 BƯỚC 3: Đi bộ ngang qua phòng A.101, bạn nhìn vào màn hình điện thoại, tìm đúng dòng của phòng A.101.',
            '👉 BƯỚC 4: Nhấn vào nút "Kiểm tra" (hoặc biểu tượng cây bút) ở dòng A.101 đó.',
            '👉 BƯỚC 5: Cửa sổ đánh giá hiện lên. Bạn tick vào các tùy chọn: Lớp học có ồn ào không? Giảng viên có mặt đúng giờ không? Sĩ số khoảng bao nhiêu?',
            '👉 BƯỚC 6: Bấm nút "Lưu đánh giá". Hệ thống ghi nhận kết quả và đổi màu dòng đó sang màu Xanh (thể hiện đã kiểm tra xong).'
        ]
    ),
    (
        '4. XUẤT BIÊN BẢN KHÔNG PHÙ HỢP (CHỐT SỔ CUỐI THÁNG)',
        'reports-comprehensive.png',
        [
            '📌 MỤC ĐÍCH: Chốt sổ số liệu cuối tháng, xuất file Word báo cáo tự động để ký tên nộp Ban Giám Hiệu.',
            '',
            '👉 BƯỚC 1: Truy cập menu "Báo cáo thống kê" -> "Thống kê việc KPH".',
            '👉 BƯỚC 2: Sử dụng bộ lọc ở đầu trang. Tại ô "Từ ngày" và "Đến ngày", bạn chọn khoảng thời gian (Ví dụ: Từ 01/10/2023 đến 31/10/2023). Tại ô "Cơ sở", chọn cơ sở cần báo cáo.',
            '👉 BƯỚC 3: Bấm nút "Tìm kiếm". Hệ thống tự động tính toán và lọc ra các trường hợp vi phạm nhiều nhất trong tháng.',
            '👉 BƯỚC 4: Tìm nút "Xuất báo cáo Word" (nút màu xanh dương) và bấm vào đó.',
            '👉 BƯỚC 5: Một file .docx được thiết kế chuẩn theo đúng biểu mẫu ISO của trường sẽ tự động tải về máy tính của bạn. Bạn chỉ việc mở lên, xem lại, in ra ký tên và đem nộp.'
        ]
    ),
    (
        '5. TIẾP NHẬN YÊU CẦU & ĐƠN THƯ KHIẾU NẠI',
        'requests.png',
        [
            '📌 MỤC ĐÍCH: Ghi nhận phản ánh của sinh viên hoặc giảng viên (mất đồ, máy lạnh hỏng, thái độ phục vụ...).',
            '',
            '👉 BƯỚC 1: Truy cập "Công cụ kiểm tra" -> "Tiếp nhận yêu cầu" (hoặc "Tiếp nhận đơn thư").',
            '👉 BƯỚC 2: Bấm nút "Thêm mới".',
            '👉 BƯỚC 3: Cửa sổ hiện ra, bạn nhập thông tin người yêu cầu (Tên, SĐT, MSSV nếu có). Ở ô "Nội dung", bạn đánh chữ miêu tả chi tiết sự việc (Ví dụ: Mất xe máy ở bãi, Máy chiếu phòng A.101 hỏng).',
            '👉 BƯỚC 4: Nhấn "Lưu lại". Yêu cầu này sẽ hiển thị trong bảng với trạng thái "Chờ xử lý".',
            '👉 BƯỚC 5: Khi giải quyết xong sự việc, bạn quay lại bảng này, tìm đến yêu cầu đó và bấm nút "Sửa" (hình cây bút ở cột cuối).',
            '👉 BƯỚC 6: Bấm vào ô Trạng thái chuyển thành "Đã hoàn thành", đồng thời nhập kết quả vào ô "Cách giải quyết". Bấm "Lưu" để chốt hồ sơ.'
        ]
    ),
    (
        '6. QUẢN LÝ NHẬN VÀ TRẢ TÀI SẢN (MICRO, CHÌA KHÓA)',
        'asset-check.png',
        [
            '📌 MỤC ĐÍCH: Quản lý thiết bị Giảng viên mượn lên lớp để tránh thất thoát.',
            '',
            '👉 BƯỚC 1 (Khi Giảng viên đến mượn đồ): Bạn mở menu "Công cụ kiểm tra" -> "Nhận - Trả tài sản", bấm nút "Thêm mới".',
            '👉 BƯỚC 2: Chọn Tên giảng viên từ danh sách đổ xuống. Chọn tên Thiết bị (Micro số 1, Remote). Chọn Giờ mượn là thời gian hiện tại. Bấm "Lưu".',
            '👉 BƯỚC 3: Giao thiết bị cho GV.',
            '👉 BƯỚC 4 (Khi Giảng viên đem trả đồ): Gõ tên GV hoặc mã thiết bị vào ô "Tìm kiếm" ở phía trên bảng dữ liệu để tìm ra dòng giao dịch lúc sáng.',
            '👉 BƯỚC 5: Tại dòng đó, bấm nút "Chỉnh sửa" (cây bút màu vàng).',
            '👉 BƯỚC 6: Đổi trạng thái từ "Đang mượn" thành "Đã trả", nhập Giờ trả. Bấm "Lưu lại". Việc mượn trả hoàn tất!'
        ]
    ),
    (
        '7. QUẢN LÝ DANH MỤC (NHẬP GIẢNG VIÊN, SINH VIÊN)',
        'lecturers.png',
        [
            '📌 MỤC ĐÍCH: Đưa danh sách Giảng viên, Sinh viên, Phòng học vào phần mềm để dùng cho các chức năng kiểm tra.',
            '',
            '👉 BƯỚC 1: Truy cập menu "Bộ danh mục" -> "Giảng viên" (hoặc "Sinh viên").',
            '👉 BƯỚC 2: (Nhập lẻ tẻ) Nếu chỉ có 1-2 người mới, bạn bấm nút "Thêm mới", điền thông tin và Lưu.',
            '👉 BƯỚC 3: (Nhập hàng loạt) Nếu có 1000 người, bạn KHÔNG nhập tay. Bấm nút "Tải lên" (Import).',
            '👉 BƯỚC 4: Trong cửa sổ Import, có dòng chữ "Tải file mẫu". Bấm vào để tải file Excel chuẩn về máy.',
            '👉 BƯỚC 5: Mở file Excel mẫu lên, dán/copy danh sách 1000 người của bạn vào đúng các cột tương ứng.',
            '👉 BƯỚC 6: Up file Excel đó lên lại cửa sổ Import của phần mềm và bấm "Xác nhận". Tích tắc sau, toàn bộ dữ liệu sẽ có mặt trong hệ thống.'
        ]
    )
]

for title, img, steps in modules:
    add_h1(title)
    for s in steps:
        doc.add_paragraph(s)
    insert_img(img)
    doc.add_page_break()

doc.save(r'd:\ktnb-app\HUONG_DAN_CAM_TAY_CHI_VIEC.docx')
print("Step-by-step manual built successfully.")
