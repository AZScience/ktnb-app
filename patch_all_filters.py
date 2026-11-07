import re

path = 'app/Http/Controllers/ReportController.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
'''
            'filterOptions' => [
                'buildings' => [],
                'recipients' => [],
            ],
''',
'''
            'filterOptions' => ->filterOptions->withBuildingsAndRecipients([]),
'''
)

content = content.replace(
'''
            'filterOptions' => ['buildings' => [], 'recipients' => []],
''',
'''
            'filterOptions' => ->filterOptions->withBuildingsAndRecipients([]),
'''
)

content = content.replace(
'''
         = ['buildings' => [], 'recipients' => []];
''',
'''
         = ->filterOptions->withBuildingsAndRecipients([]);
'''
)

content = content.replace(
'''
            'filterOptions' => [
                'locations' => [],
                'creator_names' => [],
            ],
''',
'''
            'filterOptions' => ->filterOptions->incidentRecords([]),
'''
)


with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed all hardcoded filterOptions")
