import os
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    '$date = date("d-M-Y", strtotime("-14 days"));',
    '$date = date("d-M-Y", strtotime("-7 days"));'
)
c = c.replace(
    'if (preg_match(\'/name="logintoken" value="([^"]+)"/\', $html1, $mToken)) {\n                        $loginToken = $mToken[1];',
    'if (preg_match(\'/name="logintoken" value="([^"]+)"/\', $html1, $mToken)) {\n                        $loginToken = $mToken[1];\n                    } else { throw new \\Exception("Khong tim thay khung dang nhap (logintoken) tren LCMS. Co the Moodle dang dung giao dien SSO (Microsoft/Google)."); }\n                    if (isset($loginToken)) {'
)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
