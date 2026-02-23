# Agent Guidelines for Joomla External Login

## Documentation Principles

1. **This file records only essential knowledge.** Advanced or complex topics should be split into subdirectory `AGENTS.md` files or relevant `README.md` files by domain.
2. **Use subdirectory `AGENTS.md` to organize domain-specific knowledge.** For example, `e2e/AGENTS.md` for testing, `.devcontainer/AGENTS.md` for dev environment.
3. **Record valuable knowledge back to related files** when solving problems or discovering useful patterns.
4. **Do not redundantly modify `CLAUDE.md`.** It is a symlink to this file.
5. **All files and code must be written in English.**

## Environment Quick Facts

- Use the VS Code dev container; see [.devcontainer/AGENTS.md](.devcontainer/AGENTS.md) for detailed commands.
- Services expose Traefik `443` and MySQL `3306`.
- TLS certificates: run `.devcontainer/generate-certs.sh .devcontainer/.secrets`.
- Access URLs: Joomla at `https://www.dev.local`, Keycloak at `https://auth.dev.local`.

## Essential Commands

```sh
# Start / stop stack
docker compose -f .devcontainer/compose.yml up -d
docker compose -f .devcontainer/compose.yml down

# Work inside the container
docker compose -f .devcontainer/compose.yml exec -w /workspace joomla <command>

# Common tasks (inside container)
composer install          # install dependencies
composer run lint         # check code style (dry-run)
composer run fix          # auto-fix code style
composer run phpstan      # static analysis
./bundle.sh              # bundle release
```

## Code Style Highlights

- Follow PSR-12 with PHP 8.1 migration rules.
- Import order: `Joomla\CMS` → other Joomla → project namespaces, alphabetically.
- Use fully qualified strict types and native function casing.
- Naming: classes PascalCase, methods camelCase, files lowercase_underscores.
- Maintain Joomla MVC inheritance patterns.
- Include `defined('_JEXEC') or die;` at PHP entry points.
- Use Joomla exceptions and `Text` for user-facing messages.

## Subdirectory Guides

- [.devcontainer/AGENTS.md](.devcontainer/AGENTS.md) — Dev container, extension management, diagnostics
- [e2e/AGENTS.md](e2e/AGENTS.md) — E2E testing with Playwright (`pnpm` required)
