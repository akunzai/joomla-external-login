# 0001. One Joomla Account Binds to Exactly One External-Login Server

## Status

Accepted (documents pre-existing, inherited behavior — not a new decision)

## Context

`com_externallogin`'s `#__externallogin_users` join table (`server_id`, `user_id`) records which external-login server a Joomla account is bound to. Its schema carries `UNIQUE (user_id)` alongside `UNIQUE (server_id, user_id)` — so in practice a Joomla account can be bound to at most one server, even though the table's shape looks like a many-to-many join.

This constraint predates the current repository history: `UNIQUE (user_id)` is present in the very first commit (`caeb877`, 2012). It is enforced uniformly for every protocol plugin (`system.caslogin`, `system.oidclogin`, and any future one), because the actual write path is the protocol-agnostic bridge plugin `plugins/authentication/externallogin`'s `addLoginRecord()` — no protocol plugin writes to `#__externallogin_users` directly.

When the OIDC plugin was designed (issue #236), this constraint was inherited without re-evaluation: the spec states the bridge plugin and `com_externallogin` are reused "unchanged," with "no database schema changes." None of that epic's design tickets (#216–#225) discuss relaxing it.

Today's admin-facing behavior around this constraint:

- **Login-time**: if a visitor authenticates via server B but their account is already bound to server A, the login is *rejected*, not silently reassigned. CAS's own user-facing message is explicit about the remedy: `PLG_SYSTEM_CASLOGIN_NO_ACTIVATION_ON_SERVER` = "This account is linked to a different login server. Sign in through that server instead, or contact your site administrator."
- **Admin-time**: an administrator *can* manually rebind a user to a different server, via `com_externallogin`'s Users screen (`UserModel::enableExternallogin()`), which explicitly `UPDATE`s `server_id`. This is an authenticated, deliberate admin action — never something the login flow itself performs.

So the binding is not just a uniqueness constraint; it's meant to encode a security boundary: **rebinding an account to a different server requires an authenticated admin action, and is never a side effect of an anonymous login attempt.**

> **Correction (2026-08-06, issue #254):** when this ADR was first written, that boundary was assumed to already hold universally. It didn't. The rejection quoted above is `Caslogin`'s *own*, CAS-specific pre-check — the shared bridge plugin (`Externallogin::onUserAuthenticate()`) that every protocol actually routes through had no equivalent check at all. A protocol plugin without its own copy of that check (`Oidclogin`, and any future one) would let a login whose resolved username matched an *existing* Joomla user through unconditionally, regardless of which server (if any) that user was actually bound to — with `autoupdate` on, even overwriting that account's email/name/groups. This was a real, exploitable account-takeover gap in shipped code, not a hypothetical risk of some future design change (see Alternative 2 below, which originally described it as exactly that). Issue #254 closed the gap by moving the check into the shared bridge plugin, so the security boundary this ADR describes is now actually enforced uniformly — not something each protocol plugin must remember to reimplement.

## Decision

Keep the one-account-one-server constraint as-is. No schema or bridge-plugin change.

## Alternatives Considered

**1. Coexist — allow one account to bind to multiple servers simultaneously** (drop `UNIQUE (user_id)`, keep `UNIQUE (server_id, user_id)`).

Technically viable and wouldn't touch the existing admin-gated rebinding security model — it would only *add* the ability to hold more than one binding at once. Not pursued because there is no concrete driving use case today (no reported need for a single Joomla account to be reachable via two different IdPs) and it isn't free: it would require reworking the admin Users screen (currently models "assign this server" as replacing the one existing binding), the bridge plugin's auto-register/auto-update logic, and a policy for resolving identity conflicts when two IdPs supply divergent claims (email, full name) for what's treated as the same Joomla account. Left as a future option if a real need appears, not rejected on principle.

**2. Overwrite / last-login-wins — logging in via a new server silently replaces the existing binding.**

Rejected. This would collapse the deliberate admin-gated rebinding boundary described above into an action any anonymous visitor can trigger. Concretely: if an attacker can register a matching username/email on a *different* IdP the site trusts (something many IdPs let end users self-set), they could silently steal an existing account's binding away from its legitimate IdP — without ever knowing the original IdP's password. This is a real account-takeover vector — and, as the correction above notes, was reachable with *no design change at all* prior to #254, because the shared bridge plugin never actually checked server binding for existing users in the first place.

## Consequences

- A visitor whose account is already bound to server A gets a hard rejection (not a redirect, not a silent merge) when attempting to log in via server B, with guidance to either use server A or contact an administrator. As of #254, this is enforced by the shared bridge plugin for every protocol, not just CAS's own pre-check.
- A single Joomla account cannot be federated across two IdPs at once (e.g., a CAS server and an OIDC server) without administrator intervention.
- Any future proposal to relax this constraint should default to the "coexist" shape, not "overwrite," and must additionally design: admin UI for managing multiple bindings, an identity-conflict resolution policy across providers, and updated bridge-plugin auto-register/auto-update semantics.
- Any *new* protocol plugin automatically inherits this protection by routing through the shared bridge plugin's `onUserAuthenticate()` — it no longer needs to reimplement `Caslogin`'s own defensive check to be safe.
- `Caslogin`'s own pre-check in `onAfterInitialise()` is now strictly redundant with the shared bridge-plugin check, but was deliberately left in place by #254 rather than removed: it aborts *earlier* (before the request is even rewritten into a Joomla login attempt) and produces CAS's own specific, already-translated user-facing message, which the shared check's generic `STATUS_DENIED` + redirect-menuitem path doesn't reproduce. Consolidating the two — dropping CAS's copy and giving the shared bridge-plugin denial equivalent per-protocol messaging — is a legitimate future cleanup, not a security requirement (the gap is already closed either way), and would touch CAS's existing, well-covered test surface (`CasloginTest.php`, `sso.spec.ts`, `cross-protocol-login.spec.ts`) for no user-facing security benefit. Left as an open follow-up rather than a tracked issue, since it's low priority and self-contained enough to pick up whenever someone next touches this area.
