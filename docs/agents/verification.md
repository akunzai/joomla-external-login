# Verification

How an agent exercises a change in this repo before it reaches review.
Human setup narrative lives in `README.md` and `.devcontainer/README.md`; this file
holds only what an agent needs.

## Starting the environment

```sh
composer run lint && composer run phpstan && composer test
```

<!-- drift:forge github -->
<!-- drift:entrypoint-cmd composer run lint && composer run phpstan && composer test -->

It never prompts. A step needing a human aborts non-zero naming the
prerequisite — see Human prerequisites below.

**Proof it ran**: PHPUnit exits 0 with all tests passing (`OK (57 tests, 120 assertions)`), PHPStan reports `[OK] No errors`, and PHP CS Fixer reports no fixable files.

Entry point: `https://www.dev.local`. Test account: admin / credentials in `.devcontainer/README.md`.

## Checks

Task runner commands are defined in `composer.json`.

| What | Command |
| --- | --- |
| Gate (lint, static analysis, unit tests) | `composer run lint && composer run phpstan && composer test` |
| Code style check (dry-run) | `composer run lint` |
| Code style fix | `composer run fix` |
| Static analysis | `composer run phpstan` |
| Unit tests (all) | `composer test` |
| Single test | `composer test -- --filter <pattern>` |
| Extension package build | `./bundle.sh` |
| E2E tests (headless) | `cd e2e && aube test` |

## Human prerequisites

Run once, by a person. The start command fails until they are done.

- [ ] Install toolchain via `mise install` (PHP 8.4, Composer, Node LTS, Aube)
- [ ] Run `composer install` in repository root
- [ ] For E2E / dev stack: trust local certificates (`mkcert -install`) and configure `.secrets/` per `.devcontainer/README.md`
- [ ] Add `/etc/hosts` entries: `127.0.0.1 www.dev.local auth.dev.local store.dev.local`

## Ports

This stack is reached by hostname, so ports cannot be offset. **Only one
agent runs the environment at a time**; the lock is `.devcontainer/.lock`.

## Changes that need a deployed environment

These cannot be verified locally. Open the request as a draft, let the
pipeline deploy, then verify against the deployed environment:

- Third-party IdP live integration with external IdPs (e.g. production Azure AD / Okta / Google Workspace endpoints requiring public domain callbacks): local Keycloak covers standard CAS/OIDC flows, but external tenant-specific IdPs require live callbacks.

Evidence from that environment cites the pipeline or deployment id and
the commit SHA, and is treated as containing real data: mask, crop, or
use a dedicated test account.

Agent may deploy to it: **no**.
Credentials come from GitHub Actions repository secrets.

## Capturing evidence

- Recording: `to-walkthrough-video` or `tcut` — fallback: Playwright video recordings in `e2e/test-results/`
- Screenshots: Playwright screenshots or terminal output captures
- UI locale: **`en`**. The extension ships `en-GB` language files only.
  Browser automation defaults to `en-US`, which matches. Captions follow
  English.

**This document is where the capture rules live**, and the request
document points here rather than restating them. A capture taken on the
developer's own machine carries their account's data, username, and home
paths as readily as a shared environment does. Assert on the frame, a
marker, or fixture data, and crop or mask what the tool happened to be
showing.

For a change behind a mode switch or feature flag, confirm the far end
received the call. A healthy container and a green build are not
evidence that an integration is wired up.

## Not verified

- Live external enterprise IdPs (Azure AD, Okta): verified locally against Keycloak mock/realm fixtures; live SaaS IdP end-to-end authentication cannot be executed without external tenant credentials.

A gap you could have closed is not a gap. Run the check whose dependency
you have already seen running, and report a check you skipped as untried,
rather than recording it here as one this repo cannot run.

<!-- drift:file bundle.sh -->
<!-- drift:file composer.json -->
<!-- drift:file .devcontainer/compose.yml -->
