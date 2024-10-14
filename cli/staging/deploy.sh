#!/bin/bash
cd /opt/hejbit

deploy() {
	local version=$1
	cd hejbit-$version
	git pull
	npm install
	npm run build
	docker exec -u www-data hejbit-$version-nextcloud-1 php occ upgrade
}

for version in {28..30}
do
	deploy $version &
done

wait

