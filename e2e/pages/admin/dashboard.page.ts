import { type Page, type Locator } from '@playwright/test';

export class AdminDashboardPage {
  readonly page: Page;
  readonly componentsMenu: Locator;
  readonly externalLoginMenu: Locator;

  constructor(page: Page) {
    this.page = page;
    this.componentsMenu = page.locator('nav.main-nav').getByRole('link', { name: 'Components' });
    this.externalLoginMenu = page.locator('nav.main-nav').getByRole('link', { name: 'External Login' }).first();
  }

  async goto() {
    await this.page.goto('/administrator/index.php');
  }

  async navigateToExternalLogin() {
    await this.componentsMenu.click();
    await this.externalLoginMenu.click();
  }
}
