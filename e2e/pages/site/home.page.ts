import { type Page, type Locator } from '@playwright/test';

export class SiteHomePage {
  readonly page: Page;
  readonly externalLoginModule: Locator;
  readonly externalLoginButton: Locator;
  readonly loginForm: Locator;
  readonly logoutButton: Locator;
  readonly userGreeting: Locator;

  constructor(page: Page) {
    this.page = page;
    this.externalLoginModule = page.locator('.externallogin, [class*="externallogin"]').first();
    // The login button is an input[type="submit"] with value "Log in" inside the externallogin module
    this.externalLoginButton = page.locator('.externallogin input[type="submit"], .externallogin button, .externallogin a').filter({ hasText: /Log in/i }).first();
    this.loginForm = page.locator('.mod-login, .login-form');
    // Logout button is also an input[type="submit"]
    this.logoutButton = page.locator('.externallogin input[type="submit"], .externallogin button').filter({ hasText: /Log out/i }).first();
    this.userGreeting = page.locator('.mod-login__username, .login-greeting');
  }

  async goto() {
    await this.page.goto('/');
  }

  /**
   * Click the External Login module's submit button. When more than one
   * server is configured, the module renders a server-selection dropdown
   * ahead of the shared "Log in" submit button; pass `serverTitle` to pick
   * a specific server (matched against the dropdown's option text), or
   * omit it to use the first configured server (its default selection).
   */
  async clickExternalLogin(serverTitle?: string) {
    const select = this.externalLoginModule.locator('select');

    if (await select.isVisible({ timeout: 1000 }).catch(() => false)) {
      if (serverTitle) {
        await select.selectOption({ label: serverTitle });
      } else {
        // Option index 0 is the placeholder "Select..." entry; 1 is the first real server.
        const firstServerValue = await select.locator('option').nth(1).getAttribute('value');
        await select.selectOption({ value: firstServerValue ?? '' });
      }
    }

    await this.externalLoginButton.click();
  }

  async clickLogout() {
    await this.logoutButton.click();
  }

  async isLoggedIn(): Promise<boolean> {
    return this.logoutButton.isVisible({ timeout: 5000 }).catch(() => false);
  }

  async isExternalLoginModuleVisible(): Promise<boolean> {
    return this.externalLoginModule.isVisible({ timeout: 5000 }).catch(() => false);
  }

  async getLoggedInUserName(): Promise<string | null> {
    if (await this.isLoggedIn()) {
      return this.userGreeting.textContent();
    }
    return null;
  }
}
