# External Login extension for Joomla

[![Build Status][ci-badge]][ci]

[ci]: https://github.com/akunzai/joomla-external-login/actions?query=workflow%3ACI
[ci-badge]: https://github.com/akunzai/joomla-external-login/workflows/CI/badge.svg

The [Joomla!](https://www.joomla.org/) authentication extension allows to login to Joomla using external [CAS](https://github.com/apereo/cas) servers

## Requirements

- [Joomla!](https://www.joomla.org/) 3.x
- [Composer](https://getcomposer.org/)
- PHP >= 7.4

## Build

```sh
# install dependencies
composer install

# build the project. The build artifacts will be stored in the `build/` directory
composer build
```

## History of this extension

- [Christophe Demko](https://github.com/chdemko) continue the [Authentication Manager project](http://joomlacode.org/gf/project/auth_manager/), originally developed for Joomla! 1.5, and make it compatible with Joomla! 3.x.
- [Charley Wu](https://github.com/akunzai) continue the [External Login extension](https://github.com/chdemko/joomla-external-login) and make it compatible with PHP 7.4 and Joomla! 4.0.
