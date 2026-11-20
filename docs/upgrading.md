# Upgrading

Release-specific steps, newest first. Upload `pkg_externallogin.zip` over the existing package, or use Joomla Update, then check the notes for every version you skipped.

## 5.3 (OIDC subject binding)

OIDC servers can bind accounts to the provider's verified issuer and subject (`iss` + `sub`) instead of the username claim, so a changed username or email at the provider no longer moves a person into another account. See [server binding](server-binding.md#which-account-a-login-reaches).

- **Existing OIDC servers stay in username mode**: the upgrade sets their new **Account binding** option to *Username claim (legacy)*, and they behave as in 5.2.1.
- **New OIDC servers default to subject mode.**
- **Switching an existing server to subject mode** is a trust-on-first-use step: each account already bound to it records its `iss` + `sub` on its next successful login. Until then, whoever presents that account's username claim is trusted once.
- Denied subject-mode logins are always written to the External Login logs.

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
