@echo off
setlocal

rem Respaldo de la base de datos del Hotel Kuzco (XAMPP / MariaDB-MySQL)

set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NAME=hotel_kuzco
set DB_USER=root
set DB_PASS=
set DB_HOST=127.0.0.1

set BACKUP_DIR=%~dp0..\backups
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmmss"') do set STAMP=%%i
set ARCHIVO=%BACKUP_DIR%\hotel_%STAMP%.sql

echo Generando respaldo en %ARCHIVO% ...
"%MYSQL_BIN%\mysqldump.exe" -h %DB_HOST% -u %DB_USER% %DB_PASS% --routines --triggers --single-transaction %DB_NAME% > "%ARCHIVO%"

if %errorlevel% neq 0 (
    echo ERROR: no se pudo generar el respaldo. Verifique que MySQL de XAMPP este activo.
    exit /b 1
)

echo Respaldo generado correctamente: %ARCHIVO%
endlocal
