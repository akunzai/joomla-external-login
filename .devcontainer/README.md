# Dev Containers

## Requirements

- [Docker Engine](https://docs.docker.com/install/)
- [Docker Compose V2](https://docs.docker.com/compose/cli-command/)
- [mkcert](https://github.com/FiloSottile/mkcert)
- [Visual Studio Code](https://code.visualstudio.com/)
- Bash

## Getting Start

```sh
# set up TLS certs in Host
mkdir -p .secrets
mkcert -cert-file .secrets/cert.pem -key-file .secrets/key.pem 'auth.dev.local'

# set up hosts in Host
echo "127.0.0.1 auth.dev.local www.dev.local" | sudo tee -a /etc/hosts

# starting container or open folder in container
docker compose up -d

# install the Joomla! (requires Joomla! version >= 4.3)
./joomla/install.sh
```

## URLs

- [Joomla!](http://www.dev.local/administrator/)
- [Keycloak](https://auth.dev.local:8443)

## Credentials

### Joomla! admin

- Username: `admin`
- Password: `ChangeTheP@ssw0rd`

### Keycloak admin

- realm: `master`
- Username: `admin`
- Password: `admin`

### Keycloak user

- realm: `demo`
- Username: `test`
- Password: `test`

## Setup

- [Joomla!](./joomla/)

## Troubleshooting

### [Exporting Keycloak](https://www.keycloak.org/server/importExport)

```sh
docker compose exec keycloak /opt/keycloak/bin/kc.sh export --dir /opt/keycloak/data/export/ --realm demo
```
