import {
  test,
  expect,
  OIDC_KEYCLOAK_USERNAME,
  OIDC_KEYCLOAK_PASSWORD,
  OIDC_KEYCLOAK_USER_EMAIL,
} from '../../fixtures/test-fixtures';

const OIDC_SERVER_TITLE = 'Keycloak OIDC';
const OIDC_SERVER_ID = 2;
// Joomla core's stock install fixture group id — not configurable by this project's own
// install.sh, so stable across a fresh devcontainer stack.
const JOOMLA_EDITOR_GROUP_ID = 4;

test.describe('OIDC login with Keycloak', () => {
  test('should display External Login module on home page', async ({ siteHomePage }) => {
    await siteHomePage.goto();

    const isVisible = await siteHomePage.isExternalLoginModuleVisible();
    expect(isVisible).toBe(true);
  });

  test('should show login button in External Login module', async ({ siteHomePage }) => {
    await siteHomePage.goto();

    await expect(siteHomePage.externalLoginButton).toBeVisible();
  });

  test('should redirect to Keycloak on OIDC login click', async ({ siteHomePage, keycloakLoginPage, page }) => {
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);

    // Wait for redirect to Keycloak's authorization endpoint
    await keycloakLoginPage.waitForKeycloakPage();

    // Verify we're on Keycloak login page
    await expect(keycloakLoginPage.usernameInput).toBeVisible();
    await expect(keycloakLoginPage.passwordInput).toBeVisible();
  });

  test('should login via Keycloak OIDC and redirect back to Joomla', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);

    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);

    // Wait for redirect back to Joomla (the authorization code callback)
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    // Verify user is logged in
    const isLoggedIn = await siteHomePage.isLoggedIn();
    expect(isLoggedIn).toBe(true);
  });

  test('should show logout button after OIDC login', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);

    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);

    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    await expect(siteHomePage.logoutButton).toBeVisible();

    // Leave the site logged out for subsequent tests / suites. The configured server has
    // autologout enabled, so this chains into Keycloak's RP-Initiated Logout and lands back
    // on the site via post_logout_redirect.
    await siteHomePage.clickLogout();
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
  });

  test('should end the Keycloak session on logout when autologout is enabled (RP-Initiated Logout)', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);

    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);

    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    // Logout triggers the plugin's RP-Initiated Logout redirect to Keycloak's
    // end_session_endpoint with id_token_hint, which (with post_logout_redirect configured)
    // sends the browser straight back to the site once Keycloak's own session is ended.
    await siteHomePage.clickLogout();
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
    await expect(siteHomePage.externalLoginButton).toBeVisible();

    // Prove it was the *IdP* session that ended, not just the local Joomla one: if Keycloak's
    // SSO session had survived, a fresh OIDC login attempt would silently re-authenticate
    // without prompting for credentials. Requiring the login form again is the signal.
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);
    await keycloakLoginPage.waitForKeycloakPage();
    await expect(keycloakLoginPage.usernameInput).toBeVisible();

    // Finish logging back in and log out again so subsequent tests / suites start fresh.
    await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
    await siteHomePage.clickLogout();
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
  });

  test('should map user groups from Keycloak realm_access.roles claim during OIDC login', async ({
    siteHomePage,
    keycloakLoginPage,
    serverEditPage,
    usersPage,
    coreUsersPage,
    authenticatedAdminPage,
    page,
  }) => {
    void authenticatedAdminPage;

    // Ensure site user is logged out first
    await siteHomePage.goto();
    if (await siteHomePage.isLoggedIn()) {
      await siteHomePage.clickLogout();
    }

    // Configure OIDC server group claim mapping using Keycloak's default claim shape
    // (realm_access.roles) — the test2 fixture user carries a Keycloak "Editor" realm role,
    // matching the Joomla "Editor" group's title exactly, so no group_separator is needed.
    await serverEditPage.goto(OIDC_SERVER_ID);
    await serverEditPage.setGroupsClaim('realm_access.roles');
    await serverEditPage.save();
    expect(await serverEditPage.groupsClaimInput.inputValue()).toBe('realm_access.roles');

    try {
      // Perform OIDC login
      await siteHomePage.goto();
      await siteHomePage.clickExternalLogin(OIDC_SERVER_TITLE);

      await keycloakLoginPage.waitForKeycloakPage();
      await keycloakLoginPage.login(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);

      await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

      // Verify logged in on site
      expect(await siteHomePage.isLoggedIn()).toBe(true);

      // Check user in com_externallogin admin users list
      await usersPage.goto();
      await usersPage.searchUser(OIDC_KEYCLOAK_USER_EMAIL);
      expect(await usersPage.isUserVisible(OIDC_KEYCLOAK_USER_EMAIL)).toBe(true);

      // Prove the claim actually reached Joomla's ACL: the Keycloak "Editor" realm role
      // on test2 (demo-users-1.json) must have resolved through ExternalloginHelper::getGroups()
      // into the real "Editor" Joomla group — not just that login/bridging succeeded.
      await coreUsersPage.gotoFilteredByGroup(JOOMLA_EDITOR_GROUP_ID);
      expect(await coreUsersPage.isUserVisible(OIDC_KEYCLOAK_USER_EMAIL)).toBe(true);

      // Leave the site logged out for subsequent tests / suites.
      await siteHomePage.goto();
      await siteHomePage.clickLogout();
      await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });
    } finally {
      // Reset server group mapping settings
      await serverEditPage.goto(OIDC_SERVER_ID);
      await serverEditPage.setGroupsClaim('');
      await serverEditPage.save();
    }
  });
});
