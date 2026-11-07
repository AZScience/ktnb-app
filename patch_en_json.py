import json

path = 'resources/js/locales/en.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

new_keys = {
    "Chức vụ": "Positions",
    "Dãy nhà": "Buildings",
    "Đơn vị": "Departments",
    "Phòng học": "Classrooms",
    "Giảng viên": "Lecturers",
    "Nhân viên": "Employees",
    "Sinh viên": "Students",
    "Quà tặng": "Gifts",
    "Vai trò": "Roles",
    "Việc ghi nhận": "Recognitions",
    "Việc phát sinh": "Incident Categories",
    "Loại văn bản": "Document Types",
    "Bộ danh mục": "Catalogs",
    "Cố vấn học tập": "Academic Advisor",
    "Lớp học online": "Online Classes",
    "Lớp học trực tiếp": "In-person Classes",
    "Thi kết thúc môn": "Final Exams",
    "Thực hành ngoài": "External Practice",
    "Sinh viên vi phạm": "Student Violations",
    "Biên bản sự việc": "Incident Records",
    "Nhận - Trả tài sản": "Asset Receiving / Returning",
    "Tiếp nhận yêu cầu": "Requests",
    "Tiếp nhận đơn thư": "Petitions",
    "Quản lý hồ sơ": "Document Management",
    "Công cụ kiểm tra": "Monitoring Tools",
    "Báo cáo cuối ngày": "Daily Reports",
    "Minh chứng ca trực": "Shift Evidence",
    "Thống kê việc KPH": "Comprehensive Statistics",
    "Người tốt việc tốt": "Good Deeds",
    "Thống kê biên bản": "Incident Record Statistics",
    "Báo cáo thống kê": "Reports",
    "Lịch học theo ngày": "Daily Schedules",
    "Tham số hệ thống": "System Parameters",
    "Phân quyền truy cập": "Access Permissions",
    "Cài đặt hệ thống": "System Settings",
    "Mẫu văn bản / biểu mẫu": "Document Templates",
    "Thiết lập hệ thống": "System Settings",
    "Nhật ký truy cập": "Access Logs",
    "Sao lưu & Phục hồi": "Backup & Restore",
    "Quản lý file mã nguồn": "Source Code Manager",
    "Lịch sinh hoạt chủ nhiệm": "Homeroom Schedule",
    "Lịch trực văn phòng": "Office Duty Schedule",
    "Lịch sinh hoạt đầu khoá": "Orientation Schedule",
    "Lịch thi (TTKT)": "Exam Schedule",
    "Lịch thực hành (Ngoài)": "External Practice Schedule",
    "Lịch trực lớp (CSVC)": "Classroom Duty Schedule",
    "Lịch giảng dạy online": "Online Teaching Schedule",
    "bản ghi.": "records.",
    "Đã chọn": "Selected",
    "dòng.": "rows.",
    "Tài liệu API": "API Documentation",
    "Thông báo nội bộ": "Internal Notices",
    "Bảng thảo luận ↗": "Discussion Board",
    "Internal Mailbox": "Internal Mailbox",
    "Cổng Check-in Giảng viên": "Lecturer Check-in Portal",
    "Chi tiết việc phát sinh": "Incident Details",
    "Chính sách bảo mật": "Privacy Policy",
    "Tổng cộng": "Total",
    "Số dòng": "Rows per page"
}

for k, v in new_keys.items():
    data[k] = v

with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print("Updated en.json with sidebar keys")
