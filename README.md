# External Login extension for Joomla!

[![Build Status][build-badge]][build]

[build]: https://github.com/akunzai/joomla-external-login/actions/workflows/build.yml
[build-badge]: https://github.com/akunzai/joomla-external-login/actions/workflows/build.yml/badge.svg

The [Joomla!](https://www.joomla.org/) authentication extension allows to login to Joomla using external servers

## Supported authenticaion standards

- [CAS](https://apereo.github.io/cas/7.0.x/protocol/CAS-Protocol-Specification.html) 3.0

## Requirements

- PHP >= 7.4
- [Composer](https://getcomposer.org/)
- [Joomla!](https://www.joomla.org/) 3.x

## Build

```sh
# install dependencies
composer install

# check coding style
composer run lint

# static code analysis
composer run phpstan

# build the Joomla! extension. The `pkg_externallogin.zip` can be found in the `dist/` directory
./build.sh
```

## History of this extension

- [Christophe Demko](https://github.com/chdemko) continue the [Authentication Manager project](http://joomlacode.org/gf/project/auth_manager/), originally developed for Joomla! 1.5, and make it compatible with Joomla! 3.x.
- [Charley Wu](https://github.com/akunzai) continue the [External Login extension](https://github.com/chdemko/joomla-external-login) and make it compatible with PHP 7.4 and Joomla! 4.0.
