# Joomla External Login — Agent Guidelines

**Index-driven entrypoint.** Prefer **Progressive Disclosure**: keep this file lean; **Context Offload** multi-step SOPs to nested guides via **Lazy Loading** (`@path`). **Trust model judgment** for generic coding practice; record only project-specific, non-derivable constraints.

## Quick Commands

Toolchain SSOT: @mise.toml — run `mise install` for PHP, Composer, Node, Aube.

```sh
# Stack (full SOP: @.devcontainer/AGENTS.md)
docker compose -f .devcontainer/compose.yml up -d
docker compose -f .devcontainer/compose.yml exec -w /workspace joomla <command>

# Quality / release (script SSOT: @composer.json)
composer install
composer run lint       # php-cs-fixer dry-run
composer run fix
composer run phpstan
./bundle.sh             # → dist/pkg_externallogin.zip
```

**Dev endpoints:** Joomla `https://www.dev.local` · Keycloak `https://auth.dev.local` · Traefik `443` · MySQL `3306`. TLS: `.devcontainer/generate-certs.sh .devcontainer/.secrets`.

## Architecture

| Path | Role |
|------|------|
| `src/` | Extension package: admin (`administrator/`), site (`components/`), plugins (`plugins/{authentication,system,user}/`) |
| `e2e/` | Playwright E2E — @e2e/AGENTS.md |
| `dist/` | Bundled zips (`pkg_externallogin.zip`) |
| `.devcontainer/` | Compose stack, extension install, diagnostics — @.devcontainer/AGENTS.md |

## Code Style (project-specific)

Machine-enforced SSOT: @.php-cs-fixer.dist.php (`@PSR12`, `@PHP83Migration`). Static analysis: @phpstan.neon.

Non-derivable conventions:

- Import order: `Joomla\CMS` → other Joomla → project namespaces (alpha within each group)
- PHP entry points: `defined('_JEXEC') or die;`
- User-facing copy: Joomla `Text`; failures: Joomla exceptions
- Components follow Joomla MVC inheritance
- All source and docs in English

## Context Offloading

| Domain | Lazy-load |
|--------|-----------|
| Dev stack, extension lifecycle, file-copy testing, logs | @.devcontainer/AGENTS.md |
| E2E (`aube` / Playwright) | @e2e/AGENTS.md |

## Knowledge Writeback

When a durable, non-obvious gotcha appears: propose a **context-tagged** bullet on the nearest relevant `AGENTS.md`. **Active Pruning:** keep any `## Lessons Learned` ≤ 5; drop obsolete version tags; promote durable rules into configs or Rich References rather than prose.

## Claude Code Compatibility

> [!NOTE]
> This repository maintains compatibility with Claude Code. The file `CLAUDE.md` is a symbolic link pointing to `AGENTS.md`.
> All commands, style guides, and workflows defined in `AGENTS.md` apply to both Antigravity (and other agentic assistants) and Claude Code.
> **DO NOT** delete the `CLAUDE.md` symbolic link or edit it independently; all guidelines must be updated directly in `AGENTS.md`.
