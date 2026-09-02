#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[0;33m'
NC='\033[0m'

log () {
	echo -e "$1"
}

log_message() {
	echo -e "${BLUE}$1${NC}"
}

log_note() {
	echo -e "${YELLOW}$1${NC}"
}

log_success() {
	echo -e "${GREEN}$1${NC}"
}

log_error() {
	echo -e "${RED}$1${NC}"
}

log_gap() {
	echo -e "-------------------------------------"
}

output=()
buffer() {
	local line=$1
	output+=("$line")
}

print_buffer() {
	for line in "${output[@]}"
	do
		log "$line"
	done
	output=()
}

failed() {
	local version=$1
	local result=$2
	print_buffer
	log_error "$result"
	log_error "failed to deploy hejbit-$version"
	log_gap
	exit 1
}

action() {
	local action=$1
	local message=$2
	buffer "$(log_message "$message")"
	result=$($action) || failed "$version" "$result"
	buffer "$result"
}

deploy() {
	local version=$1
	cd "/opt/hejbit/hejbit-$version" || failed "$version" "/opt/hejbit/hejbit-$version not found"

	buffer "$(log_note "deploying hejbit-$version")"

	action "sync_code" "syncing code"

	action "build_app" "building app"

	action "nextcloud_upgrade" "upgrading nextcloud"

	buffer "$(log_success "deployed hejbit-$version")"

	print_buffer
	log_gap
}

sync_code() {
	git reset --hard > /dev/null 2>&1
	git pull 2>&1 || return 1
}

setup_node() {
	command -v pnpm > /dev/null 2>&1 && return 0

	export NVM_DIR="$HOME/.nvm"
	if [ -s "$NVM_DIR/nvm.sh" ]; then
		. "$NVM_DIR/nvm.sh"

		NODE_24_VERSION=$(nvm version 24 2> /dev/null)
		if [ "$NODE_24_VERSION" != "N/A" ]; then
			nvm use --silent "$NODE_24_VERSION" > /dev/null 2>&1 || return 1
		else
			nvm use --silent default > /dev/null 2>&1 || return 1
		fi
	fi

	if command -v corepack > /dev/null 2>&1; then
		corepack enable > /dev/null 2>&1 || return 1
		corepack prepare pnpm@10.28.2 --activate > /dev/null 2>&1 || return 1
	fi

	command -v pnpm > /dev/null 2>&1 || return 1
}

build_app() {
	rm -rf css js

	result=$(pnpm install --frozen-lockfile 2>&1)
	status=$?
	log "$result"

	[ $status -ne 0 ] && return $status

	result=$(pnpm run build 2>&1)
	status=$?
	log "$result"

	return $status
}

nextcloud_upgrade() {
	INFO_XML="appinfo/info.xml"
    CURRENT_VERSION=$(grep -oPm1 "(?<=<version>)[^<]+" "$INFO_XML")
    NEW_VERSION="${CURRENT_VERSION}_${TIMESTAMP}"

    sed -i "s/<version>${CURRENT_VERSION}<\/version>/<version>${NEW_VERSION}<\/version>/" "$INFO_XML"
    log_message "bumped version to $NEW_VERSION"

    result=$(docker exec -u www-data hejbit-"$version"-nextcloud-1 php occ upgrade 2>&1)
    status=$?
    log "$result"
    return $status
}

cd /opt/hejbit || log_error "/opt/hejbit not found"

if ! setup_node; then
	log_error "pnpm not found for user $(whoami)"
	log_error "install node/pnpm (or corepack) or configure nvm default"
	exit 1
fi

log_note "deploying hejbit to staging"
log_gap

TIMESTAMP=$(date +%s)
for version in {32..34}; do
	deploy "$version"
done

log_success "deployed hejbit to staging"
