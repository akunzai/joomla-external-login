#!/usr/bin/env bash

if docker compose exec joomla test -f configuration.php; then
  echo "Joomla! already installed!"
  exit 0
fi

# shellcheck disable=1091
[[ -f .env ]] && source .env

for _ in $(seq 1 20); do
  # shellcheck disable=2312
  docker compose exec mysql sh -c "mysql -u${JOOMLA_DB_USER:-root} -p${JOOMLA_DB_PASSWORD:-${MYSQL_ROOT_PASSWORD:-secret}} -e 'show databases;' 2>/dev/null" | grep -qF 'joomla' && break
  sleep 1
done

if ! docker compose exec joomla test -f installation/joomla.php; then
  echo "Joomla! CLI installer not found! (requires Joomla! version >= 4.3)"
  exit 1
fi

# https://docs.joomla.org/J4.x:Joomla_CLI_Installation
docker compose exec --user www-data joomla php installation/joomla.php install \
  --site-name DEMO \
  --admin-user ADMIN  \
  --admin-username "${ADMIN_USERNAME:-admin}" \
  --admin-password "${ADMIN_PASSWORD:-ChangeTheP@ssw0rd}" \
  --admin-email "${ADMIN_EMAIL:-admin@example.com}" \
  --db-host "${JOOMLA_DB_HOST:-mysql}" \
  --db-name "${JOOMLA_DB_NAME:-joomla}" \
  --db-user "${JOOMLA_DB_USER:-root}" \
  --db-pass "${JOOMLA_DB_PASSWORD:-${MYSQL_ROOT_PASSWORD:-secret}}" \
  --db-prefix "${JOOMLA_DB_PREFIX:-vlqhe_}" \
  --no-interaction

echo "Fixing permissions ..."
docker compose exec joomla chown -R www-data:www-data /var/www/html