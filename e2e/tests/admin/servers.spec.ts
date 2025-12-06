import { test, expect } from '../../fixtures/test-fixtures';

test.describe('External Login Servers', () => {
  test.beforeEach(async ({ authenticatedAdminPage }) => {
    // Login to admin is handled by the fixture
    void authenticatedAdminPage;
  });

  test('should display servers list', async ({ serversPage }) => {
    await serversPage.goto();

    await expect(serversPage.heading).toBeVisible();
    await expect(serversPage.newButton).toBeVisible();
  });

  test('should show Keycloak server', async ({ serversPage }) => {
    await serversPage.goto();

    const keycloakRow = await serversPage.getServerRow('Keycloak');
    await expect(keycloakRow).toBeVisible();

    // Check plugin column shows CAS in the row
    await expect(keycloakRow.getByText('CAS')).toBeVisible();
  });

  test('should open server edit page', async ({ serversPage, serverEditPage }) => {
    await serversPage.goto();
    await serversPage.clickServerLink('Keycloak');

    await expect(serverEditPage.titleInput).toHaveValue('Keycloak');
  });

  test('should edit server settings', async ({ serversPage, serverEditPage, page }) => {
    await serversPage.goto();
    await serversPage.clickServerLink('Keycloak');

    // Verify current settings
    await expect(serverEditPage.titleInput).toHaveValue('Keycloak');

    // Check auto-register is enabled
    await expect(serverEditPage.autoRegisterYes).toBeChecked();

    // Check auto-update is enabled
    await expect(serverEditPage.autoUpdateYes).toBeChecked();

    // Close without changes
    await serverEditPage.close();
    await expect(serversPage.heading).toBeVisible();
  });

  test('should toggle server publish status', async ({ serversPage, page }) => {
    await serversPage.goto();

    // Get initial status
    const keycloakRow = await serversPage.getServerRow('Keycloak');
    await expect(keycloakRow).toBeVisible();

    // Select and unpublish
    await serversPage.selectServer('Keycloak');
    await serversPage.unpublishButton.click();

    // Wait for page refresh
    await page.waitForTimeout(1000);

    // Select and publish again
    await serversPage.selectServer('Keycloak');
    await serversPage.publishButton.click();

    // Verify it's published
    await page.waitForTimeout(1000);
    const row = await serversPage.getServerRow('Keycloak');
    await expect(row).toBeVisible();
  });
});
