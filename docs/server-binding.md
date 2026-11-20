# One account, one external-login server

A Joomla account is bound to **exactly one** external-login server (CAS or OIDC). That binding is stored in `#__externallogin_users` (`server_id`, `user_id`) with `UNIQUE (user_id)`.

Design rationale: [ADR 0001](adr/0001-one-account-binds-one-external-login-server.md).

## Which account a login reaches

- **CAS**, and **OIDC servers in username mode**: the account whose username equals the username claim.
- **OIDC servers in subject mode** (the default for servers created in 5.3.0 or later): the account bound to the provider's verified issuer and subject (`iss` + `sub`), stored in the binding's `issuer` and `subject` columns. A changed username or email at the provider still reaches the same account. An account already bound to the server records its `iss` + `sub` on its first login after the server is switched to subject mode. A login whose `sub` or `iss` differs from the stored pair is denied.

The **Users** screen shows a stored subject and issuer under the server title.

Design rationale: [ADR 0002](adr/0002-oidc-accounts-bind-to-issuer-and-subject.md).

## What happens at login

If a visitor authenticates via server B but the matching Joomla account is already bound to server A, the login is **rejected** (not silently reassigned or merged). The user-facing guidance is to sign in through the original server or contact a site administrator.

This is enforced by the shared `authentication/externallogin` bridge for every protocol.

## How to rebind a user (admin UI)

Rebinding is an **admin-only** action. It is never performed automatically when someone logs in.

1. Open **Components → External Login → Users**.
2. Select the Joomla user(s) to rebind (the list shows the current server title).
3. Toolbar → **Enable External Login**.
4. In the server modal, click the **target** server (e.g. Keycloak OIDC).

That runs `UserModel::enableExternallogin()`:

- row exists → `UPDATE server_id` to the chosen server, and clear the stored OIDC issuer and subject  
- no row → `INSERT` a new binding  

Rebinding to the **same** server is how to recover an OIDC account whose issuer changed: it clears the stored pair, and the next login records the new one.

You do **not** need to Disable first. Using **Enable External Login** again with a different server overwrites the binding.

### Tips

- Pure SSO accounts (empty Joomla password) may not support **Disable External Login** (that path requires a non-empty password). Prefer Enable + pick the new server.
- **Enable/Disable Global** (modal with “Activate for all users” / “Disable for all users”) rebinds or clears **every** account for a server — use only for deliberate site-wide migration.

## How to rebind via SQL (emergency / bulk)

Prefer the admin UI for day-to-day work. SQL is fine for scripts or when the admin UI is unavailable.

```sql
-- List servers
SELECT id, title, plugin FROM #__externallogin_servers;

-- Inspect a user's binding (replace username)
SELECT u.id, u.username, e.server_id, s.title
FROM #__users u
LEFT JOIN #__externallogin_users e ON e.user_id = u.id
LEFT JOIN #__externallogin_servers s ON s.id = e.server_id
WHERE u.username = 'user@example.com';

-- Point the account at another server (replace ids)
UPDATE #__externallogin_users
SET server_id = 2,  -- target server id
    issuer = NULL,  -- clear the stored OIDC subject
    subject = NULL
WHERE user_id = 42; -- Joomla user id
```

Replace `#__` with your table prefix (e.g. `jos_`).

## Related demo note

In the devcontainer, e2e uses Keycloak user `test` for CAS and `test2` for OIDC so the two protocols do not compete for the same Joomla binding. See [`.devcontainer/joomla/README.md`](../.devcontainer/joomla/README.md).
