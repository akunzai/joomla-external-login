# Joomla External Login Developer Guidelines

Joomla 5/6 extension package providing external authentication (CAS, OIDC) and user synchronization.

Toolchain SSOT: @mise.toml — run `mise install` for PHP, Composer, Node, Aube.

## Commands

```sh
# Stack & lifecycle (SOP: @.devcontainer/AGENTS.md)
docker compose -f .devcontainer/compose.yml up -d
docker compose -f .devcontainer/compose.yml exec -w /workspace joomla <command>

# Quality & packaging (SSOT: @composer.json)
composer install
composer run lint       # php-cs-fixer dry-run
composer run fix
composer run phpstan    # includes --memory-limit=512M (default 128M OOMs on this tree)
composer test
./bundle.sh             # → dist/pkg_externallogin.zip
```

**Dev endpoints:** Joomla `https://www.dev.local` · Keycloak `https://auth.dev.local` · Traefik `443` · MySQL `3306`.

## Pointers

- Dev stack & extension lifecycle: @.devcontainer/AGENTS.md
- E2E tests (Playwright / `aube`): @e2e/AGENTS.md
- Domain model & ADRs: @docs/agents/domain.md
- Issue tracker SOP: @docs/agents/issue-tracker.md
- Triage labels: @docs/agents/triage-labels.md
- Code style rules: @.php-cs-fixer.dist.php
- Static analysis: @phpstan.neon
- Known limitations: @docs/known-limitations.md

## Code Style

Non-derivable conventions:

- Import order: `Joomla\CMS` → other Joomla → project namespaces (alphabetical within groups)
- PHP entry points: `defined('_JEXEC') or die;`
- User-facing copy: Joomla `Text`; failures: Joomla exceptions

## Self-Reflection

- **Candidate**: Distill a non-obvious gotcha into ≤ 2 context-tagged bullets. Propose it before writing.
- **Promote**: On confirmation, put it where whoever would break it must already pass — enforce it (assert/type/test) when the fix is in hand, else a comment at that site, else an agent-facing doc (`docs/agents/<topic>.md`, else `docs/agents/lessons-learned.md`) with one `@path` line under Pointers. Never both.
- **Prune**: Drop entries once stale (obsolete version, now enforced, duplicated, or a transcript) — not by a fixed count.

## Claude Code Compatibility

`CLAUDE.md` is a symbolic link pointing to `AGENTS.md`. Edit `AGENTS.md` directly.
