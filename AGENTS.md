# Agent Guidelines for Joomla External Login

## Environment Quick Facts

- Use the VS Code dev container; see `.devcontainer/README.md` for full setup.
- Services expose Traefik `443`, and MySQL `3306`.
- TLS certificates required: run `.devcontainer/generate-certs.sh .devcontainer/.secrets` to generate certs with mkcert.
- All services now use HTTPS through Traefik reverse proxy.
- Access URLs: Joomla at `https://www.dev.local`, Keycloak at `https://auth.dev.local`.

## Essential Commands

- Start / stop stack (Joomla 6, default)
  - `docker compose -f .devcontainer/compose.yml up -d`
  - `docker compose -f .devcontainer/compose.yml down`
- Start / stop stack (Joomla 5)
  - `docker compose -f .devcontainer/compose.yml -f .devcontainer/compose.joomla5.yml up -d`
  - `docker compose -f .devcontainer/compose.yml -f .devcontainer/compose.joomla5.yml down`
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
- Quick file copy for rapid testing (skip full reinstall)
  - Copy single PHP file: `docker compose -f .devcontainer/compose.yml cp src/plugins/system/caslogin/src/Extension/Caslogin.php joomla:/var/www/html/plugins/system/caslogin/src/Extension/Caslogin.php`
  - Copy directory: `docker compose -f .devcontainer/compose.yml cp src/plugins/system/caslogin/language joomla:/var/www/html/plugins/system/caslogin/`
  - Copy component template: `docker compose -f .devcontainer/compose.yml cp src/administrator/components/com_externallogin/tmpl/servers/default.php joomla:/var/www/html/administrator/components/com_externallogin/tmpl/servers/default.php`
  - After copying, clear cache: `docker compose -f .devcontainer/compose.yml exec joomla php /var/www/html/cli/joomla.php cache:clean`
- Diagnose issues
  - Joomla errors: `tail -20 /www/html/administrator/logs/everything.php`
  - Container logs: `docker compose -f .devcontainer/compose.yml logs --tail 100 joomla`
- E2E tests (Playwright-based)
  - Install dependencies: `cd e2e && pnpm install`
  - Run tests: `pnpm test` (headless), `pnpm test:headed` (browser visible)
  - Debug tests: `pnpm test:debug` or `pnpm test:ui` (interactive UI mode)
  - View reports: `pnpm report`
  - Tests require services running with HTTPS enabled (note: use pnpm, not npm)

## Code Style Highlights

- Follow PSR-12 with PHP 8.1 migration rules.
- Order imports: `Joomla\CMS` → other Joomla → project namespaces, alphabetically.
- Use fully qualified strict types and native function casing.
- Keep PHPDoc blocks meaningful, aligned, and free of empty tags.
- Naming: classes PascalCase, methods camelCase, files lowercase_underscores.
- Maintain Joomla MVC inheritance patterns.
- Include `defined('_JEXEC') or die;` at PHP entry points.
- Use Joomla exceptions and `Text` for user-facing messages.

## Language Requirements

**All code comments, documentation, and project descriptions MUST be written in English.**

- PHP comments (inline, block, PHPDoc) must be in English.
- Git commit messages must be in English.
- Variable, function, and class names must follow English naming conventions.
- README files and documentation must be in English.
- This ensures consistency and global community understanding across the project.
