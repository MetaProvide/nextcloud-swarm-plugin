@echo off
REM Clean Script for Windows

REM Stop and remove Docker containers and volumes
echo Stopping and removing the containers and volumes
docker-compose down --volumes --remove-orphans

REM Done
echo Cleaned up the dev environment
pause
