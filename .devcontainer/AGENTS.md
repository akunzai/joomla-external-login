# Agent Guidelines for Dev Container

See [README.md](README.md) for full setup instructions including requirements, credentials, and URLs.

## Starting the Stack

```sh
# Joomla 6 (default)
docker compose -f .devcontainer/compose.yml up -d
docker compose -f .devcontainer/compose.yml down

# Joomla 5
JOOMLA_VERSION=5.4.3 PHP_VERSION=8.3 docker compose -f .devcontainer/compose.yml build && docker compose -f .devcontainer/compose.yml up -d
docker compose -f .devcontainer/compose.yml down
```

## Working Inside the Container

Prefix project tasks with:

```sh
docker compose -f .devcontainer/compose.yml exec -w /workspace joomla <command>
```

### Common Tasks

```sh
composer install                # install dependencies
composer update                 # update dependencies
composer run lint               # check code style (dry-run)
composer run fix                # auto-fix code style
composer run phpstan            # static analysis
composer run phpstan-baseline   # update PHPStan baseline
composer validate --strict      # validate metadata
./bundle.sh                     # bundle release
```

## Managing the Joomla Extension

These commands run inside the container (no `-w /workspace` needed):

```sh
# Install
php /var/www/html/cli/joomla.php extension:install --path /workspace/dist/pkg_externallogin.zip

# List
php /var/www/html/cli/joomla.php extension:list | grep -iE '(external|caslogin)'

# Remove
bash -c "php /var/www/html/cli/joomla.php extension:list | grep -iE '(external|caslogin)' | awk '{print \$2}' | xargs -I{} php /var/www/html/cli/joomla.php extension:remove -n {}"
```

## Quick File Copy for Rapid Testing

Skip full reinstall by copying files directly:

```sh
# Copy single PHP file
docker compose -f .devcontainer/compose.yml cp src/plugins/system/caslogin/src/Extension/Caslogin.php joomla:/var/www/html/plugins/system/caslogin/src/Extension/Caslogin.php

# Copy directory
docker compose -f .devcontainer/compose.yml cp src/plugins/system/caslogin/language joomla:/var/www/html/plugins/system/caslogin/

# Copy component template
docker compose -f .devcontainer/compose.yml cp src/administrator/components/com_externallogin/tmpl/servers/default.php joomla:/var/www/html/administrator/components/com_externallogin/tmpl/servers/default.php

# Clear cache after copying
docker compose -f .devcontainer/compose.yml exec joomla php /var/www/html/cli/joomla.php cache:clean
```

## Diagnosing Issues

```sh
# Joomla error logs
tail -20 /www/html/administrator/logs/everything.php

# Container logs
docker compose -f .devcontainer/compose.yml logs --tail 100 joomla
```
