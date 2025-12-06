import { test, expect } from '../../fixtures/test-fixtures';

test.describe('External Login Users', () => {
  test.beforeEach(async ({ authenticatedAdminPage }) => {
    void authenticatedAdminPage;
  });

  test('should display users list page', async ({ usersPage, page }) => {
    await usersPage.goto();

    await expect(page.getByRole('heading', { name: /Users/i })).toBeVisible();
    await expect(usersPage.searchInput).toBeVisible();
  });

  test('should show tab navigation', async ({ usersPage, page }) => {
    await usersPage.goto();

    // Check for tab links in main content area
    const tabNav = page.locator('main');
    await expect(tabNav.getByRole('link', { name: 'Servers' })).toBeVisible();
    await expect(tabNav.getByRole('link', { name: 'Users' })).toBeVisible();
    await expect(tabNav.getByRole('link', { name: 'Logs' })).toBeVisible();
    await expect(tabNav.getByRole('link', { name: 'About' })).toBeVisible();
  });
});
