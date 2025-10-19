#!/usr/bin/env bash

# shellcheck disable=1091
[[ -f .env ]] && source .env

echo "Checking database ..."
for _ in $(seq 1 20); do
  # shellcheck disable=2312
  docker compose exec mysql sh -c "mysql -u${JOOMLA_DB_USER:-root} -p${JOOMLA_DB_PASSWORD:-${MYSQL_ROOT_PASSWORD:-secret}} -e 'show databases;' 2>/dev/null" | grep -qF 'joomla' && break
  sleep 1
done

if ! docker compose exec joomla test -f configuration.php && docker compose exec joomla test -f installation/joomla.php; then
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
fi

echo "Bundling extension ..."
docker compose exec -w /workspace joomla ./bundle.sh >/dev/null

# https://docs-next.joomla.org/docs/command-line-interface/using-the-cli/
if docker compose exec joomla test -f /var/www/html/cli/joomla.php; then

  if [[ "${1:-}" == "--force" ]]; then
    docker compose exec joomla bash -c "php /var/www/html/cli/joomla.php extension:list | grep -iE '(external|caslogin)' | awk '{print \$2}' | xargs -I{} php /var/www/html/cli/joomla.php extension:remove -n {}" || true
  fi

  docker compose exec joomla php /var/www/html/cli/joomla.php extension:install --path /workspace/dist/pkg_externallogin.zip

  docker compose exec joomla php /var/www/html/cli/joomla.php cache:clean || true

  echo "Configuring logging ..."
  docker compose exec joomla php /var/www/html/cli/joomla.php config:set log_path=/tmp
  if ! docker compose exec joomla grep -q "log_everything" /var/www/html/configuration.php; then
    docker compose exec joomla sed -i "s|^}$|\tpublic \$log_everything = 1;\n}|" /var/www/html/configuration.php
  fi

fi

echo "Fixing permissions ..."
docker compose exec joomla chown -R www-data:www-data /var/www/html