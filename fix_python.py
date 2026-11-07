import re

with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

replacements = {
    r"Ch.*?a c.*?u h.*?nh t.*?i kho.*?n Email trong h.*? th.*?ng.": "Chưa cấu hình tài khoản Email trong hệ thống.",
    r"Email ho.*?c LCMS": "Email hoặc LCMS",
    r"Kh.*?ng t.*?m th.*?y Link Google Meet n.*?o t.*? \ kh.*?p v.*?i ti.*?u ch.*?.": "Không tìm thấy Link Google Meet nào từ \ khớp với tiêu chí.",
    r"Ãƒâ€žÃ‚Â ÃƒÆ’Ã‚Â£ lÃƒâ€ Ã‚Â°u ghi nhÃƒÂ¡Ã‚ÂºÃ‚Â­n.": "Đã lưu ghi nhận.",
    r"Ãƒâ€žÃ‚Â ÃƒÆ’Ã‚Â£ hÃƒÂ¡Ã‚Â»Ã‚Â§y ghi nhÃƒÂ¡Ã‚ÂºÃ‚Â­n.": "Đã hủy ghi nhận.",
    r"Ãƒâ€žÃ‚Â ÃƒÆ’Ã‚Â£ thÃƒÆ’Ã‚Âªm lÃƒÂ¡Ã‚Â»Ã¢â‚¬Â¹ch.": "Đã thêm lịch.",
    r"Ãƒâ€žÃ‚Â ÃƒÆ’Ã‚Â£ xÃƒÆ’Ã‚Â³a lÃƒÂ¡Ã‚Â»Ã¢â‚¬Â¹ch.": "Đã xóa lịch.",
    r"Ãƒâ€žÃ‚Â ÃƒÆ’Ã‚Â£ lÃƒâ€ Ã‚Â°u bÃƒÂ¡Ã‚Â»Ã¢â€žÂ¢ lÃƒÂ¡Ã‚Â»Ã‚Â c": "Đã lưu bộ lọc"
}

for k, v in replacements.items():
    c = re.sub(k, v, c)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)

print('Done')
