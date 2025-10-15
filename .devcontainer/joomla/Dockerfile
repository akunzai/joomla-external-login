ARG PHP_VERSION=8.3.24
ARG PHP_EXTRA_BUILD_DEPS=""
# https://hub.docker.com/_/joomla
ARG JOOMLA_VERSION=5.4.0

FROM joomla:${JOOMLA_VERSION}-apache AS joomla

# https://hub.docker.com/_/php
FROM php:${PHP_VERSION}-apache

ENV LANG=en_US.UTF-8

# hadolint ignore=SC2086
RUN --mount=type=cache,target=/var/lib/apt/lists,sharing=locked \
    --mount=type=cache,target=/var/cache/apt,sharing=locked \
    set -eux; \
    apt-get update; \
    # install the system tools we need
    apt-get install -y --no-install-recommends mariadb-client unzip; \
    # install the PHP extensions we need
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get install -y --no-install-recommends \
    libfreetype6-dev libicu-dev \
    libjpeg62-turbo-dev libpng-dev \
    libxml2-dev libxslt1-dev \
    libzip-dev libwebp-dev \
    ${PHP_EXTRA_BUILD_DEPS:-}; \
    # https://www.php.net/manual/en/image.installation.php
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) opcache \
    intl gd mysqli pcntl pdo_mysql soap xsl zip; \
    curl -Lo /usr/local/bin/pickle https://github.com/FriendsOfPHP/pickle/releases/latest/download/pickle.phar && \
    chmod +x /usr/local/bin/pickle; \
    pickle install --no-interaction apcu-stable; \
    pickle install --no-interaction redis-stable; \
    pickle install --no-interaction xdebug-stable; \
    docker-php-ext-enable apcu opcache redis xdebug; \
    # reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
    find /usr/local -type f -executable -exec ldd '{}' ';' 2>/dev/null \
    | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
    | sort -u \
    | xargs -r dpkg-query --search \
    | grep -v -i '^diversion' \
    | cut -d: -f1 \
    | sort -u \
    | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /tmp/*;

# set up Apache2
RUN set -eux; \
    # enable Apache2 modules
    a2enmod rewrite expires include deflate remoteip headers; \
    { \
        # disable Apache2 server signature
        echo 'ServerSignature Off'; \
        echo 'ServerTokens Prod'; \
        # enable support for TLS termination
        echo 'SetEnvIf X-Forwarded-Proto https HTTPS=on'; \
    } >> /etc/apache2/apache2.conf;

# install composer
# https://hub.docker.com/_/composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# set up Joomla!
# Disable remote database security requirements.
ENV JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1
COPY --from=joomla /entrypoint.sh /
COPY --from=joomla /makedb.php /
COPY --from=joomla --chown=www-data:www-data /usr/src/joomla /usr/src/joomla
RUN set -eux; \
    chown -R www-data:www-data /usr/src/joomla; \
    chmod -R g+w /usr/src/joomla;

ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "apache2-foreground" ]