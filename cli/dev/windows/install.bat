@echo off
REM Install Script for Windows

REM Check if nextcloud.local is in the hosts file
echo Checking if nextcloud.local is in C:\Windows\System32\drivers\etc\hosts
findstr /C:"nextcloud.local" C:\Windows\System32\drivers\etc\hosts >nul
if %errorlevel% neq 0 (
    echo Adding nextcloud.local to hosts file
    echo 127.0.0.1 nextcloud.local >> C:\Windows\System32\drivers\etc\hosts
) else (
    echo nextcloud.local is already in the hosts file
)

REM Set up environment variables
if not exist ".env" (
    echo Creating .env file
    copy .env.example .env

    echo Updating environment variables in .env file
    powershell -Command "(Get-Content .env) -replace 'REDIS_HOST_PASSWORD=', 'REDIS_HOST_PASSWORD=secret' | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'MYSQL_PASSWORD=', 'MYSQL_PASSWORD=secret' | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'MYSQL_ROOT_PASSWORD=', 'MYSQL_ROOT_PASSWORD=rootpassword' | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'NEXTCLOUD_ADMIN_PASSWORD=', 'NEXTCLOUD_ADMIN_PASSWORD=swarmbox' | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'APP_URL=localhost', 'APP_URL=nextcloud.local' | Set-Content .env"

    echo .env file created
) else (
    echo .env file already exists
)

REM Start the Docker containers
echo Starting the containers
docker-compose up -d --force-recreate --remove-orphans --build

REM Done
echo Started the dev environment
echo Visit https://nextcloud.local
pause
