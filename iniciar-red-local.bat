@echo off
REM ==============================================================
REM  CEPRE UNU - Iniciar en la red local
REM  Edite IP y PUERTO, guarde y haga doble clic en este archivo.
REM  La primera vez, ejecutelo como administrador (clic derecho >
REM  Ejecutar como administrador) para crear la regla de firewall.
REM ==============================================================

set IP=192.168.1.50
set PUERTO=8000

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0iniciar-red-local.ps1" -Ip %IP% -Puerto %PUERTO% %*

echo.
pause
