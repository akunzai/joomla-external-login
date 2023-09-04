# External Login extension for Joomla!

[![Build Status][ci-badge]][ci]

[ci]: https://github.com/akunzai/joomla-external-login/actions?query=workflow%3ACI
[ci-badge]: https://github.com/akunzai/joomla-external-login/workflows/CI/badge.svg

The [Joomla!](https://www.joomla.org/) authentication extension allows to login to Joomla using external [CAS](https://github.com/apereo/cas) servers

## Requirements

- PHP >= 8.1
- [Composer](https://getcomposer.org/)
- [Joomla!](https://www.joomla.org/) 3.10.x or 4.x

## Build

```sh
# install dependencies
composer install

# build the project. The build artifacts will be stored in the `build/` directory
composer build
```

## Set up development containers

> [mkcert](https://github.com/FiloSottile/mkcert) needs to be installed

```sh
# append /etc/hosts
echo '127.0.0.1 cas.dev.local www.dev.local' | sudo tee -a /etc/hosts

# install the local CA in the system trust store
mkcert -install

# generate locally-trusted development certificates
mkcert -cert-file .devcontainer/cert.pem -key-file .devcontainer/key.pem '*.dev.local'
```

## History of this extension

- [Christophe Demko](https://github.com/chdemko) continue the [Authentication Manager project](http://joomlacode.org/gf/project/auth_manager/), originally developed for Joomla! 1.5, and make it compatible with Joomla! 3.x.
- [Charley Wu](https://github.com/akunzai) continue the [External Login extension](https://github.com/chdemko/joomla-external-login) and make it compatible with PHP 8.1 and Joomla! 4.x.
