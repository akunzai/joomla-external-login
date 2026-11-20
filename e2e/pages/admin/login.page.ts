import { type Page, type Locator } from '@playwright/test';

export class AdminLoginPage {
  readonly page: Page;
  readonly usernameInput: Locator;
  readonly passwordInput: Locator;
  readonly loginButton: Locator;
  readonly errorMessage: Locator;

  constructor(page: Page) {
    this.page = page;
    this.usernameInput = page.getByRole('textbox', { name: 'Username' });
    this.passwordInput = page.getByRole('textbox', { name: 'Password' });
    // Scoped to core mod_login's form: the External Login administrator module on the same page
    // renders its own "Log in" button.
    this.loginButton = page.locator('#form-login').getByRole('button', { name: 'Log in' });
    this.errorMessage = page.getByText('Username and password do not match', { exact: false });
  }

  async goto() {
    await this.page.goto('/administrator/');
  }

  async login(username: string, password: string) {
    await this.usernameInput.fill(username);
    await this.passwordInput.fill(password);
    await this.loginButton.click();
  }

  /** Starts administrator SSO through the External Login administrator module. */
  async clickExternalLogin(serverTitle: string) {
    const module = this.page.locator('#external-login');
    await module.getByLabel('Choose server').selectOption({ label: serverTitle });
    await module.getByRole('button', { name: 'Log in' }).click();
  }

  async isLoggedIn(): Promise<boolean> {
    return this.page.locator('.com-cpanel').isVisible({ timeout: 5000 }).catch(() => false);
  }
}
