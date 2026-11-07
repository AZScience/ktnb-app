import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

reps = {
    'Vui lÃƒÂ²ng nhÃ¡ÂºÂ\xadp Chi tiÃ¡ÂºÂ¿t viÃ¡Â»â€¡c phÃƒÂ¡t sinh.': 'Vui lòng nhập Chi tiết việc phát sinh.',
    'Ghi nhÃ¡ÂºÂ\xadn giÃƒÂ¡m sÃƒÂ¡t': 'Ghi nhận giám sát',
    'Ã‚Â·': '·',
    'LÃ¡Â»â€ºp': 'Lớp',
    'TiÃ¡ÂºÂ¿t': 'Tiết',
    'Ã„Â\x90ÃƒÂ£ lÃ\xa0Â°u ghi nhÃ¡ÂºÂ\xadn.': 'Đã lưu ghi nhận.',
    'Ã„Â\x90ÃƒÂ£ cÃ¡ÂºÂ\xadp nhÃ¡ÂºÂ\xadt giÃƒÂ¡m sÃƒÂ¡t.': 'Đã cập nhật giám sát.',
    'HÃ¡Â»Â§y ghi nhÃ¡ÂºÂ\xadn giÃƒÂ¡m sÃƒÂ¡t': 'Hủy ghi nhận giám sát',
    'Ã„Â\x90ÃƒÂ£ hÃ¡Â»Â§y ghi nhÃ¡ÂºÂ\xadn.': 'Đã hủy ghi nhận.',
    'Ghi nhÃ¡ÂºÂ\xadn': 'Ghi nhận',
    'PhÃƒÂ²ng hÃ¡Â»Â\x8dc': 'Phòng học',
    'ThÃƒÂªm lÃ¡Â»â€¹ch giÃƒÂ¡m sÃƒÂ¡t': 'Thêm lịch giám sát',
    'Ã„Â\x90ÃƒÂ£ thÃƒÂªm lÃ¡Â»â€¹ch.': 'Đã thêm lịch.',
    'XÃƒÂ³a lÃ¡Â»â€¹ch giÃƒÂ¡m sÃƒÂ¡t': 'Xóa lịch giám sát',
    'Ã„Â\x90ÃƒÂ£ xÃƒÂ³a lÃ¡Â»â€¹ch.': 'Đã xóa lịch.',
    'Ã„Â\x90ÃƒÂ£ import {$count} tiÃ¡ÂºÂ¿t hÃ¡Â»Â\x8dc.': 'Đã import {$count} tiết học.',
    'KhÃƒÂ´ng cÃƒÂ³ lÃ¡Â»â€¹ch ngÃƒÂ\xa0y': 'Không có lịch ngày',
    'Ã„Â\x90ang hiÃ¡Â»Æ’n thÃ¡Â»â€¹ ngÃƒÂ\xa0y': 'Đang hiển thị ngày',
    'bÃ¡ÂºÂ£n ghi': 'bản ghi',
    'Ã„Â\x90ÃƒÂ£ lÃ\xa0Â°u bÃ¡Â»â„¢ lÃ¡Â»Â\x8dc': 'Đã lưu bộ lọc',
    'Chưa cấu hình t\xa0i khoản Email trong hệ thống.': 'Chưa cấu hình tài khoản Email trong hệ thống.',
    '011007558111 - Kinh tÃ¡ÂºÂ¿ ChÃƒÂ\xadnh trÃ¡Â»â€¹': '011007558111 - Kinh tế Chính trị',
    'Chưa cấu hình t\xa0i khoản LCMS trong hệ thống.': 'Chưa cấu hình tài khoản LCMS trong hệ thống.',
    'tÃ\xa0i khoÃ\xa3n': 'tài khoản'
}

for k, v in reps.items():
    c = c.replace(k, v)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)

print('Done')
