# E2E Tests — Agent Guidelines

**Context-offloaded SOP** for Playwright E2E. Root index: @AGENTS.md.

Use **`aube` only** (not npm/pnpm/yarn). Runner binary is `aubr`.

## Prerequisites

- Compose stack up with HTTPS (@.devcontainer/AGENTS.md)
- Toolchain: `mise install` (SSOT: @mise.toml)

## Commands

```sh
cd e2e

aube install                    # install dependencies
aubr test                       # headless
aubr test:headed                # headed browser
aubr test -- --grep <pattern>   # single test / filter
aubr test:debug                 # debug
aubr test:ui                    # interactive UI
aubr report                     # HTML report
```
