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
  // Regression test for issue #249, later strengthened alongside #254's follow-up fix. CAS and
  // OIDC servers backed by the same Keycloak realm now resolve the same Keycloak identity to the
  // same Joomla username (both use the identity's email — see the OIDC server's username_claim
  // seed config), so logging in via both with the same identity (whether Keycloak's SSO session
  // carries the identity over silently, or it's re-entered on a fresh Keycloak login form) makes
  // the external-login bridge plugin's shared isActivatedForServer check
  // (plugins/authentication/externallogin) deny the second attempt uniformly regardless of
  // direction. Before #249's fix, that denial never stopped event propagation, so the core
  // `authentication/joomla` plugin ran next and overwrote the response with its own generic
  // "Empty password not allowed." message, masking the real reason. Before the isActivatedForServer
  // branch grew its own enqueueMessage() call, the denial was also silent — the visitor was bounced
  // back to the login form with no explanation at all.
  test('OIDC login with an identity already bound to the CAS server shows the cross-server message, not the misleading core-Joomla error', async ({
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

    const bodyText = await page.textContent('body');
    // The core Joomla plugin's generic empty-password fallback must never be what the visitor sees.
    expect(bodyText).not.toMatch(/empty password not allowed/i);
    expect(bodyText).toContain('This account is linked to a different login server');
    expect(await siteHomePage.isLoggedIn()).toBe(false);
  });

  // Regression test for issue #251. Caslogin::onAfterInitialise() enqueues a translated message
  // when it denies a login because the account is bound to a different server — but unlike
  // onGetIcons()/onContentPrepareForm() in the same file, it didn't defensively re-call
  // loadLanguage() first, so Text::_() could fall back to showing the raw language key instead
  // of the translated string. That legacy CAS-specific pre-check is superseded here by the shared
  // isActivatedForServer denial (see the describe-block comment above), but the same translation
  // hygiene applies to its own language key.
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
    // text, not as a raw language key.
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(CAS_SERVER_TITLE);
    if (await keycloakLoginPage.usernameInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);
    }
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('PLG_SYSTEM_CASLOGIN_NO_ACTIVATION_ON_SERVER');
    expect(bodyText).not.toContain('PLG_AUTHENTICATION_EXTERNALLOGIN_NOT_ACTIVATED');
    expect(bodyText).toContain('This account is linked to a different login server');
    expect(await siteHomePage.isLoggedIn()).toBe(false);
  });
});
