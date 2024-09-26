#!/usr/bin/env bash
set -euo pipefail

# stop and remove the containers and volumes
echo "stopping and removing the containers and volumes";
docker compose down --volumes --remove-orphans

# remove nextcloud source code
echo "removing nextcloud source code";
echo "need sudo password to remove dev-environment directory"
sudo rm -rf dev-environment

# done
echo "cleaned up the dev environment"
