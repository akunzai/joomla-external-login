import { test, expect } from '../../fixtures/test-fixtures';

test.describe('External Login Logs', () => {
  test.beforeEach(async ({ authenticatedAdminPage }) => {
    void authenticatedAdminPage;
  });

  test('should display logs list page', async ({ logsPage, page }) => {
    await logsPage.goto();

    await expect(page.getByRole('heading', { name: /Logs/i })).toBeVisible();
    await expect(logsPage.searchInput).toBeVisible();
  });

  test('should show tab navigation', async ({ logsPage, page }) => {
    await logsPage.goto();

    // Check for tab links in main content area
    const tabNav = page.locator('main');
    await expect(tabNav.getByRole('link', { name: 'Servers' })).toBeVisible();
    await expect(tabNav.getByRole('link', { name: 'Users' })).toBeVisible();
    await expect(tabNav.getByRole('link', { name: 'Logs' })).toBeVisible();
    await expect(tabNav.getByRole('link', { name: 'About' })).toBeVisible();
  });

  test('should have search functionality', async ({ logsPage }) => {
    await logsPage.goto();

    await expect(logsPage.searchInput).toBeVisible();
    await expect(logsPage.searchButton).toBeVisible();
    await expect(logsPage.clearButton).toBeVisible();
  });
});
