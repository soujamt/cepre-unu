@echo off
REM ==============================================================
REM  CEPRE UNU - Actualizar a la version descargada (git pull)
REM  NO borra datos: respalda la base, instala dependencias,
REM  aplica las migraciones nuevas y compila los assets.
REM  Cierre antes la ventana de iniciar-red-local.
REM
REM  Opciones (se pueden pasar al ejecutar desde consola):
REM    -SinRespaldo   no respalda la base de datos
REM ==============================================================

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0actualizar-sistema.ps1" %*

echo.
pause
