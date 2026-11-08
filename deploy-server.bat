@echo off
setlocal
title E-Raport SDN 2 Karangsari - Interactive Deploy Toolkit

where powershell >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-server.ps1"
    exit /b %ERRORLEVEL%
)

echo [ERROR] PowerShell tidak ditemukan pada sistem ini.
pause
exit /b 1
