import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

# find all return response()->json(['message' => '...'])
c = re.sub(r'\'message\' => \'.*?l.*?u ghi nh.*?\'', '\'message\' => \'Đã lưu ghi nhận.\'', c)
c = re.sub(r'\'message\' => \'.*?y ghi nh.*?\'', '\'message\' => \'Đã hủy ghi nhận.\'', c)
c = re.sub(r'\'message\' => \'.*?m l.*?ch.*?\'', '\'message\' => \'Đã thêm lịch.\'', c)
c = re.sub(r'\'message\' => \'.*?a l.*?ch.*?\'', '\'message\' => \'Đã xóa lịch.\'', c)
c = re.sub(r'\'message\' => \'.*?u b.*?l.*?c.*?\'', '\'message\' => \'Đã lưu bộ lọc.\'', c)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
