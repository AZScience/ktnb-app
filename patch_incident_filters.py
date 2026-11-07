import re

path = 'app/Services/ReportFilterOptionsService.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

replacement = '''
    public function incidentRecords(array ): array
    {
         = ->buildingNames();
         = ->employeeDisplayNames();

        foreach ( as ) {
            if (!empty(['location'])) {
                [] = ['location'];
            }
            if (!empty(['creator_name'])) {
                [] = ['creator_name'];
            }
        }

        return [
            'locations' => ->sortedOptions(),
            'creator_names' => ->sortedOptions(),
        ];
    }
'''

content = re.sub(
    r'public function incidentRecords\(array \\): array\s*\{\s*\ = \[\];\s*\ = \[\];',
    'public function incidentRecords(array ): array\n    {\n         = ->buildingNames();\n         = ->employeeDisplayNames();',
    content,
    flags=re.DOTALL
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated incidentRecords filter options")
