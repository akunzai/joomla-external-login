#!/usr/bin/env bash

[ -f .env ] && source .env

docker compose exec joomla sh -c "[ -f configuration.php ] && [ ! -f installation/joomla.php ]"
if [ "$?" -eq "0" ]; then
  echo "Joomla! already installed!"
  exit 0
fi

for i in $(seq 1 20); do
  docker compose exec mysql sh -c "mysql -uroot -p${MYSQL_ROOT_PASSWORD:-secret} -e 'show databases;' 2>/dev/null" | grep -qF 'joomla' && break
  sleep 1
done

# https://docs.joomla.org/J4.x:Joomla_CLI_Installation
docker compose exec joomla php installation/joomla.php install \
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
