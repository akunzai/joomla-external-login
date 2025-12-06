import { test, expect, KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD, KEYCLOAK_USER_EMAIL } from '../../fixtures/test-fixtures';

test.describe('SSO with Keycloak', () => {
  test('should display External Login module on home page', async ({ siteHomePage }) => {
    await siteHomePage.goto();

    const isVisible = await siteHomePage.isExternalLoginModuleVisible();
    expect(isVisible).toBe(true);
  });

  test('should show login button in External Login module', async ({ siteHomePage }) => {
    await siteHomePage.goto();

    await expect(siteHomePage.externalLoginButton).toBeVisible();
  });

  test('should redirect to Keycloak on login click', async ({ siteHomePage, keycloakLoginPage, page }) => {
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin();

    // Wait for redirect to Keycloak
    await keycloakLoginPage.waitForKeycloakPage();

    // Verify we're on Keycloak login page
    await expect(keycloakLoginPage.usernameInput).toBeVisible();
    await expect(keycloakLoginPage.passwordInput).toBeVisible();
  });

  test('should login via Keycloak and redirect back to Joomla', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin();

    // Wait for Keycloak page
    await keycloakLoginPage.waitForKeycloakPage();

    // Login with Keycloak credentials
    await keycloakLoginPage.login(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);

    // Wait for redirect back to Joomla
    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    // Verify user is logged in
    const isLoggedIn = await siteHomePage.isLoggedIn();
    expect(isLoggedIn).toBe(true);
  });

  test('should show logout button after SSO login', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin();

    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);

    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    await expect(siteHomePage.logoutButton).toBeVisible();
  });

  test('should logout from SSO session', async ({
    siteHomePage,
    keycloakLoginPage,
    page,
  }) => {
    // Login first
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin();

    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);

    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    // Verify logged in
    expect(await siteHomePage.isLoggedIn()).toBe(true);

    // Logout
    await siteHomePage.clickLogout();

    // Wait for logout to complete
    await page.waitForTimeout(2000);

    // Verify logged out - login button should be visible again
    await siteHomePage.goto();
    await expect(siteHomePage.externalLoginButton).toBeVisible();
  });

  test('should create user on first SSO login (auto-register)', async ({
    siteHomePage,
    keycloakLoginPage,
    usersPage,
    authenticatedAdminPage,
    page,
  }) => {
    // First, do SSO login to create user
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin();

    await keycloakLoginPage.waitForKeycloakPage();
    await keycloakLoginPage.login(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);

    await page.waitForURL(/^https:\/\/www\.dev\.local\/?/, { timeout: 15000 });

    // Logout from site
    await siteHomePage.clickLogout();
    await page.waitForTimeout(2000);

    // Now check admin for the user
    void authenticatedAdminPage;
    await usersPage.goto();

    // Search for the user by email
    await usersPage.searchUser(KEYCLOAK_USER_EMAIL);

    // User should be visible (was auto-registered)
    const isVisible = await usersPage.isUserVisible(KEYCLOAK_USER_EMAIL);
    expect(isVisible).toBe(true);
  });
});
