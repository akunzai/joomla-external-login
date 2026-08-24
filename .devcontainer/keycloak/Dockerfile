# https://github.com/keycloak/keycloak/releases
ARG KEYCLOAK_VERSION=26.7.2
# https://github.com/jacekkow/keycloak-protocol-cas/releases
ARG KEYCLOAK_CAS_VERSION=26.7.2
FROM quay.io/keycloak/keycloak:${KEYCLOAK_VERSION}
ARG KEYCLOAK_VERSION
ARG KEYCLOAK_CAS_VERSION

# CAS provider + kc.sh build first (expensive). Keep healthcheck binary after so
# microcheck image updates do not invalidate the Keycloak build layer.
# https://github.com/jacekkow/keycloak-protocol-cas
ADD --chown=keycloak:keycloak https://github.com/jacekkow/keycloak-protocol-cas/releases/download/${KEYCLOAK_CAS_VERSION}/keycloak-protocol-cas-${KEYCLOAK_CAS_VERSION}.jar /opt/keycloak/providers/keycloak-protocol-cas.jar

WORKDIR /opt/keycloak

RUN /opt/keycloak/bin/kc.sh build --health-enabled=true

# https://github.com/tarampampam/microcheck
COPY --from=ghcr.io/tarampampam/microcheck:1 /bin/httpscheck /bin/httpscheck
