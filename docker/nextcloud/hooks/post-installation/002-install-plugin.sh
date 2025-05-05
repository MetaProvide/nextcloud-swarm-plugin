#!/bin/sh
set -e

# php occ app:enable files_external
# The now is capable to enable the external storage app itself if not enabled

php occ app:enable files_external_ethswarm
