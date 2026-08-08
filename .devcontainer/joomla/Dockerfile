ARG PHP_VERSION=8.4
ARG PHP_EXTRA_BUILD_DEPS=""
# https://hub.docker.com/_/joomla
ARG JOOMLA_VERSION=6.1.2

# Joomla upstream tree only — changes to JOOMLA_VERSION must not rebuild PHP extensions.
FROM joomla:${JOOMLA_VERSION}-apache AS joomla-src

# -----------------------------------------------------------------------------
# php-ext: expensive compile layer (docker-php-ext-install + PECL).
# Cache key depends on PHP_VERSION / PHP_EXTRA_BUILD_DEPS / this stage only.
# -----------------------------------------------------------------------------
# https://hub.docker.com/_/php
FROM php:${PHP_VERSION}-apache AS php-ext

ARG PHP_EXTRA_BUILD_DEPS

ENV LANG=en_US.UTF-8

# hadolint ignore=SC2086
RUN --mount=type=cache,target=/var/lib/apt/lists,sharing=locked \
    --mount=type=cache,target=/var/cache/apt,sharing=locked \
    set -eux; \
    apt-get update; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get install -y --no-install-recommends \
    libfreetype6-dev libicu-dev libssl-dev \
    libjpeg62-turbo-dev libpng-dev \
    libxml2-dev libxslt1-dev \
    libzip-dev libwebp-dev \
    ${PHP_EXTRA_BUILD_DEPS:-}; \
    # https://www.php.net/manual/en/image.installation.php
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) ftp \
    gd intl mysqli opcache pcntl pdo_mysql soap xsl zip; \
    # Pin pickle release so this layer is reproducible (not floating "latest").
    # https://github.com/FriendsOfPHP/pickle/releases
    curl -fsSL -o /usr/local/bin/pickle \
      https://github.com/FriendsOfPHP/pickle/releases/download/v0.7.11/pickle.phar; \
    chmod +x /usr/local/bin/pickle; \
    pickle install --no-interaction apcu-stable; \
    pickle install --no-interaction redis-stable; \
    pickle install --no-interaction xdebug-stable; \
    docker-php-ext-enable apcu opcache redis xdebug; \
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

# -----------------------------------------------------------------------------
# Runtime image: cheap layers on top of php-ext; JOOMLA_VERSION only hits the end.
# -----------------------------------------------------------------------------
FROM php-ext

# Runtime OS tools (separate from extension compile so tool list changes stay cheap)
RUN --mount=type=cache,target=/var/lib/apt/lists,sharing=locked \
    --mount=type=cache,target=/var/cache/apt,sharing=locked \
    set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git mariadb-client unzip rsync; \
    rm -rf /tmp/*;

# set up Apache2
RUN set -eux; \
    a2enmod rewrite expires include deflate remoteip headers; \
    { \
        echo 'ServerSignature Off'; \
        echo 'ServerTokens Prod'; \
        echo 'SetEnvIf X-Forwarded-Proto https HTTPS=on'; \
    } >> /etc/apache2/apache2.conf;

# Pin major tag (not :latest) so COPY --from digest churn is limited.
# https://hub.docker.com/_/composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# set up Joomla! (invalidates only from here when JOOMLA_VERSION changes)
ENV JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1
COPY --from=joomla-src /entrypoint.sh /
COPY --from=joomla-src /makedb.php /
COPY --from=joomla-src --chown=www-data:www-data /usr/src/joomla /usr/src/joomla
RUN set -eux; \
    chown -R www-data:www-data /usr/src/joomla; \
    chmod -R g+w /usr/src/joomla;

ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "apache2-foreground" ]
