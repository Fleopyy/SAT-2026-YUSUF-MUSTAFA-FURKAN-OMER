@echo off
setlocal
cd /d "%~dp0\.."

set PHP_BIN=c:\xampp\php\php.exe

if not exist "%PHP_BIN%" (
    set PHP_BIN=php
)

"%PHP_BIN%" cron\mail_worker.php %*
exit /b %ERRORLEVEL%
