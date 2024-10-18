#!/bin/sh
set -e

php occ config:system:set trusted_domains 1 --value="nextcloud.local"

[ -z "$APP_URL" ] || [ "$APP_URL" = "localhost" ] && exit 0

php occ config:system:set trusted_domains 2 --value="$APP_URL"
