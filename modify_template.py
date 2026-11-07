import zipfile
import re
import os

template_path = r"d:\ktnb-app\public\templates\baocaothang-template.docx"
backup_path = r"d:\ktnb-app\public\templates\baocaothang-template.docx.bak"

# We assume backup_path is the fully pristine original file, since we just restored it.
with zipfile.ZipFile(backup_path, 'r') as zin:
    with zipfile.ZipFile(template_path, 'w') as zout:
        zout.comment = zin.comment
        for item in zin.infolist():
            if item.filename == 'word/document.xml':
                xml = zin.read(item.filename).decode('utf-8')
                
                # Replace 1: "3 đon vị" -> "..."
                xml = xml.replace(
                    'Trong kỳ, có 3 đon vị ngoài trường vào tiếp thị, quảng cáo cho SV:',
                    'Trong kỳ, có ..... đơn vị ngoài trường vào tiếp thị, quảng cáo cho SV:'
                )
                
                # Remove the first sample company line
                xml = xml.replace(
                    '<w:rPr><w:color w:val="0070C0"/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>- Cty TNHH truyền thông tiếp thị P2P - Quảng cáo thức uống Sting Gold Pro</w:t>',
                    '<w:rPr><w:color w:val="0070C0"/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t></w:t>'
                )
                
                # Replace 2: Remove "Không có đề xuất hay kiến nghị gì thêm."
                xml = xml.replace(
                    '<w:rPr><w:color w:val="0070C0"/><w:sz w:val="26"/><w:szCs w:val="26"/><w:lang w:val="en-US"/></w:rPr><w:t>Không có đề xuất hay kiến nghị gì thêm.</w:t>',
                    '<w:rPr><w:color w:val="0070C0"/><w:sz w:val="26"/><w:szCs w:val="26"/><w:lang w:val="en-US"/></w:rPr><w:t></w:t>'
                )
                
                zout.writestr(item, xml.encode('utf-8'))
            else:
                zout.writestr(item, zin.read(item.filename))

print("Template restored to previous state successfully!")
