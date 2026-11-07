import xml.etree.ElementTree as ET

tree = ET.parse('public/templates/word/document.xml')
root = tree.getroot()

with open('scratch.txt', 'w', encoding='utf-8') as f:
    for node in root.iter('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}t'):
        if node.text:
            f.write(node.text + '\n')
