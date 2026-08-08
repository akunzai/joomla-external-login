# Known limitations

Short index for operators and integrators. The root [README](../README.md) links here; each section holds the detail that would otherwise bloat the project overview.

| Topic | Summary |
| --- | --- |
| [One server per account](#one-server-per-account) | Account binds to first server used; rebind is admin-only |
| [Keycloak role claims](#keycloak-role-claims) | Roles must be on ID Token / UserInfo, not only the access token |
| [OIDC `username_claim` default](#oidc-username_claim-default) | Defaults to `email`, not `preferred_username` |
| [CAS email verification](#cas-email-verification) | Opt-in `email_verified_xpath`; cookbook of XPath shapes |
| [Azure AD `groups`](#azure-ad-groups) | Opaque GUIDs are not mapped; use App Roles |

---

## One server per account

A Joomla account binds to the specific external server (CAS or OIDC) it first authenticated against. Login via a different server is rejected until an administrator rebinds the account.

**Rebind howto (admin UI and SQL):** [server-binding.md](server-binding.md)  
**Design decision:** [ADR 0001](adr/0001-one-account-binds-one-external-login-server.md)

---

## Keycloak role claims

The OIDC plugin only reads claims from the **ID Token** and the **UserInfo** response (never the access token).

Out of the box, Keycloak’s built-in `roles` client scope adds `realm_access` / `resource_access` to the **access token only**. For group mapping (`groups_claim`, e.g. `realm_access.roles`) to work:

1. Client Scopes → `roles` → Mappers  
2. On **realm roles** / **client roles**, enable **Add to userinfo token** and/or **Add to ID token**

This toggles Keycloak’s own mapper; it is not a custom mapper. The devcontainer demo realm already has this enabled.

See [Keycloak: Role mappings in the token](https://www.keycloak.org/docs/latest/server_admin/index.html#_oidc_token_role_mappings).

---

## OIDC `username_claim` default

`username_claim` defaults to **`email`**, not the OIDC-standard `preferred_username`. That matches the CAS plugin’s default (`username_xpath` → email) so the same person at the same IdP resolves to the **same Joomla username** on either protocol — which lets the [one-server binding check](server-binding.md) deny cross-server reuse cleanly instead of creating a second account.

Trade-offs:

- Not every provider includes or verifies `email` by default (some gate it behind an `email` scope / `email_verified`).
- Self-asserted, unverified emails can be abused to claim another account’s username.
- Email is mutable at the IdP; changing it changes which Joomla account the user resolves to.

Set `username_claim` to `preferred_username` (or another stable claim) if those trade-offs do not fit your deployment.

When **username is the email claim** (the default), login requires `email_verified === true` unless the server option **Allow unverified email** is enabled. Explicit `email_verified: false` is always denied. Providers that omit the claim must send `true`, use a non-email `username_claim`, or opt into allow-unverified.

---

## CAS email verification

CAS has no standard equivalent of OIDC’s `email_verified` claim. Whatever appears under `<cas:attributes>` is provider-specific.

Use the optional **Email verified xpath** field (empty by default = no check). The expression must evaluate to a boolean; login is denied only on an explicit `false` (fail-open for missing attributes or misconfiguration).

Wrap conditions as:

```text
boolean(not(<attribute>) or <condition>)
```

so a response that omits the attribute is not treated as false (XPath empty node-set comparisons are always false).

### Cookbook

| Provider shape | XPath |
| --- | --- |
| Boolean-ish attribute (e.g. `emailVerified=true`; devcontainer demo in `.devcontainer/keycloak/import/demo-realm.json`) | `boolean(not(cas:attributes/cas:emailVerified) or cas:attributes/cas:emailVerified = 'true')` |
| Single sentinel text (e.g. `status=confirm`) | `boolean(not(cas:attributes/cas:status) or cas:attributes/cas:status = 'confirm')` |
| Multiple acceptable text values | `boolean(not(cas:attributes/cas:status) or contains('\|confirm\|verified\|yes\|', concat('\|', cas:attributes/cas:status, '\|')))` |
| Multi-valued list of verified addresses | `boolean(not(cas:attributes/cas:verified_email) or cas:attributes/cas:verified_email = cas:attributes/cas:email)` (node-set `=` node-set is existential in XPath 1.0) |

Demo manual field values: [`.devcontainer/joomla/README.md`](../.devcontainer/joomla/README.md).

---

## Azure AD groups

The OIDC `groups_claim` resolver maps a flat array of role/group **names** (or Joomla group IDs). That works with Keycloak-style `realm_access.roles` / `resource_access.<clientId>.roles`.

Azure AD / Microsoft Entra ID’s `groups` claim is an array of **opaque Security Group GUIDs** with no display name, so it cannot be resolved to Joomla group titles and is deliberately left unmapped.

Use [Azure AD App Roles](https://learn.microsoft.com/en-us/entra/identity-platform/howto-add-app-roles-in-apps) (named, not GUID-only) and map via a claim such as `roles`.
