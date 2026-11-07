import json

path = 'resources/js/locales/en.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

new_keys = {
    "Cổng Check-in Giảng viên ↗": "Lecturer Check-in Portal",
    "Bảng thảo luận": "Discussion Board",
    "Cổng Check-in Giảng viên": "Lecturer Check-in Portal"
}

for k, v in new_keys.items():
    data[k] = v

with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)
