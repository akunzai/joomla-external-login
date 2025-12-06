import { test, expect } from '../../fixtures/test-fixtures';

test.describe('External Login Plugins', () => {
  test.beforeEach(async ({ authenticatedAdminPage }) => {
    void authenticatedAdminPage;
  });

  test('should find External Login authentication plugin', async ({ pluginsPage, page }) => {
    await pluginsPage.goto();
    await pluginsPage.searchPlugin('external');

    const authPlugin = await pluginsPage.getPluginRow('Authentication - External Login');
    await expect(authPlugin).toBeVisible();
  });

  test('should find External Login system plugin', async ({ pluginsPage }) => {
    await pluginsPage.goto();
    await pluginsPage.searchPlugin('external');

    const systemPlugin = await pluginsPage.getPluginRow('System - External Login');
    await expect(systemPlugin).toBeVisible();
  });

  test('should find CAS Login system plugin', async ({ pluginsPage }) => {
    await pluginsPage.goto();
    await pluginsPage.searchPlugin('cas');

    const casPlugin = await pluginsPage.getPluginRow('System - CAS Login');
    await expect(casPlugin).toBeVisible();
  });

  test('should show plugins as enabled', async ({ pluginsPage }) => {
    await pluginsPage.goto();
    await pluginsPage.searchPlugin('external');

    // Check Authentication plugin is enabled
    const isAuthEnabled = await pluginsPage.isPluginEnabled('Authentication - External Login');
    expect(isAuthEnabled).toBe(true);

    // Check System plugin is enabled
    const isSystemEnabled = await pluginsPage.isPluginEnabled('System - External Login');
    expect(isSystemEnabled).toBe(true);
  });

  test('CAS Login plugin should be enabled', async ({ pluginsPage }) => {
    await pluginsPage.goto();
    await pluginsPage.searchPlugin('cas');

    const isCasEnabled = await pluginsPage.isPluginEnabled('System - CAS Login');
    expect(isCasEnabled).toBe(true);
  });
});
