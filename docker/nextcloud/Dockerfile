FROM nextcloud:23

RUN pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    { \
        echo "xdebug.mode = debug"; \
        echo "xdebug.start_with_request = yes"; \
        echo "xdebug.client_host=host.docker.internal"; \
    } > /usr/local/etc/php/conf.d/nextcloud.ini;

ENV NEXTCLOUD_UPDATE=1
