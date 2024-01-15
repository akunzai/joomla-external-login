# https://mcr.microsoft.com/v2/vscode/devcontainers/php/tags/list
ARG PHP_VERSION=8.2
# https://hub.docker.com/_/joomla
ARG JOOMLA_VERSION=5.0.2

FROM joomla:${JOOMLA_VERSION}-apache AS joomla

# https://hub.docker.com/_/php
FROM php:${PHP_VERSION}-apache-bookworm

ENV LANG=en_US.UTF-8

# install MySQL client
RUN apt-get update && \
    apt-get install -y --no-install-recommends default-mysql-client && \
    apt-get clean -y && rm -rf /var/lib/apt/lists/*

# install pickle
# https://github.com/FriendsOfPHP/pickle
RUN set -eux; \
    curl -Lo /usr/local/bin/pickle https://github.com/FriendsOfPHP/pickle/releases/latest/download/pickle.phar && \
    chmod +x /usr/local/bin/pickle;

# install the PHP extensions we need
RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
    libpng-dev libxml2-dev libxslt1-dev libzip-dev libwebp-dev \
    ${PHP_EXTRA_BUILD_DEPS:-}; \
    # https://www.php.net/manual/en/image.installation.php
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) opcache \
    intl gd mysqli pcntl pdo_mysql soap xsl zip; \
    pickle install --no-interaction apcu; \
    pickle install --no-interaction redis; \
    pickle install --no-interaction xdebug; \
    docker-php-ext-enable apcu opcache redis xdebug; \
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

# set up Apache2
RUN set -eux; \
    # enable Apache2 modules
    a2enmod rewrite expires include deflate remoteip headers; \
    # disable Apache2 server signature
    echo 'ServerSignature Off' >> /etc/apache2/apache2.conf; \
    echo 'ServerTokens Prod' >> /etc/apache2/apache2.conf; \
    # enable support for TLS termination
    echo 'SetEnvIf X-Forwarded-Proto https HTTPS=on' >> /etc/apache2/apache2.conf;

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

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]