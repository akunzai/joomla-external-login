# Joomla! Setup

Fresh devcontainer stacks are usually seeded by [`install.sh`](../README.md) (extension install, plugins, Keycloak CAS/OIDC servers, site module). The steps below are the **manual** recipe for the same demo layout — useful when configuring a non-seeded host, or when re-creating a server after deleting the DB row.

## Extension Installation

> System->Install->Extensions

Upload Package File: `pkg_externallogin.zip`

## Enable Extensions

> System->Manage->Extensions

- Plugin: `Authentication - External Login`
- Plugin: `System - External Login`
- Plugin: `System - CAS Login`
- Plugin: `System - OIDC Login`

## Add CAS Server definition

> Components->`External Login`->Servers->New->CAS

### CAS server details

- Title: `Keycloak CAS`
- Auto-register: `Yes`
- Auto-update: `Yes`
- Automatic logout: `Yes` (optional; ends the Keycloak CAS session on Joomla logout)

### CAS parameters

- Use SSL: `Yes`
- URL: `auth.dev.local`
- Path: `realms/demo/protocol/cas`
- Use CAS 3.0 URL: `Yes`
- Port: `443`

### CAS attributes

- Username xpath: `string(cas:attributes/cas:email)`
- Full name xpath: `string(cas:attributes/cas:display_name)`
- Email xpath: `string(cas:attributes/cas:email)`
- Email verified xpath (optional):

  ```text
  boolean(not(cas:attributes/cas:emailVerified) or cas:attributes/cas:emailVerified = 'true')
  ```

  Requires the IdP to release `emailVerified` as a CAS attribute (demo Keycloak does). Login is denied only on an explicit `false`. Leave empty to skip the check.

## Add OIDC Server definition

> Components->`External Login`->Servers->New->OIDC

Demo IdP client (Keycloak realm `demo`): confidential client `joomla-oidc` / secret `dev-oidc-secret` — see `.devcontainer/keycloak/import/demo-realm.json`. Authorize/token/JWKS URLs are discovered from the Issuer (`…/.well-known/openid-configuration`); do not enter them by hand.

### OIDC server details

- Title: `Keycloak OIDC`
- Auto-register: `Yes`
- Auto-update: `Yes`

### OIDC parameters

- Issuer URL: `https://auth.dev.local/realms/demo`

### Client

- Client ID: `joomla-oidc`
- Client secret: `dev-oidc-secret`

### OIDC attributes

- Username claim: `email` (matches CAS’s email-based username so the same IdP person maps to the same Joomla username on either protocol)
- Full name claim: `name`
- Email claim: `email`
- Groups claim (optional): `realm_access.roles`

  The plugin only reads ID Token + UserInfo (never the access token). On stock Keycloak, enable **Add to userinfo token** and/or **Add to ID token** on Client Scopes → `roles` → **realm roles** / **client roles** mappers; the demo realm already has that toggle set.

### Connection

- Automatic logout: `Yes` (RP-Initiated Logout)
- Post-logout redirect: `https://www.dev.local/` (optional; browser returns here after IdP logout)

OIDC also refuses login when the IdP explicitly sends `email_verified: false` (hardcoded claim check, not a form field).

## Edit Module

> Content->`Site Modules`->`External login`

### Module

- Servers: `Keycloak CAS` and `Keycloak OIDC`
- Position: `sidebar-right`
- Status: `Published`

### Menu Assignment

- Menu Assignment: `On all pages`

### Advanced

- Layout: `default`
- Show logout: `Yes`

## Demo users

| Keycloak (realm `demo`) | Typical use                                                                         |
| ----------------------- | ----------------------------------------------------------------------------------- |
| `test` / `test`         | CAS e2e and manual SSO                                                              |
| `test2` / `test2`       | OIDC e2e (separate account so CAS/OIDC do not share a one-server binding)           |

A Joomla account binds to the first external server it authenticated against; the same person cannot freely switch between CAS and OIDC without admin rebinding.
