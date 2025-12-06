import { test, expect, ADMIN_USERNAME, ADMIN_PASSWORD } from '../../fixtures/test-fixtures';

test.describe('Admin Login', () => {
  test('should login successfully with valid credentials', async ({ adminLoginPage, page }) => {
    await adminLoginPage.goto();
    await adminLoginPage.login(ADMIN_USERNAME, ADMIN_PASSWORD);

    await expect(page).toHaveURL(/\/administrator\/index\.php/);
    await expect(page.getByRole('heading', { name: /Home Dashboard/i })).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ adminLoginPage }) => {
    await adminLoginPage.goto();
    await adminLoginPage.login(ADMIN_USERNAME, 'wrong-password');

    await expect(adminLoginPage.errorMessage).toBeVisible();
  });
});
