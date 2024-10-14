FROM nextcloud:30-fpm-alpine AS base

FROM base AS production

ENV NEXTCLOUD_UPDATE=1

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
	{ \
	echo "memory_limit=1024M"; \
	echo "upload_max_filesize=16G"; \
	echo "post_max_size=16G"; \
	echo "max_execution_time=3600"; \
	echo "max_input_time=3600"; \
	} > /usr/local/etc/php/conf.d/nextcloud.ini;

COPY --chown=www-data ./hooks /docker-entrypoint-hooks.d

RUN mkdir -p /var/www/html/custom_apps && \
    chown -R www-data:www-data /var/www/html/custom_apps


FROM production AS development

RUN apk add --update --no-cache linux-headers $PHPIZE_DEPS;
RUN pecl install xdebug && docker-php-ext-enable xdebug;
RUN { \
	echo "xdebug.mode=develop,debug"; \
	echo "xdebug.start_with_request=trigger"; \
    echo "xdebug.discover_client_host=true"; \
	echo "xdebug.client_host=host.docker.internal"; \
	} >> /usr/local/etc/php/conf.d/nextcloud.ini;
