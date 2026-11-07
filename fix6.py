import os
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    'return response()->json([\'error\' => "Không tìm thấy Link Google Meet nào từ $sourceName khớp với tiêu chí."], 404);',
    'return response()->json([\'error\' => "Không tìm thấy Link Google Meet nào từ $sourceName khớp với tiêu chí." . (isset($emailError) && $emailError ? " Lỗi Email: $emailError" : "") . (isset($lcmsError) && $lcmsError ? " Lỗi LCMS: $lcmsError" : "")], 404);'
)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
