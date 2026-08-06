import {
  test,
  expect,
  KEYCLOAK_USERNAME,
  KEYCLOAK_PASSWORD,
  OIDC_KEYCLOAK_USERNAME,
  OIDC_KEYCLOAK_PASSWORD,
} from '../../fixtures/test-fixtures';

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

  // Regression test for issue #251. Caslogin::onAfterInitialise() enqueues a translated message
  // when it denies a login because the account is bound to a different server — but unlike
  // onGetIcons()/onContentPrepareForm() in the same file, it didn't defensively re-call
  // loadLanguage() first, so Text::_() could fall back to showing the raw language key instead
  // of the translated string.
  test('CAS login with an identity already bound to the OIDC server shows the translated cross-server message', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    // Log in via OIDC first so a Joomla account exists, bound to the OIDC server, under the
    // OIDC-specific Keycloak identity.
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);
    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
    expect(await siteHomePage.isLoggedIn()).toBe(true);
    await siteHomePage.clickLogout();

    // Attempt a CAS login with the same identity. Keycloak's CAS principal is the same regardless
    // of protocol, so this correctly matches the OIDC-bound account and is correctly denied (the
    // per-account server-binding check) — but the denial message itself must render as translated
    // text, not as the raw PLG_SYSTEM_CASLOGIN_NO_ACTIVATION_ON_SERVER language key.
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(CAS_SERVER_TITLE);
    if (await keycloakLoginPage.usernameInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);
    }
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('PLG_SYSTEM_CASLOGIN_NO_ACTIVATION_ON_SERVER');
    expect(bodyText).toContain('This account is linked to a different login server');
    expect(await siteHomePage.isLoggedIn()).toBe(false);
  });
});
