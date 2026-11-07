import json
path_en = 'resources/js/locales/en.json'
with open(path_en, 'r', encoding='utf-8') as f:
    en_dict = json.load(f)

new_keys = {
    'BÁO CÁO GHI NHẬN SINH VIÊN VI PHẠM': 'VIOLATING STUDENTS REPORT',
    'KẾT THÚC MÔN': 'FINAL EXAMS',
    'KIỂM TRA LỚP HỌC TRỰC TUYẾN': 'ONLINE CLASSROOM INSPECTION',
    'Dữ liệu từ bảng': 'Data from the',
    'sẽ được đưa vào Google Sheet đã kết nối.': 'table will be pushed to the connected Google Sheet.',
    'Tổng cộng': 'Total',
    'bản ghi.': 'records.',
    'dòng tổng hợp.': 'summarized rows.',
    'Đã chọn': 'Selected',
    'dòng.': 'rows.'
}

for k, v in new_keys.items():
    if k not in en_dict:
        en_dict[k] = v

with open(path_en, 'w', encoding='utf-8') as f:
    json.dump(en_dict, f, ensure_ascii=False, indent=2)
