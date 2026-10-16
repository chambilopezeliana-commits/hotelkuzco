@echo off
setlocal

rem Restauracion de la base de datos del Hotel Kuzco (XAMPP / MariaDB-MySQL)

set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NAME=hotel_kuzco
set DB_USER=root
set DB_PASS=
set DB_HOST=127.0.0.1

if "%~1"=="" (
    echo Uso: restaurar.bat "ruta\al\archivo.sql"
    echo Ejemplo: restaurar.bat backups\hotel_2026-10-15_120000.sql
    exit /b 1
)

set ARCHIVO=%~1

if not exist "%ARCHIVO%" (
    echo ERROR: no existe el archivo %ARCHIVO%
    exit /b 1
)

echo ATENCION: se sobrescribira la base de datos %DB_NAME%.
set /p CONFIRMAR="Escriba SI para continuar: "
if /i not "%CONFIRMAR%"=="SI" (
    echo Operacion cancelada.
    exit /b 0
)

echo Restaurando desde %ARCHIVO% ...
"%MYSQL_BIN%\mysql.exe" -h %DB_HOST% -u %DB_USER% %DB_PASS% %DB_NAME% < "%ARCHIVO%"

if %errorlevel% neq 0 (
    echo ERROR: no se pudo restaurar el respaldo. Verifique que MySQL de XAMPP este activo.
    exit /b 1
)

echo Base de datos restaurada correctamente.
endlocal
