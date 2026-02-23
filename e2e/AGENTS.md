# Agent Guidelines for E2E Tests

End-to-end tests use [Playwright](https://playwright.dev/).

**IMPORTANT: Always use `pnpm`, NOT `npm`.**

## Prerequisites

- Dev container services must be running with HTTPS enabled
- Node.js and pnpm installed

## Commands

```sh
cd e2e

pnpm install              # install dependencies
pnpm test                 # run tests (headless)
pnpm test:headed          # run tests (browser visible)
pnpm test -- --grep <pattern>  # run specific tests
pnpm test:debug           # debug tests
pnpm test:ui              # interactive UI mode
pnpm report               # view HTML report
```
