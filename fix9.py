import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    '$searchTerm = explode(\' \', $subject)[0]; // HoAAA,AA,Ac lAAA,AA,Ay tAAA,AA,A A?zA,EoAAA,AA,A u tiA\'A,An',
    '$searchTerm = trim(preg_replace(\'/\s*\([^)]*\)/\', \'\', $subject)); // Lay toan bo ten mon hoc, bo qua phan trong ngoac'
)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
