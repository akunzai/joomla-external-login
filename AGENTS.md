# Agent Guidelines for Joomla External Login

## Environment Quick Facts

- Use the VS Code dev container; see `.devcontainer/README.md` for full setup.
- Services expose Joomla `80`, Keycloak `8443`, and MySQL `3306`.

## Essential Commands

- Start / stop stack
  - `docker compose -f .devcontainer/compose.yml up -d`
  - `docker compose -f .devcontainer/compose.yml down`
- Work inside the container
  - Prefix project tasks with `docker compose -f .devcontainer/compose.yml exec -w /workspace joomla`
  - Install / update dependencies: `composer install`, `composer update`
  - Code style: `composer run lint` (dry-run), `composer run fix` (auto-fix)
  - Static analysis: `composer run phpstan`, `composer run phpstan-baseline`
  - Validate metadata: `composer validate --strict`
  - Bundle release: `./bundle.sh`
- Manage extension in Joomla CLI (no workspace flag required)
  - Install: `php /var/www/html/cli/joomla.php extension:install --path /workspace/dist/pkg_externallogin.zip`
  - List: `php /var/www/html/cli/joomla.php extension:list | grep -iE '(external|caslogin)'`
  - Remove: `bash -c "php /var/www/html/cli/joomla.php extension:list | grep -iE '(external|caslogin)' | awk '{print $2}' | xargs -I{} php /var/www/html/cli/joomla.php extension:remove -n {}"`
- Diagnose issues
  - Joomla errors: `tail -20 /tmp/everything.php`
  - Container logs: `docker compose -f .devcontainer/compose.yml logs --tail 100 joomla`

## Code Style Highlights

- Follow PSR-12 with PHP 8.1 migration rules.
- Order imports: `Joomla\CMS` → other Joomla → project namespaces, alphabetically.
- Use fully qualified strict types and native function casing.
- Keep PHPDoc blocks meaningful, aligned, and free of empty tags.
- Naming: classes PascalCase, methods camelCase, files lowercase_underscores.
- Maintain Joomla MVC inheritance patterns.
- Include `defined('_JEXEC') or die;` at PHP entry points.
- Use Joomla exceptions and `Text` for user-facing messages.
