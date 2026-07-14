@echo off
REM =============================================================================
REM KTNB App — Đóng gói bản deploy production (Windows)
REM Chạy: deploy\hosting\package-for-deploy.bat
REM Kết quả: deploy\ktnb-app-production.zip
REM =============================================================================
setlocal enabledelayedexpansion
cd /d "%~dp0..\.."

echo [1/5] Composer production...
call composer install --no-dev --no-interaction
if errorlevel 1 exit /b 1
php -d xdebug.mode=off "%COMPOSER_HOME%\..\ComposerSetup\bin\composer.phar" dump-autoload --no-dev --no-scripts 2>nul
if errorlevel 1 php -d xdebug.mode=off "C:\ProgramData\ComposerSetup\bin\composer.phar" dump-autoload --no-dev --no-scripts
php artisan package:discover --ansi
if errorlevel 1 exit /b 1

echo [2/5] NPM build...
call npm ci --ignore-scripts 2>nul
if errorlevel 1 call npm install --ignore-scripts
call npm run build
if errorlevel 1 exit /b 1

if exist "public\hot" del /f /q "public\hot"
copy /Y "deploy\hosting\user.ini" "public\user.ini" >nul

echo [3/5] Stage NGOAI project (tranh long deploy\_stage)...
set STAGE=%TEMP%\ktnb_prod_stage
if exist "%STAGE%" rmdir /s /q "%STAGE%"
mkdir "%STAGE%"

REM Khong copy: node_modules, .git, storage/app (upload/backup local), tests, deploy (se copy hosting sau)
robocopy . "%STAGE%" /E /XD node_modules .git deploy storage\app storage\logs storage\framework\cache\data storage\framework\sessions storage\framework\views storage\pail .cursor .codex .idea .vscode tests /XF .env .env.backup .env.production *.log Thumbs.db public\hot *.rar ktnb-app.rar /NFL /NDL /NJH /NJS /nc /ns /np
if %ERRORLEVEL% GEQ 8 exit /b 1

mkdir "%STAGE%\deploy\hosting" 2>nul
robocopy "deploy\hosting" "%STAGE%\deploy\hosting" /E /NFL /NDL /NJH /NJS /nc /ns /np
if %ERRORLEVEL% GEQ 8 exit /b 1

if exist "%STAGE%\public\hot" del /f /q "%STAGE%\public\hot"
copy /Y "deploy\hosting\user.ini" "%STAGE%\public\user.ini" >nul

for %%D in (storage\app\public storage\app\private\backups storage\framework\cache storage\framework\sessions storage\framework\views storage\logs bootstrap\cache) do (
  if not exist "%STAGE%\%%D" mkdir "%STAGE%\%%D"
)

echo [4/5] Kiem tra...
if not exist "%STAGE%\public\build\manifest.json" (
  echo LOI: thieu public\build\manifest.json
  exit /b 1
)
if exist "%STAGE%\public\hot" (
  echo LOI: van con public\hot
  exit /b 1
)

echo [5/5] Nen zip (tar — nhanh hon Compress-Archive)...
if exist "deploy\ktnb-app-production.zip" del /f /q "deploy\ktnb-app-production.zip"
tar -a -cf "deploy\ktnb-app-production.zip" -C "%STAGE%" .
if errorlevel 1 (
  echo LOI nen zip
  exit /b 1
)
rmdir /s /q "%STAGE%"

echo.
echo Xong: deploy\ktnb-app-production.zip
echo Hosting: giai nen de CODE; GIU .env + storage/app; chay post-deploy.
echo Local: composer install

endlocal
