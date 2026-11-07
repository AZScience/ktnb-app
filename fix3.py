import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    'public function fetchMeetLink(Request )\n    {\n         = ->input(\'module\');',
    'public function fetchMeetLink(Request )\n    {\n         = null;\n         = null;\n         = ->input(\'module\');'
)

c = re.sub(r'\} catch \(\\Exception \\) \{\s*\}\s*\} else if \(\ === \'email\'\)', '} catch (\\Exception ) {  = ->getMessage(); }\n            } else if ( === \'email\')', c)
c = re.sub(r'\} catch \(\\Exception \\) \{\s*\\Illuminate\\Support\\Facades\\Log::error\("LCMS Exception: " \. \->getMessage\(\)\);\s*\}\s*\} else if \(\ === \'lcms\'\)', '} catch (\\Exception ) {  = ->getMessage(); \\Illuminate\\Support\\Facades\\Log::error("LCMS Exception: " . ->getMessage()); }\n            } else if ( === \'lcms\')', c)

c = c.replace(
    'return response()->json([\'error\' => "Không tìm thấy Link Google Meet nào từ  khớp với tiêu chí."], 404);',
    'return response()->json([\'error\' => "Không tìm thấy Link Google Meet nào từ  khớp với tiêu chí." . ( ? " Lỗi Email: " : "") . ( ? " Lỗi LCMS: " : "")], 404);'
)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)

print('Done')
