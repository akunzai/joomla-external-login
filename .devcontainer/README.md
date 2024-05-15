# Dev Containers

## Requirements

- [Docker Engine](https://docs.docker.com/install/)
- [Docker Compose V2](https://docs.docker.com/compose/cli-command/)
- [Visual Studio Code](https://code.visualstudio.com/)
- Bash

## Getting Start

```sh
# set up hosts in Host
echo "127.0.0.1 auth.dev.local www.dev.local" | sudo tee -a /etc/hosts

# starting container or open folder in container
docker compose up -d

# install the Joomla! (requires Joomla! version >= 4.3)
./joomla/install.sh
```

## URLs

- [Joomla!](http://www.dev.local/administrator/)
- [Keycloak](http://auth.dev.local:8080)

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

## Troubleshooting

### [Exporting Keycloak](https://www.keycloak.org/server/importExport)

```sh
docker compose exec keycloak /opt/keycloak/bin/kc.sh export --dir /opt/keycloak/data/export/ --realm demo
```
