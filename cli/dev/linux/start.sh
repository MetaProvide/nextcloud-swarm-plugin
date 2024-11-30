#!/usr/bin/env bash
set -euo pipefail

# start the containers
echo "starting the containers"
docker compose --profile dev up -d --force-recreate --remove-orphans --build

# done
echo "started the dev environment"
echo "visit https://localhost"
