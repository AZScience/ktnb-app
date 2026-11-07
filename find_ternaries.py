import re, glob
for file in glob.glob('resources/views/**/*.blade.php', recursive=True):
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()
    for match in re.findall(r'x-text="[^"]*\?[^"]*:[^"]*"', content):
        print(f'{file}: {match}'.encode('utf-8'))
