# 0002. OIDC Accounts Bind to the Provider's Issuer and Subject

## Status

Accepted (issue #304). Supplements, and does not supersede, [ADR 0001](0001-one-account-binds-one-external-login-server.md).

## Context

An OIDC login reaches its Joomla account through the username resolved from the server's `username_claim`, `email` by default. That value can change at the identity provider, and some providers let users edit it themselves. When it changes, the person either lands in a different Joomla account, the one that now has that username, or loses access to their own.

OIDC already gives every identity a stable identifier: the subject (`sub`), which is unique and never reassigned within its issuer (`iss`). Since 5.2.1 both claims are mandatory and verified in the ID token, the UserInfo `sub` must equal the ID token's, and the ID token's values win when the two claim sets are merged.

ADR 0001 recorded "no schema or bridge-plugin change" for the binding table. This decision changes both, so it is recorded separately.

## Decision

- An OIDC server has a **binding mode**: `subject` or `username`.
  - **Subject mode**: the verified `iss` + `sub` choose the account, and the username claim only names it.
  - **Username mode**: the 5.2.1 behaviour, unchanged.
- `#__externallogin_users` gains nullable `issuer` and `subject` columns, each `varchar(255)` with a case-sensitive (`utf8mb4_bin`) collation, and `UNIQUE (server_id, issuer, subject)`. `UNIQUE (user_id)` stays, so one account still binds to exactly one server (ADR 0001).
- `ExternalAuthenticationResponse` carries optional `issuer` and `subject`. Only the OIDC plugin sets them, so CAS is untouched.
- **Defaults.** New OIDC servers default to subject mode. The 5.3.0 schema update writes `binding_mode = username` into every existing OIDC server's params, and code reads a missing value as username mode. A form default alone is not enough: Joomla shows a field's default when the stored params lack the key, so an administrator saving an old server would switch it unknowingly.
- **Matching in subject mode** (`SubjectBinding::resolve()`):

  | Situation | Result |
  | --- | --- |
  | An account on this server stores this `iss` + `sub` | Log in to it, whatever the username claim says |
  | The account named by the username claim is bound to this server with no stored subject | Record `iss` + `sub` on it, then log in (trust on first use) |
  | That account is bound to this server with a different subject or issuer | Deny and log |
  | No such account, or it is unbound or bound to another server | The existing rules: auto-register (recording `iss` + `sub`), or deny |

- **Claim drift.** Once an account is bound by subject, a changed username or email claim still reaches it. With autoupdate on, its username and email follow the claims; if another account already uses them, the login is denied and logged instead of silently keeping the old values. With autoupdate off, the account keeps its username.
- **Issuer change.** A new issuer (a new discovery URL or tenant) no longer matches the stored one and is denied, not rebound. An administrator rebind clears the stored issuer and subject, so the next login records the new pair.
- **Switching back to username mode** keeps the stored pairs but ignores them, so switching to subject mode again reuses them.
- **Over-long values.** An `iss` or `sub` longer than 255 characters is denied and logged in subject mode, never truncated, since a truncated identifier could match a different identity.

## Alternatives Considered

**1. Separate table for subject bindings.** Rejected: it would duplicate the one-account-one-server rule that `UNIQUE (user_id)` on `#__externallogin_users` already enforces.

**2. Match on `(server_id, sub)` alone and ignore `iss`.** Rejected: `sub` is only unique within its issuer. A changed issuer means a different source of identities and should take an administrator's decision.

**3. Admin import of verified `iss` + `sub` pairs, with no trust on first use.** Deferred. It closes the window entirely but needs per-provider export tooling. Trust on first use opens only when an administrator switches an existing server to subject mode, and the option's description says so.

**4. Bind Microsoft Entra accounts by `oid`.** Out of scope. Entra's `sub` is pairwise per application, so re-registering the application changes it; `oid` is stable across applications but is not a standard claim.

## Consequences

- Subject mode stops a changed or user-editable username claim from moving a person into another account or locking them out of their own.
- Until every account on a switched server has logged in once, whoever presents an account's username claim is trusted once. New servers have no such window, because accounts they create record their subject at registration.
- Administrators can see the stored issuer and subject on the Users screen, read-only.
- The subject-mode denials (subject or issuer mismatch, a rename or email clash under autoupdate, an over-long `iss` or `sub`) are always written to the External Login logs, unlike the opt-in log categories. They are security events, and the log is the administrator's only lead after an issuer change.
- CAS keeps username binding: it has no standard immutable attribute.
