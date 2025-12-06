import { test as base } from '@playwright/test';
import { AdminLoginPage } from '../pages/admin/login.page';
import { AdminDashboardPage } from '../pages/admin/dashboard.page';
import { ServersPage } from '../pages/admin/servers.page';
import { ServerEditPage } from '../pages/admin/server-edit.page';
import { UsersPage } from '../pages/admin/users.page';
import { LogsPage } from '../pages/admin/logs.page';
import { PluginsPage } from '../pages/admin/plugins.page';
import { SiteHomePage } from '../pages/site/home.page';
import { KeycloakLoginPage } from '../pages/keycloak/login.page';

// Test credentials
export const ADMIN_USERNAME = 'admin';
export const ADMIN_PASSWORD = 'ChangeTheP@ssw0rd';
export const KEYCLOAK_USERNAME = 'test';
export const KEYCLOAK_PASSWORD = 'test';
export const KEYCLOAK_USER_EMAIL = 'test@example.com';
export const KEYCLOAK_USER_DISPLAY_NAME = 'Foo Bar';

// Define fixture types
type Fixtures = {
  adminLoginPage: AdminLoginPage;
  adminDashboardPage: AdminDashboardPage;
  serversPage: ServersPage;
  serverEditPage: ServerEditPage;
  usersPage: UsersPage;
  logsPage: LogsPage;
  pluginsPage: PluginsPage;
  siteHomePage: SiteHomePage;
  keycloakLoginPage: KeycloakLoginPage;
  authenticatedAdminPage: void;
};

// Extend the base test with custom fixtures
export const test = base.extend<Fixtures>({
  adminLoginPage: async ({ page }, use) => {
    await use(new AdminLoginPage(page));
  },

  adminDashboardPage: async ({ page }, use) => {
    await use(new AdminDashboardPage(page));
  },

  serversPage: async ({ page }, use) => {
    await use(new ServersPage(page));
  },

  serverEditPage: async ({ page }, use) => {
    await use(new ServerEditPage(page));
  },

  usersPage: async ({ page }, use) => {
    await use(new UsersPage(page));
  },

  logsPage: async ({ page }, use) => {
    await use(new LogsPage(page));
  },

  pluginsPage: async ({ page }, use) => {
    await use(new PluginsPage(page));
  },

  siteHomePage: async ({ page }, use) => {
    await use(new SiteHomePage(page));
  },

  keycloakLoginPage: async ({ page }, use) => {
    await use(new KeycloakLoginPage(page));
  },

  authenticatedAdminPage: async ({ page, adminLoginPage }, use) => {
    await adminLoginPage.goto();
    await adminLoginPage.login(ADMIN_USERNAME, ADMIN_PASSWORD);
    await page.waitForURL(/\/administrator\/index\.php/);

    // Wait for page to fully load
    await page.waitForLoadState('networkidle');

    // Handle first-time welcome tour if it appears
    const shepherdModal = page.locator('.shepherd-modal-is-visible');
    if (await shepherdModal.isVisible({ timeout: 2000 }).catch(() => false)) {
      // Try to click Skip/Cancel button
      const skipButton = page.locator('.shepherd-cancel-icon, .shepherd-button-secondary');
      if (await skipButton.first().isVisible({ timeout: 1000 }).catch(() => false)) {
        await skipButton.first().click({ force: true });
        await page.waitForTimeout(300);
      }
      // If still visible, remove via JavaScript
      if (await shepherdModal.isVisible({ timeout: 500 }).catch(() => false)) {
        await page.evaluate(() => {
          document.querySelectorAll('.shepherd-modal-overlay-container, .shepherd-element, svg.shepherd-modal-is-visible').forEach(el => el.remove());
        });
      }
    }

    // Handle Joomla Statistics opt-in dialog if it appears
    const statisticsDialog = page.locator('joomla-dialog, .joomla-modal').filter({ hasText: /statistics/i });
    if (await statisticsDialog.isVisible({ timeout: 1000 }).catch(() => false)) {
      const noButton = statisticsDialog.getByRole('button', { name: 'No' });
      if (await noButton.isVisible({ timeout: 500 }).catch(() => false)) {
        await noButton.click({ force: true });
        await page.waitForTimeout(300);
      }
    }

    await use();
  },
});

export { expect } from '@playwright/test';
