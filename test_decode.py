import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

m = re.search(r'message\' => \'(.*?) ghi nh.*?\'', c)
if m:
    s = m.group(1)
    print("Raw:", repr(s))
    try:
        print("Decode 1:", s.encode('windows-1252').decode('utf-8'))
    except Exception as e:
        print("Err 1:", e)
    try:
        print("Decode 2:", s.encode('windows-1252').decode('utf-8').encode('windows-1252').decode('utf-8'))
    except Exception as e:
        print("Err 2:", e)
