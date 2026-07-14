@echo off
REM =============================================================================
REM KTNB App — Lệnh sau upload (chạy trên server Windows IIS hoặc máy dev test)
REM =============================================================================
cd /d "%~dp0..\.."

if not exist .env (
  echo Thieu file .env — copy deploy\hosting\env.production thanh .env
  exit /b 1
)

composer install --no-dev --optimize-autoloader --no-interaction
php artisan package:discover --ansi

REM Xóa public/hot nếu có (tránh @vite trỏ Vite localhost)
if exist "public\hot" del /f /q "public\hot"

php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo Deploy xong.
