# Dev Container — Agent Guidelines

**Context-offloaded SOP** for the compose stack. Human setup/credentials: @.devcontainer/README.md. Root index: @AGENTS.md.

## Starting the Stack

```sh
# Joomla 6 (default)
docker compose -f .devcontainer/compose.yml up -d
docker compose -f .devcontainer/compose.yml down

# Joomla 5
JOOMLA_VERSION=5.4.5 PHP_VERSION=8.3 docker compose -f .devcontainer/compose.yml build && docker compose -f .devcontainer/compose.yml up -d
docker compose -f .devcontainer/compose.yml down
```

## Working Inside the Container

```sh
docker compose -f .devcontainer/compose.yml exec -w /workspace joomla <command>
```

Common tasks (script SSOT: @composer.json):

```sh
composer install
composer update
composer run lint
composer run fix
composer run phpstan
composer run phpstan-baseline
composer validate --strict
./bundle.sh
```

## Managing the Joomla Extension

Run inside the container (no `-w /workspace`):

```sh
# Install
php /var/www/html/cli/joomla.php extension:install --path /workspace/dist/pkg_externallogin.zip

# List
php /var/www/html/cli/joomla.php extension:list | grep -iE '(external|caslogin)'

# Remove
bash -c "php /var/www/html/cli/joomla.php extension:list | grep -iE '(external|caslogin)' | awk '{print \$2}' | xargs -I{} php /var/www/html/cli/joomla.php extension:remove -n {}"
```

## Quick File Copy (rapid iteration)

Skip full reinstall; copy then clear cache:

```sh
docker compose -f .devcontainer/compose.yml cp src/plugins/system/caslogin/src/Extension/Caslogin.php joomla:/var/www/html/plugins/system/caslogin/src/Extension/Caslogin.php
docker compose -f .devcontainer/compose.yml cp src/plugins/system/caslogin/language joomla:/var/www/html/plugins/system/caslogin/
docker compose -f .devcontainer/compose.yml cp src/administrator/components/com_externallogin/tmpl/servers/default.php joomla:/var/www/html/administrator/components/com_externallogin/tmpl/servers/default.php
# Always clear cache as www-data. Root CLI rewrites administrator/cache/language
# as root-owned; Apache then hits Permission denied and can 500 the admin UI
# (language-load recursion).
docker compose -f .devcontainer/compose.yml exec -u www-data joomla php /var/www/html/cli/joomla.php cache:clean
```

If you already ran CLI or `compose cp` as root and admin returns HTTP 500:

```sh
docker compose -f .devcontainer/compose.yml exec joomla \
  chown -R www-data:www-data /var/www/html/administrator/cache /var/www/html/cache /var/www/html/tmp
```

## Diagnosing Issues

```sh
# Joomla error logs (inside container FS layout)
tail -20 /www/html/administrator/logs/everything.php

# Container logs
docker compose -f .devcontainer/compose.yml logs --tail 100 joomla
```

**Admin HTTP 500 after file-copy / cache:clean:** check ownership first — `ls -la /var/www/html/administrator/cache/language` owned by `root` (with Apache as `www-data`) matches this failure mode. Container logs often show `file_put_contents(.../cache/language...): Permission denied` and a deep `Language::load` stack. Fix with the `chown` above, then re-test `/administrator/`.
