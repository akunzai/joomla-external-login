# E2E Tests — Agent Guidelines

**Context-offloaded SOP** for Playwright E2E. Root index: `AGENTS.md`.

Use **`bun` only** (not npm/pnpm/yarn). Runner command is `bun run --bun test` or `bun test:e2e`.

## Prerequisites

- Compose stack up with HTTPS (`.devcontainer/AGENTS.md`)
- Toolchain: `mise install` (SSOT: `mise.toml`)

## Commands

```sh
cd e2e

bun install                     # install dependencies
bun run --bun test              # headless
bun run test:headed             # headed browser
bun run --bun test -- --grep <pattern>   # single test / filter
bun run test:debug              # debug
bun run test:ui                 # interactive UI
bun run report                  # HTML report
```
