import { test, expect } from '../../fixtures/test-fixtures';

test.describe('Translation Verification', () => {
  test.beforeEach(async ({ authenticatedAdminPage }) => {
    // Login to admin is handled by the fixture
    void authenticatedAdminPage;
  });

  test('should not have untranslated strings on servers list', async ({ serversPage }) => {
    await serversPage.goto();

    // Only check for our extension's translation keys (not Joomla core)
    const pageContent = await serversPage.page.content();
    const untranslatedPattern = /(?:COM_EXTERNALLOGIN|PLG_SYSTEM_CASLOGIN|PLG_SYSTEM_EXTERNALLOGIN|PLG_AUTHENTICATION_EXTERNALLOGIN|PLG_USER_CBEXTERNALLOGIN|MOD_EXTERNALLOGIN)[A-Z_]*/g;
    const matches = pageContent.match(untranslatedPattern) || [];

    // Filter out false positives (like CSS classes or IDs that might contain these patterns)
    const realUntranslated = matches.filter((match) => {
      // Check if it appears as visible text (not in attributes)
      return pageContent.includes(`>${match}<`);
    });

    expect(realUntranslated, `Found untranslated strings: ${realUntranslated.join(', ')}`).toHaveLength(0);
  });

  test('should not have untranslated strings on users list', async ({ usersPage }) => {
    await usersPage.goto();

    const pageContent = await usersPage.page.content();
    const untranslatedPattern = /(?:COM_EXTERNALLOGIN|PLG_SYSTEM_CASLOGIN|PLG_SYSTEM_EXTERNALLOGIN|PLG_AUTHENTICATION_EXTERNALLOGIN|PLG_USER_CBEXTERNALLOGIN|MOD_EXTERNALLOGIN)[A-Z_]*/g;
    const matches = pageContent.match(untranslatedPattern) || [];

    const realUntranslated = matches.filter((match) => {
      return pageContent.includes(`>${match}<`);
    });

    expect(realUntranslated, `Found untranslated strings: ${realUntranslated.join(', ')}`).toHaveLength(0);
  });

  test('should not have untranslated strings on logs list', async ({ logsPage }) => {
    await logsPage.goto();

    const pageContent = await logsPage.page.content();
    const untranslatedPattern = /(?:COM_EXTERNALLOGIN|PLG_SYSTEM_CASLOGIN|PLG_SYSTEM_EXTERNALLOGIN|PLG_AUTHENTICATION_EXTERNALLOGIN|PLG_USER_CBEXTERNALLOGIN|MOD_EXTERNALLOGIN)[A-Z_]*/g;
    const matches = pageContent.match(untranslatedPattern) || [];

    const realUntranslated = matches.filter((match) => {
      return pageContent.includes(`>${match}<`);
    });

    expect(realUntranslated, `Found untranslated strings: ${realUntranslated.join(', ')}`).toHaveLength(0);
  });

  test('should have translated tab labels on server edit', async ({ serversPage, page }) => {
    await serversPage.goto();
    await serversPage.clickServerLink('Keycloak');

    // Verify CAS plugin tabs are translated
    // These should show as translated labels, not PLG_SYSTEM_CASLOGIN_* keys
    await expect(page.getByRole('tab', { name: 'CAS' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'CAS parameters' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Attributes' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Connection' })).toBeVisible();
  });

  test('should not have untranslated strings on server edit page', async ({ serversPage, page }) => {
    await serversPage.goto();
    await serversPage.clickServerLink('Keycloak');

    const pageContent = await page.content();
    // Only check for our extension's translation keys
    const untranslatedPattern = /(?:COM_EXTERNALLOGIN|PLG_SYSTEM_CASLOGIN|PLG_SYSTEM_EXTERNALLOGIN|PLG_AUTHENTICATION_EXTERNALLOGIN|PLG_USER_CBEXTERNALLOGIN|MOD_EXTERNALLOGIN)[A-Z_]*/g;
    const matches = pageContent.match(untranslatedPattern) || [];

    // Filter out false positives (like hidden input values or JS variables)
    const realUntranslated = matches.filter((match) => {
      // Check if it appears as visible text (between > and <)
      const visiblePattern = new RegExp(`>[^<]*${match}[^<]*<`);
      return visiblePattern.test(pageContent);
    });

    expect(realUntranslated, `Found untranslated strings: ${realUntranslated.join(', ')}`).toHaveLength(0);
  });
});
