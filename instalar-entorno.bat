@echo off
REM ==============================================================
REM  CEPRE UNU - Instalar entorno con base de datos limpia
REM  Carga catalogos y mantenedores, sin procesos ni inscripciones.
REM  ATENCION: borra y recrea todas las tablas de la base del .env.
REM
REM  Opciones (se pueden pasar al ejecutar desde consola):
REM    -ConAulasDeEjemplo   agrega 20 aulas genericas
REM ==============================================================

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0instalar-entorno.ps1" %*

echo.
pause
