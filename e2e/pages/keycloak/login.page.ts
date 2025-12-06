import { type Page, type Locator } from '@playwright/test';

export class KeycloakLoginPage {
  readonly page: Page;
  readonly usernameInput: Locator;
  readonly passwordInput: Locator;
  readonly loginButton: Locator;
  readonly errorMessage: Locator;

  constructor(page: Page) {
    this.page = page;
    this.usernameInput = page.locator('#username');
    this.passwordInput = page.locator('#password');
    this.loginButton = page.locator('#kc-login, [name="login"]');
    this.errorMessage = page.locator('.alert-error, .kc-feedback-text');
  }

  async login(username: string, password: string) {
    await this.usernameInput.fill(username);
    await this.passwordInput.fill(password);
    await this.loginButton.click();
  }

  async isOnKeycloakPage(): Promise<boolean> {
    const url = this.page.url();
    return url.includes('auth.dev.local') ||
           this.usernameInput.isVisible({ timeout: 5000 }).catch(() => false);
  }

  async waitForKeycloakPage() {
    // Wait for either redirect to complete or username input to be visible
    await this.usernameInput.waitFor({ state: 'visible', timeout: 30000 });
  }
}
