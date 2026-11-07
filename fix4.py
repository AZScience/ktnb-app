import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    'public function fetchMeetLink(Request $request)\n    {\n        $module = $request->input(\'module\');',
    'public function fetchMeetLink(Request $request)\n    {\n        $emailError = null;\n        $lcmsError = null;\n        $module = $request->input(\'module\');'
)

c = re.sub(r'\} catch \(\\Exception \$e\) \{\s*\}\s*\} else if \(\$source === \'email\'\)', '} catch (\\Exception $e) { $emailError = $e->getMessage(); }\n            } else if ($source === \'email\')', c)
c = re.sub(r'\} catch \(\\Exception \$e\) \{\s*\\Illuminate\\Support\\Facades\\Log::error\("LCMS Exception: " \. \$e->getMessage\(\)\);\s*\}\s*\} else if \(\$source === \'lcms\'\)', '} catch (\\Exception $e) { $lcmsError = $e->getMessage(); \\Illuminate\\Support\\Facades\\Log::error("LCMS Exception: " . $e->getMessage()); }\n            } else if ($source === \'lcms\')', c)

c = c.replace(
    'return response()->json([\'error\' => "Không tìm thấy Link Google Meet nào từ $sourceName khớp với tiêu chí."], 404);',
    'return response()->json([\'error\' => "Không tìm thấy Link Google Meet nào từ $sourceName khớp với tiêu chí." . ($emailError ? " Lỗi Email: $emailError" : "") . ($lcmsError ? " Lỗi LCMS: $lcmsError" : "")], 404);'
)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)

print('Done')
