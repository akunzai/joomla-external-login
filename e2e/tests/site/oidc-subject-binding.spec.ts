import { type Page } from '@playwright/test';
import { test, expect, ADMIN_USERNAME, ADMIN_PASSWORD } from '../../fixtures/test-fixtures';
import { KeycloakAdmin } from '../../fixtures/keycloak-admin';
import { type AdminLoginPage } from '../../pages/admin/login.page';
import { type KeycloakLoginPage } from '../../pages/keycloak/login.page';
import { type SiteHomePage } from '../../pages/site/home.page';

// OIDC subject binding (#304, ADR 0002). Every test creates its own Keycloak users with a
// per-run prefix, so reruns never meet accounts bound by an earlier run.
const SUBJECT_SERVER_TITLE = 'Keycloak OIDC (subject)';
const USERNAME_SERVER_TITLE = 'Keycloak OIDC';
const PASSWORD = 'subject-binding';
const RUN = `sb${Date.now().toString(36)}`;

let keycloak: KeycloakAdmin;
const createdUsers: string[] = [];

test.beforeAll(async () => {
  keycloak = await KeycloakAdmin.connect();
});

test.afterAll(async () => {
  for (const id of createdUsers) {
    await keycloak.deleteUser(id);
  }
  await keycloak.dispose();
});

async function createUser(name: string, email: string): Promise<{ username: string; sub: string }> {
  const username = `${RUN}-${name}`;
  const sub = await keycloak.createUser(username, email, PASSWORD);
  createdUsers.push(sub);

  return { username, sub };
}

const email = (name: string) => `${RUN}-${name}@example.com`;

/** Signs in to the site through the subject-mode server, starting from no session anywhere. */
async function signIn(page: Page, site: SiteHomePage, keycloakLogin: KeycloakLoginPage, username: string) {
  await page.context().clearCookies();
  await site.goto();
  await site.clickExternalLogin(SUBJECT_SERVER_TITLE);
  await keycloakLogin.completeLoginIfPrompted(username, PASSWORD);
}

async function signInToAdmin(page: Page, adminLogin: AdminLoginPage) {
  await page.context().clearCookies();
  await adminLogin.goto();
  await adminLogin.login(ADMIN_USERNAME, ADMIN_PASSWORD);
  await page.waitForURL(/\/administrator\/index\.php/);
}

/** Rows of com_externallogin's Users screen matching `search`. */
async function externalUsers(page: Page, search: string) {
  await page.goto(`/administrator/index.php?option=com_externallogin&view=users&filter_search=${encodeURIComponent(search)}`);

  return page.locator('table tbody tr');
}

test.describe('OIDC subject binding', () => {
  test('a changed email at the provider still reaches the same account, which follows the new email', async ({
    page,
    siteHomePage,
    keycloakLoginPage,
    adminLoginPage,
  }) => {
    const user = await createUser('drift', email('drift'));

    await signIn(page, siteHomePage, keycloakLoginPage, user.username);
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    await keycloak.setEmail(user.sub, email('drift-renamed'));
    await signIn(page, siteHomePage, keycloakLoginPage, user.username);
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    // One account, renamed (autoupdate is on), still carrying the same subject.
    await signInToAdmin(page, adminLoginPage);
    const rows = await externalUsers(page, `${RUN}-drift`);
    await expect(rows).toHaveCount(1);
    await expect(rows.first()).toContainText(email('drift-renamed'));
    await expect(rows.first()).toContainText(`Subject: ${user.sub}`);
    await expect(rows.first()).toContainText('Issuer: https://auth.dev.local:8443/realms/demo');
  });

  test('a different subject presenting a bound account\'s email cannot sign in to it', async ({
    page,
    siteHomePage,
    keycloakLoginPage,
  }) => {
    const owner = await createUser('owner', email('owner'));
    await signIn(page, siteHomePage, keycloakLoginPage, owner.username);
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    // The owner moves to another address at the provider (without signing in again), and a
    // different Keycloak identity takes the old one, which still names the owner's account.
    await keycloak.setEmail(owner.sub, email('owner-moved'));
    const intruder = await createUser('intruder', email('owner'));

    await signIn(page, siteHomePage, keycloakLoginPage, intruder.username);

    await expect(page.getByText('This account is linked to a different identity at the login server')).toBeVisible();
    expect(await siteHomePage.isLoggedIn()).toBe(false);
  });

  test('a rename that clashes with another account denies the login and logs the reason', async ({
    page,
    siteHomePage,
    keycloakLoginPage,
    adminLoginPage,
  }) => {
    const first = await createUser('first', email('first'));
    const second = await createUser('second', email('second'));
    await signIn(page, siteHomePage, keycloakLoginPage, first.username);
    expect(await siteHomePage.isLoggedIn()).toBe(true);
    await signIn(page, siteHomePage, keycloakLoginPage, second.username);
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    // At the provider, `second` moves away and `first` takes its old address. Joomla still has
    // an account named after that address, so renaming `first`'s account to it must fail.
    await keycloak.setEmail(second.sub, email('second-moved'));
    await keycloak.setEmail(first.sub, email('second'));
    await signIn(page, siteHomePage, keycloakLoginPage, first.username);
    expect(await siteHomePage.isLoggedIn()).toBe(false);

    await signInToAdmin(page, adminLoginPage);
    await page.goto(`/administrator/index.php?option=com_externallogin&view=logs&filter_search=${encodeURIComponent(email('first'))}`);
    await expect(page.locator('table tbody tr').filter({ hasText: 'failed' }).first()).toBeVisible();
  });

  test('a rebind clears the stored subject, and the next login records it again', async ({
    page,
    siteHomePage,
    keycloakLoginPage,
    adminLoginPage,
  }) => {
    const user = await createUser('rebind', email('rebind'));
    await signIn(page, siteHomePage, keycloakLoginPage, user.username);
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    await signInToAdmin(page, adminLoginPage);
    let rows = await externalUsers(page, email('rebind'));
    await expect(rows.first()).toContainText(`Subject: ${user.sub}`);

    // Rebind to the same server. This posts the same request the "Enable External Login"
    // toolbar modal sends, through the list form so it carries the real CSRF token.
    await page.goto('/administrator/index.php?option=com_externallogin&view=servers');
    const href = await page.getByRole('link', { name: SUBJECT_SERVER_TITLE, exact: true }).getAttribute('href');
    const serverId = href?.match(/id=(\d+)/)?.[1] ?? '';
    expect(serverId).not.toBe('');
    rows = await externalUsers(page, email('rebind'));
    await rows.first().locator('input[name="cid[]"]').check();
    await page.evaluate((sid) => {
      const form = document.getElementById('adminForm') as HTMLFormElement;
      const server = document.createElement('input');
      server.type = 'hidden';
      server.name = 'server';
      server.value = sid;
      form.appendChild(server);
      (form.elements.namedItem('task') as HTMLInputElement).value = 'users.enableExternallogin';
      form.submit();
    }, serverId);
    await page.waitForLoadState('networkidle');

    rows = await externalUsers(page, email('rebind'));
    await expect(rows.first()).toContainText(SUBJECT_SERVER_TITLE);
    await expect(rows.first()).not.toContainText('Subject:');

    // Trust on first use: the account is bound to the server with no subject, so this login
    // records it.
    await signIn(page, siteHomePage, keycloakLoginPage, user.username);
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    await signInToAdmin(page, adminLoginPage);
    rows = await externalUsers(page, email('rebind'));
    await expect(rows.first()).toContainText(`Subject: ${user.sub}`);
  });

  test('a new OIDC server defaults to subject binding, and an upgraded one stays on username after saving', async ({
    page,
    adminLoginPage,
    serversPage,
  }) => {
    const bindingMode = page.locator('select[name="jform[params][binding_mode]"]');

    await signInToAdmin(page, adminLoginPage);
    await page.goto('/administrator/index.php?option=com_externallogin&task=server.add&plugin=system.oidclogin');
    await expect(bindingMode).toHaveValue('subject');
    await page.getByRole('button', { name: 'Cancel', exact: true }).or(page.getByRole('button', { name: 'Close', exact: true })).first().click();

    for (let pass = 0; pass < 2; pass++) {
      await serversPage.goto();
      await serversPage.clickServerLink(USERNAME_SERVER_TITLE);
      await expect(bindingMode).toHaveValue('username');
      await page.getByRole('button', { name: 'Save & Close' }).click();
      await expect(page.locator('joomla-alert[type="success"], .alert-success').first()).toBeVisible();
    }
  });
});
