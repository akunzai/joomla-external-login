# Joomla External Login Developer Guidelines

Joomla 5/6 extension package providing external authentication (CAS, OIDC) and user synchronization.

Toolchain SSOT: @mise.toml — run `mise install` for PHP, Composer, Node, Aube.

## Pointers

- Verification: @docs/agents/verification.md
- Dev stack & extension lifecycle: @.devcontainer/AGENTS.md
- E2E tests (Playwright / `aube`): @e2e/AGENTS.md
- Domain model & ADRs: @docs/agents/domain.md
- Issue tracker: @docs/agents/issue-tracker.md
- Pull requests: @docs/agents/pull-request.md
- Triage labels: @docs/agents/triage-labels.md
- Code style rules: @.php-cs-fixer.dist.php
- Static analysis: @phpstan.neon
- Known limitations: @docs/known-limitations.md

## Code Style

Non-derivable conventions:

- Import order: `Joomla\CMS` → other Joomla → project namespaces (alphabetical within groups)
- PHP entry points: `defined('_JEXEC') or die;`
- User-facing copy: Joomla `Text`; failures: Joomla exceptions

## Prevent Recurrence

- **Candidate**: Name who hits this again, in which file, on what change. No such scenario, nothing to propose.
- **Promote**: Offer the first tier that reaches them and only that one, pending confirmation — enforce it (assert/type/test) with its size quoted, else a comment at that site, else an agent-facing doc (`docs/agents/<topic>.md`, else `docs/agents/lessons-learned.md`) with one `@path` line under Pointers and one sentence on why the tiers above cannot hold it.
- **Prune**: When adding to a file, audit the rest of it in the same pass. Drop entries once stale (obsolete version, now enforced, duplicated, or a transcript) — not by a fixed count.

## Claude Code Compatibility

`CLAUDE.md` is a symbolic link pointing to `AGENTS.md`. Edit `AGENTS.md` directly.
