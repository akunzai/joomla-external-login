import { test, expect, KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD } from '../../fixtures/test-fixtures';

const CAS_SERVER_TITLE = 'Keycloak CAS';
const OIDC_SERVER_TITLE = 'Keycloak OIDC';

test.describe('Cross-protocol Keycloak identity reuse', () => {
  // Regression test for issue #249. CAS and OIDC servers backed by the same Keycloak realm share
  // one Keycloak identity per user; logging in via both with the same identity (whether Keycloak's
  // SSO session carries the identity over silently, or it's re-entered on a fresh Keycloak login
  // form) makes the external-login bridge plugin (plugins/authentication/externallogin) deny the
  // second attempt — the identity's email is already bound to the first server's account. Before
  // the fix, that denial never stopped event propagation, so the core `authentication/joomla`
  // plugin ran next and overwrote the response with its own generic "Empty password not allowed."
  // message, masking the real reason.
  test('OIDC login with an identity already bound to the CAS server must not surface the misleading core-Joomla error', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    // Log in via CAS first so a Joomla account exists, bound to the CAS server, under the shared
    // Keycloak identity.
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(CAS_SERVER_TITLE);
    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
    expect(await siteHomePage.isLoggedIn()).toBe(true);
    await siteHomePage.clickLogout();

    // Attempt an OIDC login with the same identity. Depending on whether the CAS server's
    // "Automatic logout" setting terminated Keycloak's own SSO session, this either lands straight
    // back on Joomla (silent SSO reuse) or shows a fresh Keycloak login form — handle both so the
    // test reproduces the identity collision regardless of that setting.
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);
    if (await keycloakLoginPage.usernameInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await keycloakLoginPage.login(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);
    }
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    // Whatever the specific outcome, the core Joomla plugin's generic empty-password fallback
    // must never be what the visitor sees.
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toMatch(/empty password not allowed/i);
  });
});
