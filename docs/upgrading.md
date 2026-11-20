# Upgrading

Release-specific steps, newest first. Upload `pkg_externallogin.zip` over the existing package, or use Joomla Update, then check the notes for every version you skipped.

## 5.2.1 (OIDC hardening)

OIDC servers that worked on 5.2.0 can be rejected after upgrading if their configuration relied on what 5.2.1 no longer accepts. The reason is always written to the External Login logs (`system-oidclogin-discovery`).

- The **Issuer URL** must use `https`, and the discovery document's `issuer` must equal it.
- Discovered endpoints must use `https` and live on the issuer's host. List any other hosts in **Trusted endpoint hosts** ([details](known-limitations.md#oidc-endpoint-hosts)).
- The **Client secret** is no longer shown in the server form; leave it blank to keep the stored value.

## 5.2

1. Upload `pkg_externallogin.zip` over the existing package (or use Joomla Update when available).
2. **Enable** the new plugin **System - OIDC Login** if you will use OpenID Connect (`System → Plugins`).
3. **PHP ≥ 8.3** is required (8.1/8.2 are no longer supported).
4. **OIDC only:** when `username_claim` is `email` (the default), the IdP must send `email_verified=true`, or enable **Allow unverified email** on the server. CAS behaviour is unchanged unless you set `email_verified_xpath`.
5. See **[known limitations](known-limitations.md)** for claim mapping (Keycloak roles, Azure AD groups) and related operator notes.
