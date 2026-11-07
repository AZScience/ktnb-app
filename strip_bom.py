import os
with open('app/Http/Controllers/MonitoringScheduleController.php', 'rb') as f:
    c = f.read()

if c.startswith(b'\xef\xbb\xbf'):
    c = c[3:]

with open('app/Http/Controllers/MonitoringScheduleController.php', 'wb') as f:
    f.write(c)

print('BOM removed')
