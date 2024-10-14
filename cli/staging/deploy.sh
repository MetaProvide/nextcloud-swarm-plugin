#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[0;33m'
NC='\033[0m'

log_message() {
	echo -e "${BLUE}$1${NC}"
}

log_warning() {
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
		echo -e "$line"
	done
	output=()
}

deploy_error() {
	local version=$1
	local result=$2
	print_buffer
	log_error "$result"
	log_error "failed to deploy hejbit-$version"
	log_gap
	exit 1
}

exec_action() {
	local action=$1
	local message=$2
	buffer "$(log_message "$message")"
	result=$($action) || deploy_error "$version" "$result"
	buffer "$result"
}

deploy() {
	local version=$1
	cd hejbit-"$version" || log_error "hejbit-$version not found"

	buffer "$(log_warning "deploying hejbit-$version")"

	exec_action "sync_code" "syncing code"

	exec_action "build_app" "building app"

	exec_action "nextcloud_upgrade" "upgrading Nextcloud"

	buffer "$(log_success "deployed hejbit-$version")"

	print_buffer
	log_gap
}

sync_code() {
	git reset --hard > /dev/null 2>&1
	git pull || return 1
}

build_app() {
	result=$(npm install 2>&1)
	status=$?
	echo -e "$result"

	if [ $status -ne 0 ]; then
		result=$(npm run build 2>&1)
		status=$?
		echo -e "$result"
	fi

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
    echo -e "$result"
    return $status
}

cd /opt/hejbit || log_error "/opt/hejbit not found"
log_message "deploying hejbit to staging"
log_gap

DEPLOYMENT=()
TIMESTAMP=$(date +%s)
for version in {28..30}; do
	deploy "$version" &
	DEPLOYMENT+=($!)
done

statuses=0
for pid in "${DEPLOYMENT[@]}"; do
	wait "$pid"
	statuses+=$?
done

if [ $statuses -ne 0 ]; then
	log_error "failed to deploy hejbit to staging"
	exit 1
fi
log_success "deployed hejbit to staging"
