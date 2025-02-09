## Installing docker-compose on Windows

The purpose of this document is to highlight possible issues (and provide solutions and workarounds) when building the docker-compose file on a Windows development environment.

After running the \\cli\\dev\\windows\\clean.bat and start.bat files (which removes any current installation of the hejbit Docker container and builds a new container/volumes), it should be possible to run Nextcloud by opening https://localhost on a browser.

### Known issues

#### Error opening https://localhost

-   If the https://localhost page returns "503 Gateway timeout" error

    or

-   returns the error "base table or view not found: 1146 Table 'nextcloud.oc_appconfig' doesn't exist":

    ![Error: missing tables in nextcloud database](.attachments.136930/1464-316-max%20%282%29.png)

    -   then check the /config/config.php on the nextcloud container for the following entries:

        ![Expected entries in config.php](.attachments.136930/image%20%283%29.png)

    -   If entries are missing (namely that identify the dbhost, dbuser, dbpassword etc), then this could be due to Windows build process not being able to read the \\.env file in the docker-compose.yaml file. The cause of this is unknown. As a workaround, it is good practice to set the default value of environment variables inside the docker-compose (and not rely on the settings defined in \\.env file) eg:

    ![NC_VERSION default value is set in the docker-compose](.attachments.136930/image%20%284%29.png)

    -   Note: After any changes to docker-compose, remember to rebuild the docker-compose:
        -   docker-compose --profile dev up -d --force-recreate --remove-orphans --build

##### Known issue: Local path containimng NC source code has folder(s) with special character.

When the docker-compose copies the NC source code to the local Windows path, there is a known issue with a folder containing a special character, eg:

![Invalid Folder with special character](.attachments.136930/image%20%285%29.png)

The cause of this incorrect folder is unknown. The impact is that it could prevent xDebug from working because it cannot find the local source code.

To correct this issue , the easiest workaround is to rename the incorrect folder with a valid name, eg code. and then change the PathMapping setting in the \\.vscode\\launch.json file:

![Ensure the /var/www/html path maps correctly to the local sourcecode](.attachments.136930/image%20%286%29.png)
