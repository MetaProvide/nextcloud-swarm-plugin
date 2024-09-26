@echo off
REM Clean Script for Windows

REM Stop and remove Docker containers and volumes
echo Stopping and removing the containers and volumes
docker-compose down --volumes --remove-orphans

REM Remove Nextcloud source code
set devEnvironmentDir=dev-environment
if exist %devEnvironmentDir% (
    echo Removing nextcloud source code
    rmdir /S /Q %devEnvironmentDir%
    echo Removed %devEnvironmentDir% directory
) else (
    echo %devEnvironmentDir% does not exist
)

REM Done
echo Cleaned up the dev environment
pause
