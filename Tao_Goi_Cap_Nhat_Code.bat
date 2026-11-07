@echo off
chcp 65001 >nul
title Cong Cu Tao Goi Cap Nhat Hosting
echo =================================================================
echo        CONG CU TAO GOI CAP NHAT MA NGUON (Cho Shared Hosting)
echo =================================================================
echo.

cd /d "D:\ktnb-app"

echo [1/2] Dang tien hanh Build giao dien moi nhat (se mat khoang 1-2 phut)...
call npm run build
if %ERRORLEVEL% neq 0 (
    echo.
    echo [LOI] Qua trinh Build giao dien that bai!
    pause
    exit /b
)

echo.
echo [2/2] Dang dong goi Source Code thanh D:\ktnb-app\deploy\Update_Source_Code.zip...
if not exist "D:\ktnb-app\deploy" mkdir "D:\ktnb-app\deploy"
if exist "D:\ktnb-app\deploy\Update_Source_Code.zip" del /f /q "D:\ktnb-app\deploy\Update_Source_Code.zip"

tar -a -c -f "D:\ktnb-app\deploy\Update_Source_Code.zip" app bootstrap config database public resources routes vendor artisan composer.json composer.lock

if exist "D:\ktnb-app\deploy\Update_Source_Code.zip" (
    echo.
    echo =================================================================
    echo [THANH CONG] Da tao xong file D:\ktnb-app\deploy\Update_Source_Code.zip
    echo.
    echo BAN HAY LAM THEO 2 BUOC SAU:
    echo 1. Gui file ZIP nay cho IT de chep de len ma nguon hien tai.
    echo 2. Chay duong link de update Database:
    echo    https://[ten-mien-cua-ban]/run-secret-migrations-999
    echo =================================================================
) else (
    echo.
    echo [LOI] Khong the tao file ZIP.
)

echo.
pause