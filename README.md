# External Login extension for Joomla

[![Build Status][build-badge]][build]

[build]: https://github.com/akunzai/joomla-external-login/actions/workflows/build.yml
[build-badge]: https://github.com/akunzai/joomla-external-login/actions/workflows/build.yml/badge.svg

The [Joomla!](https://www.joomla.org/) authentication extension allows to login to Joomla using external servers

## Supported authentication standards

- [CAS](https://apereo.github.io/cas/development/protocol/CAS-Protocol-Specification.html) 3.0
- [OpenID Connect](https://openid.net/specs/openid-connect-core-1_0.html) (Authorization Code Flow with PKCE)

## Requirements

- PHP >= 8.3
- [Composer](https://getcomposer.org/)
- [Joomla!](https://www.joomla.org/) 5.x or 6.x

## Getting Started

```sh
# install dependencies
composer install

# check coding style
composer run lint

# static code analysis
composer run phpstan

# bundle the Joomla! extension. The `pkg_externallogin.zip` can be found in the `dist/` directory
./bundle.sh
```

## Installation

> see [joomla setup document](./.devcontainer/joomla/) for details

Navigate to `System->Install->Extensions` in Joomla! backend and upload the package file `pkg_externallogin.zip` to install

> You can get notified once a new version is released and update this extension through Joomla! admin UI

## Known limitations

- **One server per account.** A Joomla account binds to the specific server (CAS or OIDC) it first authenticated against, enforced uniformly for every protocol by the shared `authentication/externallogin` bridge plugin. If that account later tries to log in through a *different* server — including a different server of the same protocol — the login is rejected rather than silently accepted or merged. This matters if you configure more than one server (e.g. two CAS servers, or a CAS server alongside an OIDC server) and expect the same username to log in through either one — it won't, unless the account is registered for that server. There is currently no admin UI to add or change this binding manually; a Joomla administrator can edit it directly via `Components -> External Login -> Users`. See [ADR 0001](docs/adr/0001-one-account-binds-one-external-login-server.md) for the rationale.
- **Keycloak's default `realm_access.roles`/`resource_access.<clientId>.roles` claims need one mapper toggle.** The OIDC plugin only reads claims from the ID Token and the UserInfo response (never the access token). Out of the box, Keycloak's built-in `roles` client scope adds `realm_access`/`resource_access` to the **access token only** — not the ID Token or UserInfo. To make group mapping work, enable **"Add to userinfo token"** (and/or "Add to ID token") on the realm's existing **"realm roles"** / **"client roles"** protocol mappers (Client Scopes → `roles` → Mappers) — this flips an option on Keycloak's own built-in mapper, not a new custom one. See [Keycloak's Role mappings in the token](https://www.keycloak.org/docs/latest/server_admin/index.html#_oidc_token_role_mappings) docs.
- **OIDC's `username_claim` defaults to `email`, not the OIDC-standard `preferred_username`.** This matches the CAS plugin's own default (`username_xpath` resolves the identity's email), so if you run both a CAS server and an OIDC server against the same identity provider, the same person resolves to the same Joomla username on either — letting the "one server per account" binding check above deny a cross-server login attempt cleanly instead of silently creating a second, disconnected Joomla account. Trade-offs to weigh before keeping this default: not every provider includes an `email` claim by default (some gate it behind an explicit `email` scope) or verifies it (`email_verified`) — an IdP that allows self-asserted, unverified email addresses could let a user claim a Joomla username that isn't really theirs; and because email is a mutable IdP attribute, a user changing their email at the IdP changes which Joomla account they resolve to on next login. Set `username_claim` to `preferred_username` (or another stable, provider-specific claim) if these trade-offs don't fit your deployment.
- **Azure AD `groups` claim isn't group-mapped.** The OIDC plugin's `groups_claim` dot-path resolver maps a flat array of role/group *names* (or Joomla group IDs) to Joomla user groups — it works out of the box with Keycloak's default `realm_access.roles`/`resource_access.<clientId>.roles` claim shape. Azure AD / Microsoft Entra ID's `groups` claim, by contrast, is an array of opaque Security Group GUIDs with no display name, so it cannot be resolved to a matching Joomla group title and is deliberately left unmapped rather than mismapped. Use [Azure AD App Roles](https://learn.microsoft.com/en-us/entra/identity-platform/howto-add-app-roles-in-apps) (which are named, not GUID-only) instead, mapped via a claim such as `roles`.

## History of this extension

- [Christophe Demko](https://github.com/chdemko) continue the [Authentication Manager project](http://joomlacode.org/gf/project/auth_manager/), originally developed for Joomla! 1.5, and make it compatible with Joomla! 3.x
- [Charley Wu](https://github.com/akunzai) continue the [External Login extension](https://github.com/chdemko/joomla-external-login) and make it compatible with PHP 8.1 and Joomla! 4.x
