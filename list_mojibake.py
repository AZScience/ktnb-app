import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

for m in re.finditer(r'[\'"](.*?)[\'"]', c):
    s = m.group(1)
    if 'Ã' in s:
        print(repr(s))
