import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = re.sub(r'\'success\', \'.*?c.*?p nh.*?t.*?\'', '\'success\', \'Đã cập nhật giám sát.\'', c)
c = re.sub(r'\'success\', \'.*?y ghi nh.*?\'', '\'success\', \'Đã hủy ghi nhận.\'', c)
c = re.sub(r'\'success\', \'.*?m l.*?ch.*?\'', '\'success\', \'Đã thêm lịch.\'', c)
c = re.sub(r'\'success\', \'.*?a l.*?ch.*?\'', '\'success\', \'Đã xóa lịch.\'', c)
c = re.sub(r'\'success\', ".*?import.*?"', '\'success\', "Đã import {$count} tiết học."', c)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
