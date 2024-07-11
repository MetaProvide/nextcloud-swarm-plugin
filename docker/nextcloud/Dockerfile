FROM nextcloud:28.0.7

RUN pecl install xdebug; \
	docker-php-ext-enable xdebug; \
	mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
	{ \
	echo "xdebug.mode = debug"; \
	echo "xdebug.start_with_request = yes"; \
	echo "xdebug.client_host=host.docker.internal"; \
	echo "memory_limit=1024M"; \
	echo "upload_max_filesize=16G"; \
	echo "post_max_size=16G"; \
	echo "max_execution_time=3600"; \
	echo "max_input_time=3600"; \
	} > /usr/local/etc/php/conf.d/nextcloud.ini;

ENV NEXTCLOUD_UPDATE=1

ENV WWWUSER=1000
ENV WWWGROUP=1000
ENV APACHE_RUN_USER=www-user
ENV APACHE_RUN_GROUP=www-group
RUN groupadd -g $WWWGROUP $APACHE_RUN_GROUP
RUN useradd -u $WWWUSER -g $APACHE_RUN_GROUP $APACHE_RUN_USER
