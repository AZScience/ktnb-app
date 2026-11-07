import json
path_en = 'resources/js/locales/en.json'
with open(path_en, 'r', encoding='utf-8') as f:
    en_dict = json.load(f)

new_keys = {
    'KIỂM TRA PHÒNG HỌC': 'CLASSROOM INSPECTION',
    'KIỂM TRA TRỰC TUYẾN': 'ONLINE INSPECTION',
    'SINH HOẠT CỐ VẤN': 'HOMEROOM ADVISORY',
    'SINH VIÊN VI PHẠM': 'VIOLATING STUDENTS',
    'THI KẾT THÚC MÔN': 'FINAL EXAMS',
    'THỰC HÀNH NGOÀI': 'EXTERNAL PRACTICAL'
}

en_dict.update(new_keys)

with open(path_en, 'w', encoding='utf-8') as f:
    json.dump(en_dict, f, ensure_ascii=False, indent=2)
