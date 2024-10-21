#!/bin/sh
set -e

if [ "$ENV" = "development" ]; then
	php occ config:system:set debug --value=true
	php occ config:system:set loglevel --value=0
fi
