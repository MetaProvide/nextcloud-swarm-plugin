#!/bin/bash
set -e

cd /var/www/html
chown -R www-data:www-data custom_apps 2> /dev/null || true
echo "changed custom_apps owner"
