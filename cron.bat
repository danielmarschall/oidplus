@echo off

cd /d "%~dp0"

php cron.sh

exit /b %ERRORLEVEL%
