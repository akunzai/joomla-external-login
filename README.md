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

Full write-ups: **[docs/known-limitations.md](docs/known-limitations.md)**. Rebinding a user to another server: **[docs/server-binding.md](docs/server-binding.md)**.

- **One server per account** — login via a different CAS/OIDC server is rejected until an admin rebinds the account ([howto](docs/server-binding.md), [ADR 0001](docs/adr/0001-one-account-binds-one-external-login-server.md)).
- **Keycloak role claims** — enable “Add to userinfo/ID token” on the built-in roles mappers; the plugin never reads the access token.
- **OIDC `username_claim` defaults to `email`** — matches CAS’s email-based username; trade-offs and alternatives in the doc.
- **CAS email verification is opt-in** — optional `email_verified_xpath` (XPath cookbook in the doc); OIDC uses the standard `email_verified` claim.
- **Azure AD `groups` are not mapped** — opaque GUIDs; use named App Roles instead.

## History of this extension

- [Christophe Demko](https://github.com/chdemko) continue the [Authentication Manager project](http://joomlacode.org/gf/project/auth_manager/), originally developed for Joomla! 1.5, and make it compatible with Joomla! 3.x
- [Charley Wu](https://github.com/akunzai) continue the [External Login extension](https://github.com/chdemko/joomla-external-login) and make it compatible with PHP 8.1 and Joomla! 4.x
