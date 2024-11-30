#!/bin/sh
set -e

php occ app:install onlyoffice

php occ config:app:set onlyoffice DocumentServerUrl --value="https://onlyoffice.local/"
php occ config:app:set onlyoffice DocumentServerInternalUrl --value="https://onlyoffice.local/"
php occ config:app:set onlyoffice StorageUrl --value="https://nextcloud.local/"
php occ config:system:set onlyoffice verify_peer_off --value="true"
php occ config:system:set onlyoffice jwt_secret --value="secret"
php occ config:system:set onlyoffice jwt_header --value="AuthorizationJwt"
