@echo off
REM Goi delta: code + public/build (dung khi hosting DA co vendor)
setlocal
cd /d "d:\ktnb-app"

if exist "public\hot" del /f /q "public\hot"
copy /Y "deploy\hosting\user.ini" "public\user.ini" >nul

set OUT=deploy\ktnb-app-delta-update.zip
if exist "%OUT%" del /f /q "%OUT%"

set STAGE=%TEMP%\ktnb_delta_stage
rmdir /s /q "%STAGE%" 2>nul
mkdir "%STAGE%\app"
mkdir "%STAGE%\config"
mkdir "%STAGE%\public"
mkdir "%STAGE%\resources"
mkdir "%STAGE%\deploy\hosting"
mkdir "%STAGE%\bootstrap"

robocopy "app" "%STAGE%\app" /E /NFL /NDL /NJH /NJS /nc /ns /np
robocopy "config" "%STAGE%\config" /E /NFL /NDL /NJH /NJS /nc /ns /np
robocopy "resources" "%STAGE%\resources" /E /NFL /NDL /NJH /NJS /nc /ns /np
robocopy "public\build" "%STAGE%\public\build" /E /NFL /NDL /NJH /NJS /nc /ns /np
robocopy "deploy\hosting" "%STAGE%\deploy\hosting" /E /XF _make-zip-now.bat /NFL /NDL /NJH /NJS /nc /ns /np
robocopy "bootstrap" "%STAGE%\bootstrap" /E /XD cache /NFL /NDL /NJH /NJS /nc /ns /np
robocopy "routes" "%STAGE%\routes" /E /NFL /NDL /NJH /NJS /nc /ns /np
robocopy "database\migrations" "%STAGE%\database\migrations" /E /NFL /NDL /NJH /NJS /nc /ns /np

copy /Y "public\user.ini" "%STAGE%\public\user.ini" >nul
if exist "public\.htaccess" copy /Y "public\.htaccess" "%STAGE%\public\.htaccess" >nul
copy /Y "composer.json" "%STAGE%\composer.json" >nul
copy /Y "composer.lock" "%STAGE%\composer.lock" >nul
copy /Y "package.json" "%STAGE%\package.json" >nul
if exist "artisan" copy /Y "artisan" "%STAGE%\artisan" >nul

REM Dung Compress-Archive (Windows Explorer mo duoc; tar -a thuong bi Explorer mo thay trong)
powershell -NoProfile -Command "Compress-Archive -Path (Join-Path $env:TEMP 'ktnb_delta_stage\*') -DestinationPath 'deploy\ktnb-app-delta-update.zip' -Force"
echo ZIP_EXIT=%ERRORLEVEL%
rmdir /s /q "%STAGE%"

powershell -NoProfile -Command "if (Test-Path 'deploy\ktnb-app-delta-update.zip') { $z=Get-Item 'deploy\ktnb-app-delta-update.zip'; Write-Host ('DELTA_OK size_mb='+[math]::Round($z.Length/1MB,1)+' entries ok for Explorer') } else { Write-Host 'DELTA_FAIL' }"
endlocal
