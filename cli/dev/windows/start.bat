@echo off
REM Install Script for Windows

REM Start the Docker containers
echo Starting the containers
docker-compose --profile dev up -d --force-recreate --remove-orphans --build

REM Done
echo Started the dev environment
echo Visit https://localhost
pause
