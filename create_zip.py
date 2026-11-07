import os
import zipfile

zip_path = 'public/Update_ProjectFileManager.zip'
files_to_zip = [
    'app/Services/ProjectFileService.php',
    'app/Http/Controllers/ProjectFileController.php',
    'resources/views/components/project-files-manager.blade.php'
]
dir_to_zip = 'public/build'

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
    for f in files_to_zip:
        zf.write(f, f)
    
    for root, dirs, files in os.walk(dir_to_zip):
        for file in files:
            file_path = os.path.join(root, file)
            zf.write(file_path, file_path)

print("Created zip successfully")
