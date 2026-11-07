import urllib.request
import json

url = 'https://script.google.com/macros/s/AKfycbwpSaJ-ti_ppMYrFQKVmwYQc8zXsnckYzBQwDtZl0JqS4IUSQ6Ls_8ulCn7_tcIyY86/exec'
data = {'room': 'E-LEARNING 3', 'class': 'LopNayKhongBaoGioTonTai9999', 'lecturer': 'Nobody', 'subject': 'Nothing', 'period': '1'}
req = urllib.request.Request(url, data=json.dumps(data).encode('utf-8'), headers={'Content-Type': 'application/json'})

try:
    response = urllib.request.urlopen(req)
    with open('gas_result_fail.txt', 'w', encoding='utf-8') as f:
        f.write(response.read().decode('utf-8'))
except Exception as e:
    with open('gas_result_fail.txt', 'w', encoding='utf-8') as f:
        f.write(str(e))
