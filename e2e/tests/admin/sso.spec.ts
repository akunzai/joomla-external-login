import {
  test,
  expect,
  ADMIN_USERNAME,
  ADMIN_PASSWORD,
  KEYCLOAK_USERNAME,
  KEYCLOAK_PASSWORD,
  KEYCLOAK_USER_EMAIL,
  OIDC_KEYCLOAK_USERNAME,
  OIDC_KEYCLOAK_PASSWORD,
} from '../../fixtures/test-fixtures';

const CAS_SERVER_TITLE = 'Keycloak CAS';
const OIDC_SERVER_TITLE = 'Keycloak OIDC';

// Regression tests for #303. The protocol plugins forward the administrator callback to
// com_login's `login` task, whose checkToken() reads POST only; the token used to be set on the
// top-level input, so every administrator SSO login was refused as a forged request.
test.describe('Administrator SSO with Keycloak', () => {
  test('CAS login through the administrator module lands on the control panel', async ({
    siteHomePage,
    adminLoginPage,
    keycloakLoginPage,
    page,
  }) => {
    // Make sure the Keycloak identity has a Joomla account bound to the CAS server. CAS sends no
    // groups here (group_xpath is empty), so the group assigned below survives later logins.
    await siteHomePage.goto();
    await siteHomePage.clickExternalLogin(CAS_SERVER_TITLE);
    await keycloakLoginPage.completeLoginIfPrompted(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);
    expect(await siteHomePage.isLoggedIn()).toBe(true);
    await siteHomePage.logoutAndWaitForGuest();

    // Grant that account administrator access (Manager) as the local super user.
    await adminLoginPage.goto();
    await adminLoginPage.login(ADMIN_USERNAME, ADMIN_PASSWORD);
    await page.waitForURL(/\/administrator\/index\.php/);
    await page.goto(
      `/administrator/index.php?option=com_users&view=users&filter[search]=${encodeURIComponent(KEYCLOAK_USER_EMAIL)}`,
    );
    await page.locator('table tbody tr').filter({ hasText: KEYCLOAK_USER_EMAIL }).getByRole('link').first().click();
    await page.getByRole('tab', { name: 'Assigned User Groups' }).click();
    await page.getByLabel('Manager').check();
    await page.getByRole('button', { name: 'Save & Close' }).click();
    await expect(page.locator('joomla-alert[type="success"], .alert-success').first()).toBeVisible();
    await page.context().clearCookies();

    await adminLoginPage.goto();
    await adminLoginPage.clickExternalLogin(CAS_SERVER_TITLE);
    await keycloakLoginPage.completeLoginIfPrompted(KEYCLOAK_USERNAME, KEYCLOAK_PASSWORD);

    await expect(page).toHaveURL(/\/administrator\//);
    await expect(page.getByText(/security token did not match/i)).toHaveCount(0);
    await expect(page.getByRole('heading', { name: /Home Dashboard/i })).toBeVisible();
  });

  test('OIDC login through the administrator module lands on the control panel', async ({
    adminLoginPage,
    keycloakLoginPage,
    page,
  }) => {
    // The test2 fixture user carries Keycloak's "Manager" realm role, which the OIDC server's
    // groups_claim maps to the Joomla Manager group on every login.
    await adminLoginPage.goto();
    await adminLoginPage.clickExternalLogin(OIDC_SERVER_TITLE);
    await keycloakLoginPage.completeLoginIfPrompted(OIDC_KEYCLOAK_USERNAME, OIDC_KEYCLOAK_PASSWORD);

    await expect(page).toHaveURL(/\/administrator\//);
    await expect(page.getByText(/security token did not match/i)).toHaveCount(0);
    await expect(page.getByRole('heading', { name: /Home Dashboard/i })).toBeVisible();
  });

  test('a direct request to the administrator login task without a token is refused', async ({
    adminLoginPage,
    page,
  }) => {
    // Load the login page first so the session is not new; otherwise checkToken() takes its
    // "session expired" redirect instead of refusing the token.
    await adminLoginPage.goto();
    await page.goto(
      `/administrator/index.php?option=com_login&task=login&username=${ADMIN_USERNAME}&passwd=${encodeURIComponent(ADMIN_PASSWORD)}`,
    );

    await expect(page.getByText(/security token did not match/i)).toBeVisible();
    await expect(page.getByRole('heading', { name: /Joomla Administrator Login/i })).toBeVisible();
  });
});
