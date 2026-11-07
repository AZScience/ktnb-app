import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

def repl(m):
    return '$searchTerm = trim(preg_replace(\'/\s*\([^)]*\)/\', \'\', $subject));'

c = re.sub(
    r'\$searchTerm = explode\(\' \', \$subject\)\[0\];.*?ti.*?n',
    repl,
    c
)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
