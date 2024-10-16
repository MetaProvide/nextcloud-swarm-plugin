#!/bin/sh
set -e

[ -z "$APP_URL" ] && exit
[ "$APP_URL" = "localhost" ] && exit

php occ config:system:set trusted_domains 1 --value=$APP_URL
