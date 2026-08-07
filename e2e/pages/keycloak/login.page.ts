import { type Page, type Locator } from '@playwright/test';

/** Site host after IdP redirects back (query/path may vary). */
const SITE_URL = /^https:\/\/www\.dev\.local/;

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
    await this.usernameInput.waitFor({ state: 'visible', timeout: 30000 });
    await this.usernameInput.fill(username);
    await this.passwordInput.fill(password);
    await this.loginButton.click();
  }

  /**
   * After the site starts an IdP login, either:
   * - Keycloak shows a credential form (no IdP session), or
   * - the browser returns to the site (existing IdP SSO / silent reuse).
   *
   * Prefer this over a fixed isVisible timeout: polling form-vs-return avoids
   * the flaky path where the form is still loading when a short timeout fires
   * and the test then waitForURL's the site while still stuck on Keycloak.
   * (A Promise.race of two waits would leave the loser as an unhandled reject.)
   */
  async completeLoginIfPrompted(
    username: string,
    password: string,
    options: { timeout?: number } = {},
  ) {
    const timeout = options.timeout ?? 30000;
    const deadline = Date.now() + timeout;

    while (Date.now() < deadline) {
      if (SITE_URL.test(this.page.url())) {
        return;
      }

      if (await this.usernameInput.isVisible().catch(() => false)) {
        await this.login(username, password);
        const remaining = Math.max(10_000, deadline - Date.now());
        await this.page.waitForURL(SITE_URL, { timeout: remaining });
        return;
      }

      await this.page.waitForTimeout(200);
    }

    throw new Error(
      `Timed out after ${timeout}ms waiting for Keycloak login form or redirect back to site (url=${this.page.url()})`,
    );
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
