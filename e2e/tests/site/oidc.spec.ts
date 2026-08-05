import { test, expect, OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD } from '../../fixtures/test-fixtures';

const OIDC_SERVER_TITLE = 'Keycloak OIDC';

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

    // Leave the site logged out for subsequent tests / suites.
    await siteHomePage.clickLogout();
  });
});
