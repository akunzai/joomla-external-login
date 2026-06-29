# Agent Guidelines for E2E Tests

End-to-end tests use [Playwright](https://playwright.dev/).

**IMPORTANT: Always use `aube` (the Aube package manager), NOT `npm`.**

## Prerequisites

- Dev container services must be running with HTTPS enabled
- Development tools installed via mise (`mise install`); see the root [AGENTS.md](../AGENTS.md) "Environment & Tooling" section

## Commands

```sh
cd e2e

aube install              # install dependencies
aubr test                 # run tests (headless)
aubr test:headed          # run tests (browser visible)
aubr test -- --grep <pattern>  # run specific tests
aubr test:debug           # debug tests
aubr test:ui              # interactive UI mode
aubr report               # view HTML report
```
