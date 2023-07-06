# https://mcr.microsoft.com/v2/vscode/devcontainers/php/tags/list
ARG VARIANT=8.1-apache-bookworm
# https://github.com/devcontainers/images/blob/main/src/php/.devcontainer/Dockerfile
FROM mcr.microsoft.com/vscode/devcontainers/php:${VARIANT}

ENV LANG=C.UTF-8
# Disable remote database security requirements.
ENV JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1

# install MariaDB client
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y mariadb-client \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# enable Apache2 modules
RUN a2enmod rewrite expires include deflate remoteip headers;

# install the PHP extensions we need.
RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
    libpng-dev libxslt1-dev libzip-dev libwebp-dev \
    ${PHP_EXTRA_BUILD_DEPS:-}; \
    rm -rf /var/lib/apt/lists/*; \
    # https://www.php.net/manual/en/image.installation.php
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) opcache \
    intl gd mysqli pcntl pdo_mysql xsl zip; \
    # reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
    find /usr/local -type f -executable -exec ldd '{}' ';' \
    | awk '/=>/ { print $(NF-1) }' \
    | sort -u \
    | xargs -r dpkg-query --search \
    | cut -d: -f1 \
    | sort -u \
    | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false;

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN set -eux; \
	docker-php-ext-enable opcache; \
	{ \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=2'; \
		echo 'opcache.fast_shutdown=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini
# set recommended error logging
RUN { \
# https://www.php.net/manual/en/errorfunc.constants.php
		echo 'error_reporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR'; \
		echo 'display_errors = Off'; \
		echo 'display_startup_errors = Off'; \
		echo 'log_errors = On'; \
		echo 'error_log = /dev/stderr'; \
		echo 'log_errors_max_len = 1024'; \
		echo 'ignore_repeated_errors = On'; \
		echo 'ignore_repeated_source = Off'; \
		echo 'html_errors = Off'; \
	} > /usr/local/etc/php/conf.d/error-logging.ini

# Xdebug's default debugging port has changed from 9000 to 9003 since v3
RUN sed -i 's/9000/9003/' /usr/local/etc/php/conf.d/xdebug.ini
# Reset step debugging activation from yes to default (trigger)
RUN sed -i '/xdebug.start_with_request/d' /usr/local/etc/php/conf.d/xdebug.ini

# https://hub.docker.com/_/joomla
ARG JOOMLA_VERSION=3.10.11
# Download package and extract to web volume
RUN set -ex; \
	curl -o joomla.tar.bz2 -SL https://github.com/joomla/joomla-cms/releases/download/${JOOMLA_VERSION}/Joomla_${JOOMLA_VERSION}-Stable-Full_Package.tar.bz2; \
	mkdir /usr/src/joomla; \
	tar -xf joomla.tar.bz2 -C /usr/src/joomla; \
	rm joomla.tar.bz2; \
	chown -R www-data:www-data /usr/src/joomla; \
    chmod -R g+w /usr/src/joomla;

COPY --from=joomla:3.10.11 /entrypoint.sh /entrypoint.sh
COPY --from=joomla:3.10.11 /makedb.php /makedb.php

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]