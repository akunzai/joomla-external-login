# Dev Containers

## Requirements

- [Podman](https://podman.io/) (recommended) or [Docker Engine](https://docs.docker.com/install/)
- `podman-compose` or [Docker Compose](https://docs.docker.com/compose/cli-command/)
- [mkcert](https://github.com/FiloSottile/mkcert)
- DevContainer-compatible IDE or CLI
- Bash

## Getting Started

```sh
# set up TLS certs in Host
mkdir -p .secrets
mkcert -cert-file .secrets/cert.pem -key-file .secrets/key.pem '*.dev.local'
cp "$(mkcert -CAROOT)/rootCA.pem" .secrets/ca.pem

# set up hosts in Host
echo "127.0.0.1 auth.dev.local www.dev.local" | sudo tee -a /etc/hosts

# starting container (Joomla 6, default)
# (use `docker compose` instead if using Docker)
podman compose up -d

# starting container (Joomla 5)
JOOMLA_VERSION=5.4.5 PHP_VERSION=8.3 podman compose build && podman compose up -d

# starting container for debug
# > attach to running joomla container for Xdebug
podman compose -f compose.yml -f compose.debug.yml up -d

# install or update the Joomla extension
./joomla/install.sh

# force re-install the Joomla extension
./joomla/install.sh --force
```

## URLs

- [Joomla!](https://www.dev.local:8443/administrator/)
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
- Username: `test` or `test2`
- Password: `test` or `test2`

## Setup

- [Joomla!](./joomla/)

## Rootless Container Engines (e.g. Podman)

When using rootless Podman:

- Point Traefik to the Podman socket: `export CONTAINER_SOCKET_PATH="${XDG_RUNTIME_DIR:-/run/user/$(id -u)}/podman/podman.sock"` (or your platform's Podman machine socket path).
- Configure your DevContainer-compatible IDE (e.g., set container engine path to `podman`).
- When running helper scripts: `CONTAINER_COMPOSE="podman compose" ./joomla/install.sh` (or `podman-compose`).

### Using mise for local environment override

You can configure your local container engine settings in `mise.local.toml`:

```toml
[env]
CONTAINER_COMPOSE = "podman compose"
# macOS (Podman machine - dynamic socket resolution):
CONTAINER_SOCKET_PATH = "{{exec(command='podman machine inspect --format \"{{.ConnectionInfo.PodmanSocket.Path}}\"')}}"
# Linux (rootless):
# CONTAINER_SOCKET_PATH = "{{env.XDG_RUNTIME_DIR}}/podman/podman.sock"
```

## Troubleshooting

### [Exporting Keycloak](https://www.keycloak.org/server/importExport)

```sh
podman compose exec keycloak /opt/keycloak/bin/kc.sh export --dir /opt/keycloak/data/export/ --realm demo
```
