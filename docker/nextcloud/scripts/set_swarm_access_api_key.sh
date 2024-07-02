#!/bin/bash
set -euo pipefail

NEXTCLOUD_CONFIG_FILE="/var/www/html/config/config.php"

# Function to set the Swarm access token in the config file
set_swarm_token() {
    local token="$1"
   su -s /bin/bash www-data -c "grep -q 'swarm_access_api_token' \"$NEXTCLOUD_CONFIG_FILE\""
}

# Function to check if token is already set
check_token_exists() {
    su -s /bin/bash www-data -c "grep -q 'swarm_access_api_token' \"$NEXTCLOUD_CONFIG_FILE\""
}

# Main script execution
main() {
    if [[ -n "${SWARM_ACCESS_API_TOKEN:-}" ]]; then
        echo "SWARM_ACCESS_API_TOKEN environment variable exists."

        if [[ -f "$NEXTCLOUD_CONFIG_FILE" ]]; then
            echo "Nextcloud config file exists."

            if ! check_token_exists; then
                echo "Setting swarm_access_api_token in config file."
                set_swarm_token "$SWARM_ACCESS_API_TOKEN"
            else
                echo "swarm_access_api_token already set in config file."
            fi
        else
            echo "Error: Nextcloud config file not found at $NEXTCLOUD_CONFIG_FILE"
            exit 1
        fi
    else
        echo "Warning: SWARM_ACCESS_API_TOKEN environment variable is not set."
    fi
}

# Run the main function
main
