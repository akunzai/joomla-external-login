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

- **CAS: one server per account.** The CAS plugin binds a Joomla account to the specific CAS server it first authenticated against. If that account later tries to log in through a *different* CAS server, the plugin rejects the login rather than silently accepting it. This matters if you configure more than one CAS server (or a CAS server alongside an OIDC server) and expect the same username to log in through either one — it won't, unless the account is registered for that server. There is currently no admin UI to add or change this binding manually; a Joomla administrator can edit it directly via `Components -> External Login -> Users`.

## History of this extension

- [Christophe Demko](https://github.com/chdemko) continue the [Authentication Manager project](http://joomlacode.org/gf/project/auth_manager/), originally developed for Joomla! 1.5, and make it compatible with Joomla! 3.x
- [Charley Wu](https://github.com/akunzai) continue the [External Login extension](https://github.com/chdemko/joomla-external-login) and make it compatible with PHP 8.1 and Joomla! 4.x
