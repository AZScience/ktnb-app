import os
import zipfile

zip_path = 'public/Update_Routes_Web.zip'
files_to_zip = [
    'routes/web.php'
]

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
    for f in files_to_zip:
        zf.write(f, f)

print("Created Update_Routes_Web.zip successfully")
