#!/usr/bin/env bash
set -euo pipefail

# stop and remove the containers and volumes
echo "stopping and removing the containers and volumes";
docker compose --profile dev down --volumes --remove-orphans

# done
echo "cleaned up the dev environment"
