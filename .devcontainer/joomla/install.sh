#!/usr/bin/env bash

# Set default compose file relative to this script's location
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export COMPOSE_FILE="${COMPOSE_FILE:-${SCRIPT_DIR}/../compose.yml}"
# Disable TTY if stdin or stdout is not a terminal
TTY_FLAG=""
if [[ ! -t 0 ]] || [[ ! -t 1 ]]; then
  TTY_FLAG="-T"
fi

# Helper function for docker compose exec
dc_exec() {
  # shellcheck disable=SC2086
  docker compose exec ${TTY_FLAG} "$@"
}

# shellcheck disable=1091
[[ -f "${SCRIPT_DIR}/.env" ]] && source "${SCRIPT_DIR}/.env"

ADMIN_USERNAME="${ADMIN_USERNAME:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-ChangeTheP@ssw0rd}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
JOOMLA_DB_HOST="${JOOMLA_DB_HOST:-mysql}"
JOOMLA_DB_NAME="${JOOMLA_DB_NAME:-joomla}"
JOOMLA_DB_PREFIX="${JOOMLA_DB_PREFIX:-jos_}"
JOOMLA_DB_PASSWORD="${JOOMLA_DB_PASSWORD:-${MYSQL_ROOT_PASSWORD:-secret}}"
JOOMLA_DB_USER="${JOOMLA_DB_USER:-root}"
JOOMLA_COOKIE_DOMAIN="${JOOMLA_COOKIE_DOMAIN:-}"

# Helper function for MySQL operations
joomla_mysql() {
  # Always use -T for MySQL operations (no TTY needed)
  # shellcheck disable=SC2086
  docker compose exec -T -e MYSQL_PWD="${JOOMLA_DB_PASSWORD}" mysql mysql -u"${JOOMLA_DB_USER}" "${JOOMLA_DB_NAME}" "$@"
}

echo "Waiting for Joomla container to initialize ..."
for i in $(seq 1 60); do
  if dc_exec joomla test -f index.php && dc_exec joomla test -f libraries/src/Version.php; then
    echo "Joomla files are ready (attempt ${i})"
    break
  fi
  if [[ "${i}" -eq 60 ]]; then
    echo "ERROR: Joomla container initialization timed out after 60 seconds"
    exit 1
  fi
  sleep 1
done

# Install CA certificate for SSL connections (for Joomla to trust Keycloak)
if dc_exec joomla test -f /run/secrets/ca.pem; then
  echo "Installing CA certificate for SSL connections ..."
  dc_exec joomla cp /run/secrets/ca.pem /usr/local/share/ca-certificates/dev-ca.crt
  dc_exec joomla update-ca-certificates
fi

echo "Checking database ..."
for _ in $(seq 1 20); do
  # Always use -T for MySQL operations (no TTY needed)
  # shellcheck disable=SC2086,SC2312
  docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD:-secret}" mysql mysql -uroot -e 'show databases;' 2>/dev/null | grep -qF 'joomla' && break
  sleep 1
done

if ! dc_exec joomla test -f configuration.php && dc_exec joomla test -f installation/joomla.php; then
  # https://docs-next.joomla.org/docs/command-line-interface/joomla-cli-installation
  dc_exec --user www-data joomla php installation/joomla.php install \
    --site-name DEMO \
    --admin-user ADMIN  \
    --admin-username "${ADMIN_USERNAME}" \
    --admin-password "${ADMIN_PASSWORD}" \
    --admin-email "${ADMIN_EMAIL}" \
    --db-host "${JOOMLA_DB_HOST}" \
    --db-name "${JOOMLA_DB_NAME}" \
    --db-user "${JOOMLA_DB_USER}" \
    --db-pass "${JOOMLA_DB_PASSWORD}" \
    --db-prefix "${JOOMLA_DB_PREFIX}" \
    --no-interaction
fi

echo "Checking composer dependencies ..."
if ! dc_exec -w /workspace joomla test -d vendor; then
  echo "Installing composer dependencies ..."
  dc_exec -w /workspace joomla composer install --quiet
fi

echo "Bundling extension ..."
dc_exec -w /workspace joomla ./bundle.sh >/dev/null

# https://docs-next.joomla.org/docs/command-line-interface/using-the-cli/
if dc_exec joomla test -f /var/www/html/cli/joomla.php; then

  if [[ "${1:-}" == "--force" ]]; then
    dc_exec joomla bash -c "php /var/www/html/cli/joomla.php extension:list | grep -iE 'magebridge' | awk '{print \$2}' | xargs -I{} php /var/www/html/cli/joomla.php extension:remove -n {}" || true
  fi

  dc_exec joomla php /var/www/html/cli/joomla.php extension:install --path /workspace/dist/pkg_externallogin.zip

  echo "Enabling External Login plugins ..."
  # Enable Authentication - External Login
  joomla_mysql -e "UPDATE ${JOOMLA_DB_PREFIX}extensions SET enabled=1 WHERE element='externallogin' AND type='plugin' AND folder='authentication';"
  # Enable System - External Login
  joomla_mysql -e "UPDATE ${JOOMLA_DB_PREFIX}extensions SET enabled=1 WHERE element='externallogin' AND type='plugin' AND folder='system';"
  # Enable System - CAS Login
  joomla_mysql -e "UPDATE ${JOOMLA_DB_PREFIX}extensions SET enabled=1 WHERE element='caslogin' AND type='plugin' AND folder='system';"

  dc_exec joomla php /var/www/html/cli/joomla.php cache:clean || true

  echo "Adding CAS Server definition ..."
  CAS_PARAMS='{"autoregister":"1","autoupdate":"1","ssl":"1","url":"auth.dev.local","dir":"realms/demo/protocol/cas","cas_v3":"1","port":"443","username_xpath":"string(cas:attributes/cas:email)","name_xpath":"string(cas:attributes/cas:display_name)","email_xpath":"string(cas:attributes/cas:email)"}'
  joomla_mysql -e "INSERT IGNORE INTO ${JOOMLA_DB_PREFIX}externallogin_servers (title, published, plugin, ordering, params) VALUES ('Keycloak', 1, 'system.caslogin', 1, '${CAS_PARAMS}');"

  echo "Configuring External Login module ..."
  # Get the server ID for Keycloak
  # shellcheck disable=2312
  SERVER_ID=$(joomla_mysql -sN -e "SELECT id FROM ${JOOMLA_DB_PREFIX}externallogin_servers WHERE title='Keycloak' LIMIT 1;" | tr -d '\r')
  # Update module params with server selection and show_logout
  MODULE_PARAMS="{\"server\":[\"${SERVER_ID}\"],\"cache\":\"0\",\"layout\":\"_:default\",\"show_logout\":\"1\"}"
  joomla_mysql -e "UPDATE ${JOOMLA_DB_PREFIX}modules SET published=1, position='sidebar-right', params='${MODULE_PARAMS}' WHERE module='mod_externallogin_site' AND client_id=0;"
  # Get module ID
  # shellcheck disable=2312
  MODULE_ID=$(joomla_mysql -sN -e "SELECT id FROM ${JOOMLA_DB_PREFIX}modules WHERE module='mod_externallogin_site' AND client_id=0 LIMIT 1;" | tr -d '\r')
  # Set module to show on all pages (menu assignment)
  joomla_mysql -e "DELETE FROM ${JOOMLA_DB_PREFIX}modules_menu WHERE moduleid=${MODULE_ID}; INSERT INTO ${JOOMLA_DB_PREFIX}modules_menu (moduleid, menuid) VALUES (${MODULE_ID}, 0);"

  echo "Configuring Joomla settings ..."
  dc_exec joomla php /var/www/html/cli/joomla.php config:set log_path=/var/www/html/administrator/logs
  dc_exec joomla php /var/www/html/cli/joomla.php config:set sef_rewrite=true
  if ! dc_exec joomla grep -q "log_everything" /var/www/html/configuration.php; then
    dc_exec joomla sed -i "s|^}$|\tpublic \$log_everything = 1;\n}|" /var/www/html/configuration.php
  fi

  if [[ -n "${JOOMLA_COOKIE_DOMAIN}" ]]; then
    echo "Configuring cookie domain ..."
    if ! dc_exec joomla grep -q "cookie_domain" /var/www/html/configuration.php; then
      dc_exec joomla sed -i "s|^}$|\tpublic \$cookie_domain = '${JOOMLA_COOKIE_DOMAIN}';\n}|" /var/www/html/configuration.php
    else
      dc_exec joomla sed -i "s|public \$cookie_domain = .*|public \$cookie_domain = '${JOOMLA_COOKIE_DOMAIN}';|" /var/www/html/configuration.php
    fi
  fi
fi

echo "Fixing permissions ..."
dc_exec joomla chown -R www-data:www-data /var/www/html