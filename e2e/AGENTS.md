# E2E Tests — Agent Guidelines

**Context-offloaded SOP** for Playwright E2E. Root index: `AGENTS.md`.

Use **`aube` only** (not npm/pnpm/yarn). Runner command is `aube run` or `aube test`.

## Prerequisites

- Compose stack up with HTTPS (`.devcontainer/AGENTS.md`)
- Toolchain: `mise install` (SSOT: `mise.toml`)

## Commands

```sh
cd e2e

aube install                    # install dependencies
aube test                       # headless
aube run test:headed            # headed browser
aube test -- --grep <pattern>   # single test / filter
aube run test:debug             # debug
aube run test:ui                # interactive UI
aube run report                 # HTML report
```
