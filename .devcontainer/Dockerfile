# https://mcr.microsoft.com/v2/vscode/devcontainers/php/tags/list
ARG PHP_VERSION=8.2
# https://hub.docker.com/_/joomla
ARG JOOMLA_VERSION=5.0.0

FROM joomla:${JOOMLA_VERSION}-apache AS joomla

# https://github.com/devcontainers/images/blob/main/src/php/.devcontainer/Dockerfile
FROM mcr.microsoft.com/vscode/devcontainers/php:${PHP_VERSION}-apache-bookworm

ENV LANG=en_US.UTF-8
# Disable remote database security requirements.
ENV JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1

COPY --from=joomla /entrypoint.sh /
COPY --from=joomla /makedb.php /
COPY --from=joomla --chown=www-data:www-data /usr/src/joomla /usr/src/joomla
# https://hub.docker.com/_/composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN set -eux; \
    chown -R www-data:www-data /usr/src/joomla; \
    chmod -R g+w /usr/src/joomla; \
    # https://github.com/FriendsOfPHP/pickle
    curl -Lo /usr/local/bin/pickle https://github.com/FriendsOfPHP/pickle/releases/latest/download/pickle.phar && \
    chmod +x /usr/local/bin/pickle; \
    # enable Apache2 modules
    a2enmod rewrite expires include deflate remoteip headers; \
    # disable Apache2 server signature
    echo 'ServerSignature Off' >> /etc/apache2/apache2.conf; \
    echo 'ServerTokens Prod' >> /etc/apache2/apache2.conf; \
    # enable support for TLS termination
    echo 'SetEnvIf X-Forwarded-Proto https HTTPS=on' >> /etc/apache2/apache2.conf; \
    # install the PHP extensions we need
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends default-mysql-client \
    libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
    libpng-dev libxslt1-dev libzip-dev libwebp-dev libxml2-dev \
    ${PHP_EXTRA_BUILD_DEPS:-}; \
    # https://www.php.net/manual/en/image.installation.php
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) opcache \
    intl gd mysqli pcntl pdo_mysql xsl zip; \
    # set recommended PHP.ini settings
    # see https://secure.php.net/manual/en/opcache.installation.php
    docker-php-ext-enable opcache; \
	{ \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=2'; \
		echo 'opcache.fast_shutdown=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini; \
    # set recommended error logging
    { \
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
	} > /usr/local/etc/php/conf.d/error-logging.ini; \
    # Xdebug's default debugging port has changed from 9000 to 9003 since v3
    sed -i 's/9000/9003/' /usr/local/etc/php/conf.d/xdebug.ini; \
    # Reset step debugging activation from yes to default (trigger)
    sed -i '/xdebug.start_with_request/d' /usr/local/etc/php/conf.d/xdebug.ini; \
    # reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
    find /usr/local -type f -executable -exec ldd '{}' ';' \
    | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
    | sort -u \
    | xargs -r dpkg-query --search \
    | cut -d: -f1 \
    | sort -u \
    | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*;

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]