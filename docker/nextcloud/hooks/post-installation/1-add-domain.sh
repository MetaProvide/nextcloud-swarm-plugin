#!/bin/sh
set -e

php occ config:system:set trusted_domains 1 --value=$APP_URL
