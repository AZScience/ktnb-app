import json
path_en = 'resources/js/locales/en.json'
with open(path_en, 'r', encoding='utf-8') as f:
    en_dict = json.load(f)

new_keys = {
    'Nhân viên': 'Employee',
    'Ngày': 'Date',
    'Phòng': 'Room',
    'Tiết': 'Period',
    'Khoa': 'Department',
    'Lớp': 'Class',
    'Giảng viên': 'Lecturer',
    'Nội dung': 'Content',
    'Việc phát sinh': 'Incident',
    'Thông báo': 'Notification',
    'Chi tiết sự việc': 'Incident Detail',
    'Môn học': 'Subject',
    'SV tham dự': 'Attending Students',
    'SV dự': 'Attending',
    'CBCT 1': 'Proctor 1',
    'CBCT 2': 'Proctor 2',
    'CBCT 3': 'Proctor 3',
    'Môn thi': 'Exam Subject',
    'Địa điểm': 'Location',
    'LT/TH': 'Theory/Practical',
    'Sĩ số': 'Total Students'
}

for k, v in new_keys.items():
    if k not in en_dict:
        en_dict[k] = v

with open(path_en, 'w', encoding='utf-8') as f:
    json.dump(en_dict, f, ensure_ascii=False, indent=2)
