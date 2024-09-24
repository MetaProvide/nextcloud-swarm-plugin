#!/usr/bin/env bash
set -euo pipefail

# add nextcloud.local to /etc/hosts
echo "checking if nextcloud.local is in /etc/hosts"
if ! grep -q "nextcloud.local" /etc/hosts; then
	echo "adding nextcloud.local to /etc/hosts"
	echo "need sudo password to add nextcloud.local to /etc/hosts"
	echo "127.0.0.1 nextcloud.local" | sudo tee -a /etc/hosts
fi

# set up environment variables
echo "setting up environment variables"
if [ ! -f .env ]; then
	echo "creating .env file"
	cp .env.example .env
	echo "setting up .env file"
	perl -pi -e 's/REDIS_PASSWORD=/REDIS_PASSWORD=secret/g' .env
    perl -pi -e 's/MYSQL_PASSWORD=/MYSQL_PASSWORD=secret/g' .env
    perl -pi -e 's/MYSQL_ROOT_PASSWORD=/MYSQL_ROOT_PASSWORD=rootpassword/g' .env
    perl -pi -e 's/NEXTCLOUD_ADMIN_PASSWORD=/NEXTCLOUD_ADMIN_PASSWORD=swarmbox/g' .env
	perl -pi -e "s/APP_URL=localhost/APP_URL=nextcloud.local/g" .env
    echo ".env file created"
else
	echo ".env file already exists"
fi

# start the containers
echo "starting the containers"
docker compose up -d --force-recreate --remove-orphans --build

# done
echo "started the dev environment"
echo "visit https://nextcloud.local"
