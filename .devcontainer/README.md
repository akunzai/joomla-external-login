# Dev Containers

## Requirements

- [Docker Engine](https://docs.docker.com/install/)
- [Docker Compose V2](https://docs.docker.com/compose/cli-command/)
- [mkcert](https://github.com/FiloSottile/mkcert)
- [Visual Studio Code](https://code.visualstudio.com/)
- Bash

## Getting Start

```sh
# set up TLS certs and hosts in Host
./init.sh www.dev.local auth.dev.local

# start containers
docker compose up -d

# install the Joomla! (requires Joomla! version >= 4.3)
./joomla/install.sh
```

## Admin URLs

- [Joomla!](https://www.dev.local/administrator/)
- [Keycloak](https://auth.dev.local)

## Credentials

### Joomla! admin

- Username: `admin`
- Password: `ChangeTheP@ssw0rd`

### Keycloak admin

- Username: `admin`
- Password: `admin`

### Keycloak user

- Username: `test`
- Password: `test`

## Setup

- [Joomla!](./joomla/)
