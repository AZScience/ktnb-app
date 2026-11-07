import os

file_path = 'app/Services/ProjectFileService.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    'public function uploadFile(string , UploadedFile ): string',
    'public function uploadFile(string , UploadedFile , bool  = false): string'
)

target = '''         = .DIRECTORY_SEPARATOR.;
        if (file_exists()) {
            abort(422, 'File đã tồn tại. Hãy đổi tên hoặc xóa file cũ.');
        }

        ->move(, );'''
replacement = '''         = .DIRECTORY_SEPARATOR.;
        if (file_exists() && !) {
            abort(422, 'File đã tồn tại. Hãy đổi tên hoặc xóa file cũ.');
        }

        ->move(, );'''
content = content.replace(target, replacement)

content = content.replace(
    'public function uploadBatch(string , array ): array',
    'public function uploadBatch(string , array , bool  = false): array'
)

target2 = '''                 = .DIRECTORY_SEPARATOR.;
                if (file_exists()) {
                    [] = ;
                    continue;
                }

                ->move(, );'''
replacement2 = '''                 = .DIRECTORY_SEPARATOR.;
                if (file_exists() && !) {
                    [] = ;
                    continue;
                }

                ->move(, );'''
content = content.replace(target2, replacement2)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched ProjectFileService")

controller_path = 'app/Http/Controllers/ProjectFileController.php'
with open(controller_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = '''            'paths.*' => 'nullable|string|max:500',
        ]);

         = (string) (['path'] ?? '');'''
replacement = '''            'paths.*' => 'nullable|string|max:500',
            'overwrite' => 'nullable|boolean',
        ]);

         = (string) (['path'] ?? '');'''
content = content.replace(target, replacement)

target2 = '''         = ->files->uploadBatch(, );'''
replacement2 = '''         = ->files->uploadBatch(, , !empty(['overwrite']));'''
content = content.replace(target2, replacement2)

with open(controller_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched ProjectFileController")
