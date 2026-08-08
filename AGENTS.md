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
composer run phpstan    # includes --memory-limit=512M (default 128M OOMs on this tree)
composer test
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

## Self-Reflection

When solving a problem reveals non-obvious knowledge (gotchas, hidden configs, env quirks):
1. **Distill**: Create a concise, context-tagged candidate rule (≤ 2 bullets).
2. **Promote**: Confirm with user, write to topic doc in `docs/<topic>.md` (fallback: `docs/lessons-learned.md`), and add a `@path` reference line under Rich References.
3. **Prune**: Review and propose deleting stale entries when underlying stack/configs change.

## Agent skills

### Issue tracker

GitHub Issues on `akunzai/joomla-external-login` via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five canonical roles, label string = role name. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context — `CONTEXT.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.

## Claude Code Compatibility

> [!NOTE]
> `CLAUDE.md` is a symlink to `AGENTS.md` (shared SSOT). Edit `AGENTS.md` only.
