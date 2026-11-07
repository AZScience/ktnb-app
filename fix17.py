import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = re.sub(r'\'error\' => \'Ch.*?a c.*?u h.*?nh t.*?i kho.*?n Email trong h.*? th.*?ng.\'', '\'error\' => \'Chưa cấu hình tài khoản Email trong hệ thống.\'', c)
c = re.sub(r'\'error\' => \'Ch.*?a c.*?u h.*?nh t.*?i kho.*?n LCMS trong h.*? th.*?ng.\'', '\'error\' => \'Chưa cấu hình tài khoản LCMS trong hệ thống.\'', c)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
