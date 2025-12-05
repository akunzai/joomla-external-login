#!/usr/bin/env bash
# Generate self-signed CA and server certificates for local development
# Usage: ./generate-certs.sh [SECRETS_DIR]
# Example: ./generate-certs.sh .secrets

set -e

SECRETS_DIR="${1:-.secrets}"
DOMAINS="www.dev.local,auth.dev.local"

echo "=================================================="
echo "Certificate Generation Script"
echo "=================================================="
echo "Output directory: ${SECRETS_DIR}"
echo "Domains: ${DOMAINS}"
echo ""

# Create secrets directory if not exists
mkdir -p "${SECRETS_DIR}"

# Check if certificates already exist
if [[ -f "${SECRETS_DIR}/ca.pem" && -f "${SECRETS_DIR}/cert.pem" && -f "${SECRETS_DIR}/key.pem" ]]; then
    echo "Certificates already exist in ${SECRETS_DIR}"
    echo "To regenerate, remove existing certificates first."
    exit 0
fi

echo "Step 1: Generating CA certificate..."
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "${SECRETS_DIR}/ca-key.pem" \
    -out "${SECRETS_DIR}/ca.pem" \
    -subj "/CN=Local Dev CA"
echo "✓ CA certificate created"

echo ""
echo "Step 2: Generating server certificate..."
openssl req -nodes -newkey rsa:2048 \
    -keyout "${SECRETS_DIR}/key.pem" \
    -out "${SECRETS_DIR}/cert.csr" \
    -subj "/CN=dev.local"

openssl x509 -req -days 365 \
    -in "${SECRETS_DIR}/cert.csr" \
    -CA "${SECRETS_DIR}/ca.pem" \
    -CAkey "${SECRETS_DIR}/ca-key.pem" \
    -CAcreateserial \
    -out "${SECRETS_DIR}/cert.pem" \
    -extfile <(echo "subjectAltName=DNS:${DOMAINS//,/,DNS:}")

# Clean up temporary files
rm -f "${SECRETS_DIR}/cert.csr" "${SECRETS_DIR}/ca.srl"
echo "✓ Server certificate created"

echo ""
echo "=================================================="
echo "Certificate Generation Completed!"
echo "=================================================="
echo "Files created:"
echo "  - ${SECRETS_DIR}/ca.pem      (CA certificate)"
echo "  - ${SECRETS_DIR}/ca-key.pem  (CA private key)"
echo "  - ${SECRETS_DIR}/cert.pem    (Server certificate)"
echo "  - ${SECRETS_DIR}/key.pem     (Server private key)"
echo ""
